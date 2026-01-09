<?php

require 'vendor/autoload.php';
require 'config.php';
require 'db_connection.php';

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');
session_start();

if (!isset($_SESSION['webauthn_challenge_raw'])) {
    $_SESSION['webauthn_challenge_raw'] = random_bytes(32);
}

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$challenge_b64u = base64url_encode($_SESSION['webauthn_challenge_raw']);

$_SESSION['webauthn_challenge'] = $challenge_b64u;


header('Content-Type: application/json');

$employeeId = $_GET['employee_id'] ?? ($_SESSION['employee_id'] ?? null);
if (!$employeeId) {
    http_response_code(400);
    echo json_encode(['error' => 'employee_id is required']);
    exit;
}

$stmt = $conn->prepare('SELECT * FROM employees WHERE employee_id = ?');
$stmt->bind_param('i', $employeeId);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();
if (!$employee) {
    http_response_code(404);
    echo json_encode(['error' => 'employee not found']);
    exit;
}

$webAuthn = new lbuchs\WebAuthn\WebAuthn(RP_NAME, RP_ID, ORIGIN, true);

$userId = (string)$employee['employee_id'];
$requireResidentKey = false;

$createArgs = $webAuthn->getCreateArgs($userId, $employee['role'] ?? $employee['email'] ?? '', $employee['employee_name'] ?? $employee['employee_id'], 60, $requireResidentKey);

if (isset($createArgs->publicKey)) {
    $createArgs->publicKey->challenge = $challenge_b64u;
} else {
    $createArgs->challenge = $challenge_b64u;
}

$_SESSION['webauthn_user'] = $userId;

if (connection_status() === CONNECTION_NORMAL && ob_get_length() === 0) {
    echo json_encode(['error' => 'Empty response: script ended unexpectedly']);
}
ob_end_flush();
ob_end_clean();
echo json_encode($createArgs);
exit;

