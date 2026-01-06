<?php
include 'db_connection.php';

$query = "SELECT dept_id, dept_name FROM departments ORDER BY dept_name ASC";
$result = mysqli_query($conn, $query);

$options = "<option value='' disabled selected>📌 Select a Department</option>";

while ($row = mysqli_fetch_assoc($result)) {
    $options .= "<option value='{$row['dept_id']}'>📂 {$row['dept_name']}</option>";
}

echo $options;
?>
