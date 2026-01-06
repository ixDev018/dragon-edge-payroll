<?php

session_start();

require 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT employee_id FROM employee_accounts WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['ok' => false, 'error' => 'Employee not found for this account']);
    exit;
}

$row = $result->fetch_assoc();
$employeeId = $row['employee_id'];

$stmt = $conn->prepare("SELECT action, timestamp, device_info, ip_address, method FROM attendance_logs WHERE employee_id = ? ORDER BY timestamp DESC");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

echo json_encode(['ok' => true, 'employee_id' => $employeeId, 'logs' => $logs]);
