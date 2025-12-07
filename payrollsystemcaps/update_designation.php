<?php
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $designation_id = $_POST['designation_id'];
    $designation_name = $_POST['designation_name'];
    $department_id = $_POST['department_id'];

    $query = "UPDATE designations SET designation_name = ?, department_id = ?, modified_date = NOW() WHERE designation_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $designation_name, $department_id, $designation_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }

    $stmt->close();
    $conn->close();
}
?>
