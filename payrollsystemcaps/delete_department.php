<?php
include 'db_connection.php';

if (isset($_POST['dept_id'])) {
    $dept_id = $_POST['dept_id'];
    $query = "DELETE FROM departments WHERE dept_id = $dept_id";
    
    if (mysqli_query($conn, $query)) {
        echo "success";
    } else {
        echo "error";
    }
}
?>
