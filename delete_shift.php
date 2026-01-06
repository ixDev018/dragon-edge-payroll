<?php
include 'db_connection.php';

if (isset($_POST['shift_id'])) {
    $shift_id = $_POST['shift_id'];
    $query = "DELETE FROM shifts WHERE shift_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $shift_id);
    
    if ($stmt->execute()) {
        echo "Success";
    } else {
        echo "Error";
    }
}
?>
