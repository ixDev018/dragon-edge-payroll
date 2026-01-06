<?php
include 'db_connection.php';

$sql = "SELECT * FROM shifts";
$result = mysqli_query($conn, $sql);
$data = array();

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode(["data" => $data]);
?>
