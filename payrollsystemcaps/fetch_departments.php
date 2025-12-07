<?php
include 'db_connection.php';

$query = "SELECT * FROM departments";
$result = mysqli_query($conn, $query);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode(['data' => $data]);
?>
