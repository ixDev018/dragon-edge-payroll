<?php

require 'vendor/autoload.php';
require 'config.php';
require 'db_connection.php';

session_start();

file_put_contents('debug_session_id.log', session_id() . PHP_EOL, FILE_APPEND);

header('Content-Type: application/json');

$employeeId = $_GET['employee_id'] ?? ($_SESSION['employee_id'] ?? null);
if (!$employeeId) {
    http_response_code(400);
    echo json_encode(['error' => 'employee_id is required']);
    exit;
}

$stmt = $conn->prepare('SELECT credential_id FROM webauthn_credentials WHERE employee_id = ?');
$stmt->bind_param('i', $employeeId);
$stmt->execute();
$result = $stmt->get_result();

$credentialIds = [];
while ($row = $result->fetch_assoc()) {
    $credentialIds[] = $row['credential_id'];
}

$stmt->close();

$webAuthn = new \lbuchs\WebAuthn\WebAuthn(RP_NAME, RP_ID, ORIGIN, true);

$getArgs = $webAuthn->getGetArgs($credentialIds, 60);

$getArgs->publicKey->userVerification = 'required';

$_SESSION['webauthn_challenge'] = $webAuthn->getChallenge();
$_SESSION['webauthn_user'] = $employeeId;

echo json_encode($getArgs);
