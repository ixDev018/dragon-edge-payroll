<?php
include 'db_connection.php';

if (isset($_POST['dept_id'])) {
    $dept_id = $_POST['dept_id'];
    $query = "SELECT * FROM departments WHERE dept_id = $dept_id";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);

    echo json_encode($row);
}
?>
