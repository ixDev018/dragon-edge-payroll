<?php
include 'db_connection.php'; 

header('Content-Type: application/json');

$sql = "SELECT d.designation_id, d.designation_name, dep.dept_name AS department_name 
        FROM designations d 
        JOIN departments dep ON d.department_id = dep.dept_id";
        
$result = $conn->query($sql);

$data = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode(["data" => $data]);
$conn->close();
?>
