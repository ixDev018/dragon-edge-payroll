<?php

require 'db_connection.php';

$body = json_decode(file_get_contents('php://input'), true);
$employee_id = (int)($body['employee_id'] ?? 0);
$employee_name = $body['name'] ?? '';
$email = trim($body['email'] ?? '');
$password = $body['password'] ?? '';

if (!$employee_id || !$email || !$password || !$employee_name) {
  echo json_encode(['ok' => false, 'message' => 'All fields are required.']);
  exit;
}

try {
  $stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
  $stmt->bind_param("i", $employee_id);
  $stmt->execute();
  $employee = $stmt->get_result()->fetch_assoc();

  if (!$employee) {
    echo json_encode(['ok' => false, 'message' => 'Employee not found.']);
    exit;
  }

  $stmt = $conn->prepare("SELECT * FROM employee_accounts WHERE employee_id = ? OR email = ?");
  $stmt->bind_param("is", $employee_id, $email);
  $stmt->execute();
  if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['ok' => false, 'message' => 'Account already exists.']);
    exit;
  }

  $password_hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $conn->prepare("INSERT INTO employee_accounts (employee_id, email, password, employee_name) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("isss", $employee_id, $email, $password_hash, $employee_name);
  $stmt->execute();

  echo json_encode(['ok' => true, 'message' => 'Registration successful! You can now log in.']);

} catch (Exception $e) {
  echo json_encode(['ok' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
