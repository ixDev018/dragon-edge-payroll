<?php

require 'db_connection.php';

session_start();

$accountId = $_SESSION['user_id'];

try {
  $stmt = $conn->prepare("SELECT e.employee_name, e.role, e.department_name, p.basic_pay, p.overtime_pay, p.late_deduction, p.other_deductions, p.net_pay, p.cutoff_from, p.cutoff_to FROM payroll p INNER JOIN employees e ON e.employee_id = p.employee_id INNER JOIN employee_accounts ea ON ea.employee_id = e.employee_id WHERE ea.id = ? ORDER BY p.cutoff_to DESC LIMIT 1");

  $stmt->bind_param('i', $accountId);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 0) {
    echo json_encode(['ok' => false, 'error' => 'No payslip found for this employee']);
    exit;
  }

  $payslip = $result->fetch_assoc();
  echo json_encode(['ok' => true, 'payslip' => $payslip]);

} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
