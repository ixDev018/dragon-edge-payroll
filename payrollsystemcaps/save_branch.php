<?php
include 'db_connection.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $branch_name = $_POST['branch_name'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];
    $manager_name = $_POST['manager_name'];
    $email = $_POST['email'];
    $operating_hours = $_POST['operating_hours'];

    $sql = "INSERT INTO branches (branch_name, phone_number, address, manager_name, email, operating_hours) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $branch_name, $phone_number, $address, $manager_name, $email, $operating_hours);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Branch added successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to add branch."]);
    }

    $stmt->close();
    $conn->close();
}

?>
