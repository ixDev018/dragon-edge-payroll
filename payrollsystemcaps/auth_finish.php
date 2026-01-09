<?php

require 'vendor/autoload.php';
require 'config.php';
require 'db_connection.php';

session_start();

file_put_contents('debug_auth_finish.log',
'SESSION challenge exists: ' . (isset($_SESSION['webauthn_challenge']) ? 'yes' : 'no') . PHP_EOL .
'SESSION keys: ' . print_r(array_keys($_SESSION), true) . PHP_EOL,
FILE_APPEND
);
file_put_contents('debug_session_id.log', session_id() . PHP_EOL, FILE_APPEND);

header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);

if (!$body) { 
    http_response_code(400); 
    echo json_encode(['error'=>'no body']);
    exit; 
}

if (!isset($_SESSION['webauthn_challenge'], $_SESSION['webauthn_user'])) {
    http_response_code(400);
    echo json_encode(['error'=>'missing session challenge']);
    exit;
}

$challenge = $_SESSION['webauthn_challenge'];
$employeeId = $_SESSION['webauthn_user'];

$credential = $body['credential'] ?? null;
if (!$credential) {
    http_response_code(400);
    echo json_encode(['error'=>'missing credential']);
    exit;
}

$rawId_b64url = $credential['rawId'] ?? $credential['id'];
$clientDataJSON = $credential['response']['clientDataJSON'] ?? null;
$authenticatorData = $credential['response']['authenticatorData'] ?? null;
$signature = $credential['response']['signature'] ?? null;

if (!$clientDataJSON || !$authenticatorData || !$signature) {
    http_response_code(400);
    echo json_encode(['error'=>'incomplete credential response']);
    exit;
}

$rawId_bin = base64_decode(strtr($rawId_b64url, '-_', '+/'));

$stmt = $conn->prepare("SELECT * FROM webauthn_credentials WHERE credential_id = ?");
$stmt->bind_param("s", $rawId_bin);
$stmt->execute();
$result = $stmt->get_result();
$credRow = $result->fetch_assoc();

if (!$credRow) {
    http_response_code(403);
    echo json_encode(['error' => 'unknown credential']);
    exit;
}

$publicKey = $credRow['public_key'];
$prevSignCount = (int)$credRow['sign_count'];

$webAuthn = new \lbuchs\WebAuthn\WebAuthn(RP_NAME, RP_ID, ORIGIN, true);

try {
    $data = $webAuthn->processGet(
        base64_decode(strtr($clientDataJSON, '-_', '+/')),
        base64_decode(strtr($authenticatorData, '-_', '+/')),
        base64_decode(strtr($signature, '-_', '+/')),
        $publicKey,
        $challenge,
        $prevSignCount
    );

    $newSignCount = $webAuthn->getSignatureCounter();
    if ($newSignCount !== null) {
        $up = $conn->prepare('UPDATE webauthn_credentials SET sign_count = ? WHERE id = ?');
        $up->bind_param('ii', $newSignCount, $credRow['id']);
        $up->execute();
    }

    $action = $body['action'] ?? 'time_in';
    $deviceInfo = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $ipAddr = $_SERVER['REMOTE_ADDR'] ?? null;
    $method = 'webauthn';

    $logAttendance = $conn->prepare('INSERT INTO attendance_logs (employee_id, action, device_info, ip_address, method) VALUES (?, ?, ?, ?, ?)');
    $logAttendance->bind_param('issss', $employeeId, $action, $deviceInfo, $ipAddr, $method);
    $logAttendance->execute();

    echo json_encode(['status' => 'ok', 'action' => $action]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => 'verification failed: ' . $e->getMessage()]);
}

