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

function process_attendance($conn, $data) {
    $fingerprint_id = (int)($data['fingerprint_id'] ?? 0);
    $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
    $device_id = $data['device'] ?? 'unknown';
    $is_admin = (bool)($data['is_admin'] ?? false);
    
    log_info("Processing attendance: FP_ID=$fingerprint_id, Time=$timestamp");
    
    // 1. Save to biometric_attendance_raw table
    $stmt = $conn->prepare("
        INSERT INTO biometric_attendance_raw 
        (fingerprint_id, device_id, scan_timestamp, is_admin, raw_data, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $raw_json = json_encode($data);
    $stmt->bind_param("issis", $fingerprint_id, $device_id, $timestamp, $is_admin, $raw_json);
    
    if (!$stmt->execute()) {
        log_error("Failed to save raw attendance: " . $stmt->error);
        return false;
    }
    
    // 2. Get employee from fingerprint_id
    $stmt = $conn->prepare("
        SELECT id, employee_id, first_name, last_name, department 
        FROM employees 
        WHERE fingerprint_id = ? AND is_active = 1
    ");
    $stmt->bind_param("i", $fingerprint_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();
    
    if (!$employee) {
        log_error("Employee not found for fingerprint ID: $fingerprint_id");
        return false;
    }
    
    $employee_db_id = $employee['id'];
    $employee_name = $employee['first_name'] . ' ' . $employee['last_name'];
    log_info("Employee identified: $employee_name (DB ID: $employee_db_id)");
    
    // 3. Determine attendance date
    $attendance_date = date('Y-m-d', strtotime($timestamp));
    
    // 4. Count total scans today to determine action
    $stmt = $conn->prepare("
        SELECT COUNT(*) as scan_count
        FROM biometric_attendance_raw
        WHERE fingerprint_id = ?
        AND DATE(created_at) = CURDATE()
    ");
    $stmt->bind_param("i", $fingerprint_id);
    $stmt->execute();
    $count_result = $stmt->get_result()->fetch_assoc();
    $scan_count = $count_result['scan_count'];
    
    // Odd scan = TIME IN, Even scan = TIME OUT
    $is_time_in = ($scan_count % 2 == 1);
    log_info("Scan count today: $scan_count → " . ($is_time_in ? "TIME IN" : "TIME OUT"));
    
    // 5. Check if attendance record exists for today
    $stmt = $conn->prepare("
        SELECT id, clock_in, clock_out 
        FROM attendance_logs 
        WHERE employee_id = ? 
        AND attendance_date = ?
        LIMIT 1
    ");
    $stmt->bind_param("is", $employee_db_id, $attendance_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_log = $result->fetch_assoc();
    
    if ($is_time_in) {
        // TIME IN
        if ($existing_log) {
            // Update existing record
            $stmt = $conn->prepare("
                UPDATE attendance_logs 
                SET clock_in = ?, 
                    status = 'present',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("si", $timestamp, $existing_log['id']);
            
            if ($stmt->execute()) {
                log_success("✓ TIME IN updated for $employee_name at $timestamp");
                return true;
            } else {
                log_error("Failed to update TIME IN: " . $stmt->error);
                return false;
            }
        } else {
            // Create new record
            $stmt = $conn->prepare("
                INSERT INTO attendance_logs 
                (employee_id, attendance_date, clock_in, status, created_at)
                VALUES (?, ?, ?, 'present', NOW())
            ");
            $stmt->bind_param("iss", $employee_db_id, $attendance_date, $timestamp);
            
            if ($stmt->execute()) {
                log_success("✓ TIME IN recorded for $employee_name at $timestamp");
                return true;
            } else {
                log_error("Failed to insert TIME IN: " . $stmt->error);
                return false;
            }
        }
    } else {
        // TIME OUT
        if ($existing_log) {
            // Update with clock_out
            $stmt = $conn->prepare("
                UPDATE attendance_logs 
                SET clock_out = ?,
                    status = 'present',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("si", $timestamp, $existing_log['id']);
            
            if ($stmt->execute()) {
                log_success("✓ TIME OUT recorded for $employee_name at $timestamp");
                return true;
            } else {
                log_error("Failed to update TIME OUT: " . $stmt->error);
                return false;
            }
        } else {
            // Edge case: TIME OUT without TIME IN
            log_error("TIME OUT attempted without TIME IN for $employee_name");
            
            // Create record with only clock_out
            $stmt = $conn->prepare("
                INSERT INTO attendance_logs 
                (employee_id, attendance_date, clock_out, status, created_at)
                VALUES (?, ?, ?, 'incomplete', NOW())
            ");
            $stmt->bind_param("iss", $employee_db_id, $attendance_date, $timestamp);
            
            if ($stmt->execute()) {
                log_success("✓ TIME OUT recorded (incomplete - no TIME IN) for $employee_name");
                return true;
            } else {
                log_error("Failed to insert TIME OUT: " . $stmt->error);
                return false;
            }
        }
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

// Subscribe to fingerprint delete commands
$mqtt->subscribe('dragonedge/fingerprint/delete', function ($topic, $message) use ($conn) {
    $data = json_decode($message, true);
    
    if ($data['action'] === 'delete') {
        $fingerprint_id = $data['fingerprint_id'] ?? 0;
        log_info("🗑️  Delete request for Fingerprint ID: $fingerprint_id");
        
        // Log the deletion
        $stmt = $conn->prepare("
            INSERT INTO fingerprint_deletion_log 
            (fingerprint_id, deleted_at, deleted_by) 
            VALUES (?, NOW(), 'system')
        ");
        $stmt->bind_param("i", $fingerprint_id);
        $stmt->execute();
        
        // Clear fingerprint_id from employees table
        $stmt = $conn->prepare("
            UPDATE employees 
            SET fingerprint_id = NULL, updated_at = NOW() 
            WHERE fingerprint_id = ?
        ");
        $stmt->bind_param("i", $fingerprint_id);
        $stmt->execute();
        
        log_success("✓ Fingerprint ID $fingerprint_id removed from database");
    }
    
    else if ($data['action'] === 'clear_all') {
        log_info("🗑️  CLEAR ALL fingerprints request received");
        
        // Clear all fingerprint IDs
        $conn->query("UPDATE employees SET fingerprint_id = NULL, updated_at = NOW()");
        $conn->query("INSERT INTO fingerprint_deletion_log (fingerprint_id, deleted_at, deleted_by, notes) VALUES (0, NOW(), 'system', 'Bulk clear all')");
        
        log_success("✓ All fingerprint IDs cleared from database");
    }
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