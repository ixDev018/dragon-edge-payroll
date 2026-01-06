<?php

header('Content-Type: application/json');

$API_KEY = 'replace_with_a_strong_key';

$headers = getallheaders();
if (!isset($headers['X-Api-Key']) || $headers['X-Api-Key'] !== $API_KEY) {
    http_response_code(401);
    echo json_encode(['error'=>'unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['employee_number']) || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['error'=>'missing parameters']);
    exit;
}

$employee_number = $input['employee_number'];
$action = strtoupper($input['action']);
$recorded_at = isset($input['recorded_at']) ? $input['recorded_at'] : date('Y-m-d H:i:s');

if (!in_array($action, ['TIMEIN','TIMEOUT'])) {
    http_response_code(400);
    echo json_encode(['error'=>'invalid action']);
    exit;
}

$mysqli = new mysqli('127.0.0.1', 'root', '', 'attendance_db');
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(['error'=>'db connection failed']);
    exit;
}

$stmt = $mysqli->prepare("SELECT id FROM employees WHERE employee_number = ?");
$stmt->bind_param('s', $employee_number);
$stmt->execute();
$stmt->bind_result($employee_id);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error'=>'employee not found']);
    exit;
}
$stmt->close();

$stmt2 = $mysqli->prepare("INSERT INTO attendance (employee_id, action, recorded_at) VALUES (?, ?, ?)");
$stmt2->bind_param('iss', $employee_id, $action, $recorded_at);
if ($stmt2->execute()) {
    echo json_encode(['success'=> true]);
} else {
    http_response_code(500);
    echo json_encode(['error'=> 'insert failed']);
}
$stmt2->close();
$mysqli->close();
