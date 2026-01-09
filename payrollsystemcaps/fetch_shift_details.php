<?php
include 'db_connection.php';

if (isset($_POST['shift_id'])) {
    $shift_id = $_POST['shift_id'];
    $query = "SELECT * FROM shifts WHERE shift_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $shift_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $shift = $result->fetch_assoc();
    
    echo json_encode($shift);
}
?>
