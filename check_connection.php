<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

// Check if any device is online (last seen within 2 minutes)
$stmt = $conn->query("
    SELECT COUNT(*) as count 
    FROM biometric_devices 
    WHERE status = 'online' 
    AND last_seen >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
");

$row = $stmt->fetch_assoc();
$connected = $row['count'] > 0;

echo json_encode(['connected' => $connected]);
?>