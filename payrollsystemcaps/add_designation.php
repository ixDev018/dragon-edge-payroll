<?php
include 'db_connection.php';

if (isset($_POST['designation_name']) && isset($_POST['department_id'])) {
    $designation_name = $_POST['designation_name'];
    $department_id = $_POST['department_id'];

    $query = "INSERT INTO designations (designation_name, department_id) VALUES ('$designation_name', '$department_id')";
    
    if (mysqli_query($conn, $query)) {
        echo "Success";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
