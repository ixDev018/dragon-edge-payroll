<?php

require 'db_connection.php';
require 'generate_payroll.php';

$body = json_decode(file_get_contents('php://input'), true);
$emp = (int)($body['employee_id'] ?? 0);
$from = $body['from'] ?? null;
$to = $body['to'] ?? null;

try {
  $res = computePayroll($conn, $emp, $from, $to);
  echo json_encode(['ok'=>true,'data'=>$res]);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
