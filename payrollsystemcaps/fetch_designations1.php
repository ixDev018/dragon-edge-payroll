<?php
require 'db_connection.php'; 

if (isset($_GET['type'])) {
    $type = $_GET['type'];
    $query = "";

    switch ($type) {
        case 'departments':
            $query = "SELECT dept_name AS name FROM departments";
            break;
        case 'branches':
            $query = "SELECT branch_name AS name FROM branches";
            break;
        case 'designations':
            $query = "SELECT designation_name AS name FROM designations";
            break;
        case 'shifts':
            $query = "SELECT shift_name AS name FROM shifts";
            break;
        default:
            echo json_encode([]);
            exit();
    }

    $result = mysqli_query($conn, $query);
    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    echo json_encode($data);
}
?>
