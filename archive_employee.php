<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = $_POST['employee_id'];

    require_once 'db_connection.php';

    $stmt = $conn->prepare("UPDATE employee_accounts SET status = 'archived' WHERE employee_id = ?");
    $stmt->bind_param("s", $employeeId);

    if ($stmt->execute()) {
        echo "success";
    } else {
        http_response_code(500);
        echo "Failed to archive.";
    }

    $stmt->close();
    $conn->close();
}
?>
