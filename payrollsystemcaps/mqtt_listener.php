<?php
/**
 * Dragon Edge MQTT Listener
 * Listens to MQTT attendance messages and saves to database
 * Run this script: php mqtt_listener.php
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/db_connection.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// MQTT Configuration (match your ESP32 settings)
$mqtt_server = "fad64f7d54c740f7b5b3679bdba0f4cf.s1.eu.hivemq.cloud";
$mqtt_port = 8883;
$mqtt_user = "dragonedge";
$mqtt_pass = "DragonEdge2025!";
$mqtt_client_id = "DragonEdge-PHP-Listener-" . getmypid();

// MQTT Topics
$topic_attendance = "dragonedge/attendance";
$topic_enrollment = "dragonedge/enrollment";
$topic_status = "dragonedge/status";

echo "╔════════════════════════════════════════╗\n";
echo "║  Dragon Edge MQTT Listener v2.0       ║\n";
echo "║  Connecting attendance to database     ║\n";
echo "╚════════════════════════════════════════╝\n\n";

// ═══════════════════════════════════════════════════════════════
//  MESSAGE HANDLERS
// ═══════════════════════════════════════════════════════════════

function handleAttendance($topic, $msg) {
    global $conn;
    
    $data = json_decode($msg, true);
    if (!$data) {
        echo "❌ Invalid JSON received\n";
        return;
    }
    
    $fingerprint_id = $data['fingerprint_id'] ?? null;
    $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
    $is_admin = $data['is_admin'] ?? false;
    
    if (!$fingerprint_id) {
        echo "❌ No fingerprint ID in message\n";
        return;
    }
    
    // Find employee from employees table
    $stmt = $conn->prepare("SELECT id, first_name, last_name FROM employees WHERE fingerprint_id = ? AND is_active = 1");
    $stmt->bind_param("i", $fingerprint_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "⚠️  Fingerprint ID $fingerprint_id not mapped to any employee\n";
        $stmt->close();
        return;
    }
    
    $row = $result->fetch_assoc();
    $employee_id = $row['id'];
    $employee_name = $row['first_name'] . ' ' . $row['last_name'];
    $stmt->close();
    
    // Check last action to determine if this is time_in or time_out
    $stmt = $conn->prepare("
        SELECT action 
        FROM attendance_logs_v2 
        WHERE employee_id = ? AND DATE(timestamp) = CURDATE()
        ORDER BY timestamp DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $lastResult = $stmt->get_result();
    
    $action = 'time_in'; // Default to time_in
    if ($lastResult->num_rows > 0) {
        $lastRow = $lastResult->fetch_assoc();
        // If last action was time_in, this should be time_out
        $action = ($lastRow['action'] === 'time_in') ? 'time_out' : 'time_in';
    }
    $stmt->close();
    
    // Insert attendance record
    $stmt = $conn->prepare("
        INSERT INTO attendance_logs_v2 (employee_id, action, type, timestamp, method) 
        VALUES (?, ?, 'regular', ?, 'fingerprint')
    ");
    $stmt->bind_param("iss", $employee_id, $action, $timestamp);
    
    if ($stmt->execute()) {
        $actionLabel = strtoupper(str_replace('_', ' ', $action));
        echo "✓ [$timestamp] $employee_name (#$employee_id) - $actionLabel (FP: $fingerprint_id)\n";
    } else {
        echo "❌ Database error: " . $stmt->error . "\n";
    }
    
    $stmt->close();
}

function handleEnrollment($topic, $msg) {
    global $conn;
    
    $data = json_decode($msg, true);
    if (!$data) return;
    
    $employee_id = $data['employee_id'] ?? null;
    $fingerprint_id = $data['fingerprint_id'] ?? null;
    $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
    
    if (!$employee_id || !$fingerprint_id) {
        echo "❌ Invalid enrollment data\n";
        return;
    }
    
    // Check if employee exists
    $stmt = $conn->prepare("SELECT id, first_name, last_name FROM employees WHERE id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "❌ Employee ID $employee_id not found\n";
        $stmt->close();
        return;
    }
    
    $employee = $result->fetch_assoc();
    $employee_name = $employee['first_name'] . ' ' . $employee['last_name'];
    $stmt->close();
    
    // Update employee fingerprint_id
    $stmt = $conn->prepare("UPDATE employees SET fingerprint_id = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $fingerprint_id, $employee_id);
    
    if ($stmt->execute()) {
        echo "✓ [$timestamp] Enrolled: $employee_name (#$employee_id) → Fingerprint #$fingerprint_id\n";
        
        // Record in biometric_enrollments table
        $stmt = $conn->prepare("
            INSERT INTO biometric_enrollments 
            (employee_id, fingerprint_id, enrolled_by, device_id, enrollment_date)
            VALUES (?, ?, 'admin', 'ESP32', ?)
        ");
        $stmt->bind_param("iis", $employee_id, $fingerprint_id, $timestamp);
        $stmt->execute();
        $stmt->close();
    } else {
        echo "❌ Enrollment failed: " . $stmt->error . "\n";
    }
}

function handleStatus($topic, $msg) {
    $data = json_decode($msg, true);
    if (!$data) return;
    
    $device = $data['device'] ?? 'Unknown';
    $status = $data['status'] ?? 'unknown';
    
    if ($status === 'online') {
        echo "🟢 Device '$device' is online\n";
    }
}

// ═══════════════════════════════════════════════════════════════
//  MAIN EXECUTION
// ═══════════════════════════════════════════════════════════════

// Create connection settings with SSL
$connectionSettings = (new ConnectionSettings)
    ->setUsername($mqtt_user)
    ->setPassword($mqtt_pass)
    ->setUseTls(true)
    ->setTlsSelfSignedAllowed(true)
    ->setTlsVerifyPeer(false)
    ->setTlsVerifyPeerName(false)
    ->setKeepAliveInterval(60)
    ->setConnectTimeout(10);

// Create MQTT client
$mqtt = new MqttClient($mqtt_server, $mqtt_port, $mqtt_client_id);

try {
    echo "Connecting to MQTT broker...\n";
    $mqtt->connect($connectionSettings, true);
    echo "✓ Connected to MQTT broker\n";
    
    // Subscribe to topics
    $mqtt->subscribe($topic_attendance, 'handleAttendance', 0);
    $mqtt->subscribe($topic_enrollment, 'handleEnrollment', 0);
    $mqtt->subscribe($topic_status, 'handleStatus', 0);
    
    echo "✓ Subscribed to all topics\n";
    echo "⏳ Listening for fingerprint scans...\n\n";
    
    // Keep listening
    $mqtt->loop(true);
    
} catch (Exception $e) {
    echo "❌ MQTT Error: " . $e->getMessage() . "\n";
    exit(1);
}

$mqtt->disconnect();
echo "\n🛑 Listener stopped\n";