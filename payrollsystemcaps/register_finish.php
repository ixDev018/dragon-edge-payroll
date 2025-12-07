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

header('Content-Type: application/json');

function base64url_decode_strict($b64u) {
    if (!$b64u) return false;
    $b64 = strtr($b64u, '-_', '+/');
    $pad = (4 - (strlen($b64) % 4)) % 4;
    return base64_decode($b64 . str_repeat('=', $pad));
}

$bodyRaw = file_get_contents('php://input');
$body = json_decode($bodyRaw, true);

if (!$body) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or empty request body', 'raw' => $bodyRaw]);
    exit;
}

if (!isset($_SESSION['webauthn_challenge'], $_SESSION['webauthn_user'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing session challenge or user']);
    exit;
}

$session_b64u = $_SESSION['webauthn_challenge'];
$session_raw = base64url_decode_strict($session_b64u);
if ($session_raw === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Corrupted stored challenge']);
    exit;
}

$clientDataJSON_raw = base64url_decode_strict($body['clientDataJSON'] ?? '');
$attestationObject_raw = base64url_decode_strict($body['attestationObject'] ?? '');

if ($clientDataJSON_raw === false || $attestationObject_raw === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid attestation data']);
    exit;
}

$decodedClientData = json_decode($clientDataJSON_raw, true);
$clientChallenge_b64u = $decodedClientData['challenge'] ?? null;
$clientChallenge_raw = base64url_decode_strict($clientChallenge_b64u);

file_put_contents('debug_challenge_compare.log',
    "session_b64u: $session_b64u\nsession_hex: " . bin2hex($session_raw) . "\n" .
    "client_b64u: $clientChallenge_b64u\nclient_hex: " .
    ($clientChallenge_raw ? bin2hex($clientChallenge_raw) : 'NULL') . "\n" .
    "match: " . (($clientChallenge_raw && hash_equals($session_raw, $clientChallenge_raw)) ? 'YES' : 'NO') . "\n\n",
    FILE_APPEND
);

if (!$clientChallenge_raw || !hash_equals($session_raw, $clientChallenge_raw)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid challenge']);
    exit;
}

try {
    $challengeByteBuffer = new \lbuchs\WebAuthn\Binary\ByteBuffer($session_raw);
    $webAuthn = new \lbuchs\WebAuthn\WebAuthn(RP_NAME, RP_ID, ORIGIN, true);

    $cred = $webAuthn->processCreate($clientDataJSON_raw, $attestationObject_raw, $challengeByteBuffer);

    $credentialId = $cred->attestation->credentialId ?? null;
    $publicKeyPem = $cred->attestation->publicKeyPem ?? null;
    $signCount = (int)($cred->attestation->signCount ?? 0);
    $userHandle = $cred->attestation->userHandle ?? null;

    file_put_contents('debug_cred_dump.log', print_r($cred, true), FILE_APPEND);

    $credentialId = $cred->credentialId ?? null;
    $publicKeyPem = $cred->credentialPublicKey ?? null;
    $signCount = (int)($cred->signatureCounter ?? 0);
    $userHandle = $cred->userHandle ?? null;

    if (!$credentialId) {
        echo json_encode(['error' => 'Missing credential ID']);
        exit;
    }


    $stmt = $conn->prepare('INSERT INTO webauthn_credentials (employee_id, credential_id, public_key, sign_count, user_handle) VALUES (?, ?, ?, ?, ?)');
    $credentialBinary = hex2bin($credentialId) ?: $credentialId;
    $stmt->bind_param('sssis', $_SESSION['webauthn_user'], $credentialBinary, $publicKeyPem, $signCount, $userHandle);

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error' => 'DB error: '.$stmt->error]);
        exit;
    }

    unset($_SESSION['webauthn_challenge'], $_SESSION['webauthn_challenge_raw']);

    echo json_encode(['status' => 'ok']);
    exit;

} catch (Throwable $e) {
    http_response_code(400);
    file_put_contents('debug_php_fatal.log', date('c').' '.$e->getMessage()."\n".$e->getTraceAsString()."\n\n", FILE_APPEND);
    echo json_encode(['error' => 'Validation failed: '.$e->getMessage()]);
    exit;
}
