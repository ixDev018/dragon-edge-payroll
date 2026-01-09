<?php
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $branch_id = $_POST['branch_id'];

    $sql = "DELETE FROM branches WHERE branch_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $branch_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Branch deleted successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to delete branch."]);
    }

    $stmt->close();
    $conn->close();
}
?>
