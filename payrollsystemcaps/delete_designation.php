<?php
include 'db_connection.php';

if (isset($_POST['designation_id'])) {
    $designation_id = $_POST['designation_id'];

    $sql = "DELETE FROM designations WHERE designation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $designation_id);

    if ($stmt->execute()) {
        echo "Success";
    } else {
        echo "Error";
    }

    $stmt->close();
    $conn->close();
}
?>
