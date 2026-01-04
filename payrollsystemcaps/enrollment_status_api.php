<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

$employee_id = (int)($_GET['employee_id'] ?? 0);

if (!$employee_id) {
    echo json_encode(['status' => null, 'message' => 'Invalid employee ID']);
    exit;
}

// Get latest status from database
$stmt = $conn->prepare("
    SELECT status, message, updated_at 
    FROM enrollment_status 
    WHERE employee_id = ? 
    ORDER BY updated_at DESC 
    LIMIT 1
");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['status' => null, 'message' => 'No status found']);
}
?>