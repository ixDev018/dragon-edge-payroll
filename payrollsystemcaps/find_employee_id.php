<?php

require 'db_connection.php';

$search = $_GET['name'] ?? '';

if (empty($search)) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT employee_id, employee_name FROM employees WHERE employee_name LIKE ?");
$like = "%$search%";
$stmt->bind_param("s", $like);
$stmt->execute();
$result = $stmt->get_result();

$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}

echo json_encode($employees);
