<?php
include 'db_connection.php';

if (isset($_POST['dept_name'])) {
    $dept_name = $_POST['dept_name'];
    $query = "INSERT INTO departments (dept_name) VALUES ('$dept_name')";
    
    if (mysqli_query($conn, $query)) {
        echo "success";
    } else {
        echo "error";
    }
}
?>
