<?php
include 'db_connection.php';

if (isset($_POST['designation_id'])) {
    $designation_id = $_POST['designation_id'];

    $sql = "SELECT d.designation_id, d.designation_name, dep.dept_name AS department_name, 
                   d.created_date, d.modified_date
            FROM designations d
            JOIN departments dep ON d.department_id = dep.dept_id
            WHERE d.designation_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $designation_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    }

    $stmt->close();
    $conn->close();
}
?>
