<?php
/**
 * Enhanced MQTT Bridge - Syncs enrollment status to database
 */

require_once 'db_connection.php';
require __DIR__ . '/vendor/autoload.php';
require_once 'handle_unknown_fingerprint.php';  // ⬅️ ADD THIS LINE

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// MQTT Configuration
$mqtt_server = "fad64f7d54c740f7b5b3679bdba0f4cf.s1.eu.hivemq.cloud";
$mqtt_port = 8883;
$mqtt_user = "dragonedge";
$mqtt_pass = "DragonEdge2025!";
$mqtt_client_id = "DragonEdge-Bridge-" . uniqid();

// Topics
$topic_attendance = "dragonedge/attendance";
$topic_enrollment = "dragonedge/enrollment";
$topic_enrollment_status = "dragonedge/enrollment/status";
$topic_status = "dragonedge/status";

// Logging
function log_message($level, $message) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message\n";
    echo $log_entry;
    file_put_contents(__DIR__ . '/mqtt_bridge.log', $log_entry, FILE_APPEND);
}

function log_info($msg) { log_message('INFO', $msg); }
function log_error($msg) { log_message('ERROR', $msg); }
function log_success($msg) { log_message('SUCCESS', $msg); }

// Update enrollment status in database
function update_enrollment_status($conn, $employee_id, $status, $message) {
    $stmt = $conn->prepare("
        INSERT INTO enrollment_status (employee_id, status, message, updated_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
        status = VALUES(status),
        message = VALUES(message),
        updated_at = NOW()
    ");
    $stmt->bind_param("iss", $employee_id, $status, $message);
    return $stmt->execute();
}

// Process attendance
function process_attendance($conn, $data) {
    $fingerprint_id = (int)($data['fingerprint_id'] ?? 0);
    $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
    $device_id = $data['device'] ?? 'unknown';
    $is_admin = (bool)($data['is_admin'] ?? false);
    
    log_info("Processing attendance: FP_ID=$fingerprint_id, Time=$timestamp");
    
    // Save raw attendance
    $stmt = $conn->prepare("
        INSERT INTO biometric_attendance_raw 
        (fingerprint_id, device_id, scan_timestamp, is_admin, raw_data, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $raw_json = json_encode($data);
    $stmt->bind_param("issis", $fingerprint_id, $device_id, $timestamp, $is_admin, $raw_json);
    
    if (!$stmt->execute()) {
        log_error("Failed to save raw attendance");
        return false;
    }
    
    // Get employee
    $stmt = $conn->prepare("SELECT * FROM employees WHERE fingerprint_id = ? AND is_active = 1");
    $stmt->bind_param("i", $fingerprint_id);
    $stmt->execute();
    $employee = $stmt->get_result()->fetch_assoc();
    
    if (!$employee) {
        log_error("Employee not found for fingerprint ID: $fingerprint_id");
        return false;
    }
    
    $employee_id = $employee['id'];
    $employee_name = $employee['first_name'] . ' ' . $employee['last_name'];
    log_info("Employee identified: $employee_name");
    
    // Process via stored procedure
    try {
        $stmt = $conn->prepare("CALL process_biometric_attendance(?, ?, ?)");
        $stmt->bind_param("iss", $fingerprint_id, $device_id, $timestamp);
        
        if ($stmt->execute()) {
            log_success("✓ Attendance processed for $employee_name");
            return true;
        } else {
            log_error("Stored procedure failed");
            return false;
        }
    } catch (Exception $e) {
        log_error("Exception: " . $e->getMessage());
        return false;
    }
}

// Process enrollment
function process_enrollment($conn, $data) {
    $employee_id = (int)($data['employee_id'] ?? 0);
    $fingerprint_id = (int)($data['fingerprint_id'] ?? 0);
    $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
    $device_id = $data['device'] ?? 'unknown';
    $enrolled_by = $data['enrolled_by'] ?? 'admin';
    
    log_info("Processing enrollment: Emp_ID=$employee_id, FP_ID=$fingerprint_id");
    
    // Check employee exists
    $stmt = $conn->prepare("SELECT id, first_name, last_name FROM employees WHERE id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $employee = $stmt->get_result()->fetch_assoc();
    
    if (!$employee) {
        log_error("Employee ID $employee_id not found");
        update_enrollment_status($conn, $employee_id, 'error', 'Employee not found');
        return false;
    }
    
    $employee_name = $employee['first_name'] . ' ' . $employee['last_name'];
    
    // Update employee fingerprint
    $stmt = $conn->prepare("UPDATE employees SET fingerprint_id = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $fingerprint_id, $employee_id);
    
    if (!$stmt->execute()) {
        log_error("Failed to update employee fingerprint");
        update_enrollment_status($conn, $employee_id, 'error', 'Failed to update database');
        return false;
    }
    
    // Record enrollment
    $stmt = $conn->prepare("
        INSERT INTO biometric_enrollments 
        (employee_id, fingerprint_id, enrolled_by, device_id, enrollment_date)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisss", $employee_id, $fingerprint_id, $enrolled_by, $device_id, $timestamp);
    
    if ($stmt->execute()) {
        log_success("✓ $employee_name enrolled with FP ID: $fingerprint_id");
        update_enrollment_status($conn, $employee_id, 'success', 'Enrollment complete!');
        return true;
    } else {
        log_error("Failed to record enrollment");
        update_enrollment_status($conn, $employee_id, 'error', 'Failed to save enrollment record');
        return false;
    }
}

// Handle enrollment status updates from ESP32
function handle_enrollment_status($conn, $data) {
    $employee_id = (int)($data['employee_id'] ?? 0);
    $status = $data['status'] ?? '';
    $message = $data['message'] ?? '';
    
    if ($employee_id && $status) {
        update_enrollment_status($conn, $employee_id, $status, $message);
        log_info("Status update: Emp_ID=$employee_id, Status=$status");
    }
}

// Update device status
function update_device_status($conn, $device_id, $data) {
    $ip = $data['ip'] ?? null;
    $status = $data['status'] ?? 'online';
    
    $stmt = $conn->prepare("
        INSERT INTO biometric_devices (device_id, device_name, ip_address, last_seen, status, is_active)
        VALUES (?, ?, ?, NOW(), ?, 1)
        ON DUPLICATE KEY UPDATE
            ip_address = VALUES(ip_address),
            last_seen = NOW(),
            status = VALUES(status)
    ");
    
    $device_name = $device_id;
    $stmt->bind_param("ssss", $device_id, $device_name, $ip, $status);
    return $stmt->execute();
}

// ═══════════════════════════════════════════════════════════════
//  MAIN EXECUTION
// ═══════════════════════════════════════════════════════════════

log_info("==========================================");
log_info("Enhanced MQTT Bridge Starting...");
log_info("==========================================");

if (!$conn) {
    log_error("Database connection failed!");
    exit(1);
}
log_success("Database connected");

// Setup MQTT connection
$connectionSettings = (new ConnectionSettings)
    ->setUsername($mqtt_user)
    ->setPassword($mqtt_pass)
    ->setUseTls(true)
    ->setTlsSelfSignedAllowed(true)
    ->setTlsVerifyPeer(false)
    ->setTlsVerifyPeerName(false)
    ->setKeepAliveInterval(60)
    ->setConnectTimeout(10)
    ->setSocketTimeout(5);

$mqtt = new MqttClient($mqtt_server, $mqtt_port, $mqtt_client_id);

log_info("Connecting to MQTT broker: $mqtt_server:$mqtt_port");

try {
    $mqtt->connect($connectionSettings, true);
    log_success("MQTT connected successfully");
    
    // Subscribe to attendance
    $mqtt->subscribe($topic_attendance, function ($topic, $message) use ($conn) {
        $data = json_decode($message, true);
        if ($data) {
            process_attendance($conn, $data);
        }
    }, 0);
    
    // Subscribe to enrollment
    $mqtt->subscribe($topic_enrollment, function ($topic, $message) use ($conn) {
        $data = json_decode($message, true);
        if ($data) {
            process_enrollment($conn, $data);
        }
    }, 0);
    
    // Subscribe to enrollment status
    $mqtt->subscribe($topic_enrollment_status, function ($topic, $message) use ($conn) {
        $data = json_decode($message, true);
        if ($data) {
            handle_enrollment_status($conn, $data);
        }
    }, 0);
    
    // Subscribe to device status
    $mqtt->subscribe($topic_status, function ($topic, $message) use ($conn) {
        $data = json_decode($message, true);
        if ($data) {
            $device_id = $data['device'] ?? 'unknown';
            update_device_status($conn, $device_id, $data);
        }
    }, 0);
    
    // ⬇️ ADD THIS ENTIRE BLOCK BELOW YOUR ATTENDANCE SUBSCRIPTION:
$mqtt->subscribe('dragonedge/attendance/unknown', function ($topic, $message) use ($conn) {
    $data = json_decode($message, true);
    
    echo "\n⚠️  UNKNOWN FINGERPRINT DETECTED\n";
    echo "Device: " . ($data['device'] ?? 'Unknown') . "\n";
    echo "Time: " . ($data['timestamp'] ?? date('Y-m-d H:i:s')) . "\n";
    echo "Confidence: " . ($data['confidence'] ?? 0) . "\n";
    
    // Log to database
    $device_id = $data['device'] ?? 'DragonEdge-ESP32-Biometric';
    $confidence = intval($data['confidence'] ?? 0);
    $reason = $data['reason'] ?? 'not_enrolled';
    
    $log_id = logUnknownFingerprint($device_id, $confidence, $reason);
    
    if ($log_id) {
        echo "✓ Logged to database (ID: $log_id)\n";
    } else {
        echo "✗ Failed to log\n";
    }
    echo "\n";
}, 0);
    
    log_success("Subscribed to all topics");
    
    log_info("==========================================");
    log_info("MQTT Bridge running. Waiting for messages...");
    log_info("Press Ctrl+C to stop");
    log_info("==========================================\n");
    
    // Main loop
    $mqtt->loop(true);
    
} catch (Exception $e) {
    log_error("MQTT Error: " . $e->getMessage());
    exit(1);
}

$mqtt->disconnect();
log_info("MQTT Bridge stopped");