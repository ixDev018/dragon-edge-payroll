<?php
header('Content-Type: application/json');
require_once 'db_connection.php';
require __DIR__ . '/vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

$data = json_decode(file_get_contents('php://input'), true);
$employee_id = (int)($data['employee_id'] ?? 0);
$employee_name = $data['employee_name'] ?? '';
$fingerprint_id = (int)($data['fingerprint_id'] ?? 0);

if (!$employee_id || !$employee_name || !$fingerprint_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    // MQTT Configuration
    $mqtt_server = "fad64f7d54c740f7b5b3679bdba0f4cf.s1.eu.hivemq.cloud";
    $mqtt_port = 8883;
    $mqtt_user = "dragonedge";
    $mqtt_pass = "DragonEdge2025!";
    
    $connectionSettings = (new ConnectionSettings)
        ->setUsername($mqtt_user)
        ->setPassword($mqtt_pass)
        ->setUseTls(true)
        ->setTlsSelfSignedAllowed(true)
        ->setTlsVerifyPeer(false)
        ->setTlsVerifyPeerName(false);
    
    $mqtt = new MqttClient($mqtt_server, $mqtt_port, "DragonEdge-Web-" . uniqid());
    $mqtt->connect($connectionSettings, true);
    
    // Prepare enrollment request message
    $message = json_encode([
        'employee_id' => $employee_id,
        'employee_name' => $employee_name,
        'fingerprint_id' => $fingerprint_id,
        'requested_by' => 'web',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // Publish to ESP32
    $mqtt->publish('dragonedge/enrollment/request', $message, 0);
    $mqtt->disconnect();
    
    // Store pending enrollment in database
    $stmt = $conn->prepare("
        INSERT INTO enrollment_status 
        (employee_id, status, message, updated_at)
        VALUES (?, 'waiting_admin', 'Waiting for admin authorization', NOW())
        ON DUPLICATE KEY UPDATE 
        status = 'waiting_admin',
        message = 'Waiting for admin authorization',
        updated_at = NOW()
    ");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Enrollment request sent']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>