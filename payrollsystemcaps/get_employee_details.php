<?php
include 'db_connection.php'; 

if (isset($_GET['id'])) {
    $employeeId = $_GET['id'];
    $sql = "SELECT * FROM employees WHERE employee_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode($row); 
    } else {
        echo json_encode(['error' => 'Employee not found']);
    }
    
    $stmt->close();
}

$conn->close();
?>
