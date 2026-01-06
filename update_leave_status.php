<?php

include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_id = $_POST['leave_id'];
    $status = $_POST['status'];

    if ($leave_id && $status) {
        $stmt = $conn->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $leave_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
    }
}
