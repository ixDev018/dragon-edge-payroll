<?php
/**
 * Delete Fingerprint API
 * Removes fingerprint from ESP32 sensor via MQTT
 */

header('Content-Type: application/json');
require_once 'db_connection.php';
require __DIR__ . '/vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// MQTT Configuration
$mqtt_server = "fad64f7d54c740f7b5b3679bdba0f4cf.s1.eu.hivemq.cloud";
$mqtt_port = 8883;
$mqtt_user = "dragonedge";
$mqtt_pass = "DragonEdge2025!";

$method = $_SERVER['REQUEST_METHOD'];

// Check if this is a clear_all request
$data = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? ($data['action'] ?? '');

// Handle clear_all action first
if ($action === 'clear_all') {
    try {
        $connectionSettings = (new ConnectionSettings)
            ->setUsername($mqtt_user)
            ->setPassword($mqtt_pass)
            ->setUseTls(true)
            ->setTlsSelfSignedAllowed(true)
            ->setTlsVerifyPeer(false)
            ->setTlsVerifyPeerName(false);
        
        $mqtt = new MqttClient($mqtt_server, $mqtt_port, "DragonEdge-ClearAll-" . uniqid());
        $mqtt->connect($connectionSettings, true);
        
        $message = json_encode([
            'action' => 'clear_all',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        $mqtt->publish('dragonedge/fingerprint/delete', $message, 0);
        $mqtt->disconnect();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Clear all command sent to ESP32. All fingerprints will be deleted.'
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// POST /delete_fingerprint_api.php - Delete single fingerprint
if ($method === 'POST') {
    $fingerprint_id = (int)($data['fingerprint_id'] ?? 0);
    
    if (!$fingerprint_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid fingerprint ID']);
        exit;
    }
    
    try {
        // Send delete command to ESP32 via MQTT
        $connectionSettings = (new ConnectionSettings)
            ->setUsername($mqtt_user)
            ->setPassword($mqtt_pass)
            ->setUseTls(true)
            ->setTlsSelfSignedAllowed(true)
            ->setTlsVerifyPeer(false)
            ->setTlsVerifyPeerName(false);
        
        $mqtt = new MqttClient($mqtt_server, $mqtt_port, "DragonEdge-Delete-" . uniqid());
        $mqtt->connect($connectionSettings, true);
        
        $message = json_encode([
            'action' => 'delete',
            'fingerprint_id' => $fingerprint_id,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        $mqtt->publish('dragonedge/fingerprint/delete', $message, 0);
        $mqtt->disconnect();
        
        echo json_encode([
            'success' => true, 
            'message' => "Delete command sent to ESP32 for fingerprint ID: $fingerprint_id"
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// If we get here, show usage
else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method',
        'usage' => [
            'POST' => 'Send {"fingerprint_id": X} to delete specific fingerprint',
            'DELETE or POST?action=clear_all' => 'Clear all fingerprints from sensor'
        ]
    ]);
}
?>