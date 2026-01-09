<?php
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $branch_id = $_POST['branch_id'];
    $branch_name = $_POST['branch_name'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];
    $manager_name = $_POST['manager_name'];
    $email = $_POST['email'];
    $operating_hours = $_POST['operating_hours'];

    $sql = "UPDATE branches SET branch_name = ?, phone_number = ?, address = ?, manager_name = ?, email = ?, operating_hours = ? WHERE branch_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $branch_name, $phone_number, $address, $manager_name, $email, $operating_hours, $branch_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Branch updated successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update branch."]);
    }

    $stmt->close();
    $conn->close();
}
?>
