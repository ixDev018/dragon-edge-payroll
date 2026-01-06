<?php
include 'db_connection.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $shift_id = $_POST["shift_id"];
    $shift_name = $_POST["shift_name"];
    $shift_in = $_POST["shift_in"];
    $shift_out = $_POST["shift_out"];

    $query = "UPDATE shifts SET shift_name=?, shift_in=?, shift_out=?, modified_date=NOW() WHERE shift_id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $shift_name, $shift_in, $shift_out, $shift_id);

    if ($stmt->execute()) {
        echo "Success";
    } else {
        echo "Error: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>
