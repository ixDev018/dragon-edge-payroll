<?php
header('Content-Type: application/json');
require_once 'db_connection.php';
require __DIR__ . '/vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

$data = json_decode(file_get_contents('php://input'), true);
$employee_id = (int)($data['employee_id'] ?? 0);

if (!$employee_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
    exit;
}

try {
    // Update status to cancelled
    $stmt = $conn->prepare("
        UPDATE enrollment_status 
        SET status = 'cancelled', 
            message = 'Enrollment cancelled by user',
            updated_at = NOW()
        WHERE employee_id = ?
    ");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    
    // Send cancellation to ESP32 via MQTT
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
    
    $message = json_encode([
        'employee_id' => $employee_id,
        'action' => 'cancel',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    $mqtt->publish('dragonedge/enrollment/cancel', $message, 0);
    $mqtt->disconnect();
    
    echo json_encode(['success' => true, 'message' => 'Enrollment cancelled']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>