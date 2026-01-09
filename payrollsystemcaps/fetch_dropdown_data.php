<?php
include 'db_connection.php'; 

$type = $_GET['type'];
$column_name = "";
$table_name = "";

switch ($type) {
    case "departments":
        $column_name = "department_name";
        $table_name = "departments";
        break;
    case "branches":
        $column_name = "branch_name";
        $table_name = "branches";
        break;
    case "designations":
        $column_name = "designation_name";
        $table_name = "designations";
        break;
    case "shifts":
        $column_name = "shift_name";
        $table_name = "shifts";
        break;
    default:
        echo json_encode([]);
        exit();
}

$sql = "SELECT $column_name AS name FROM $table_name";
$result = $conn->query($sql);

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
$conn->close();
?>
