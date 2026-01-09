<?php
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $shiftName = $_POST["shift_name"];
    $shiftIn = $_POST["shift_in"];
    $shiftOut = $_POST["shift_out"];
    $createdDate = date("Y-m-d");
    $modifiedDate = date("Y-m-d");

    $sql = "INSERT INTO shifts (shift_name, shift_in, shift_out, created_date, modified_date) 
            VALUES ('$shiftName', '$shiftIn', '$shiftOut', '$createdDate', '$modifiedDate')";

    if (mysqli_query($conn, $sql)) {
        echo "Success";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
