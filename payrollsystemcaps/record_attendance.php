<?php

require 'db_connection.php';

session_start();

header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);
$employee_id = (int)($body['employee_id'] ?? 0);
$action = $body['action'] ?? '';
$timestamp = date('Y-m-d H:i:s');
$ip = $_SERVER['REMOTE_ADDR'] ?? null;
$device = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

$hour = (int)date('H', strtotime($timestamp));
$type = ($hour >= 11 && $hour <= 13) ? 'lunch' : 'regular';

$check = $conn->prepare("SELECT action FROM attendance_logs_v2 WHERE employee_id = ? AND DATE(timestamp) = CURDATE() ORDER BY timestamp DESC LIMIT 1");
$check->bind_param("i", $employee_id);
$check->execute();
$res = $check->get_result();
$lastAction = $res->fetch_assoc()['action'] ?? null;

if ($lastAction === $action) {
    echo json_encode(['ok' => false, 'error' => 'Duplicate action for today']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO attendance_logs_v2 (employee_id, action, type, timestamp, method) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issss", $employee_id, $action, $type, $timestamp, $ip, $device);

if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'message' => 'Log recorded successfully', 'type' => $type]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Failed to record log']);
}
