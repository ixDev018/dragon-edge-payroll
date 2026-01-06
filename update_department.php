<?php
include 'db_connection.php';

if (isset($_POST['dept_id']) && isset($_POST['dept_name'])) {
    $dept_id = $_POST['dept_id'];
    $dept_name = $_POST['dept_name'];

    $query = "UPDATE departments SET dept_name = '$dept_name', modified_date = NOW() WHERE dept_id = $dept_id";

    if (mysqli_query($conn, $query)) {
        echo "success";
    } else {
        echo "error";
    }
}
?>
