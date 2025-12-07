<?php
// payroll_data.php
require 'db_connection.php';
header('Content-Type: application/json');

// 1. Get parameters from the Javascript request
$start_date = $_GET['start'] ?? '';
$end_date = $_GET['end'] ?? '';
$search = $_GET['search'] ?? '';

// 2. Start building the SQL Query
// We join employees to get the name, and select from payrolls
$sql = "SELECT p.*, CONCAT(e.first_name, ' ', e.last_name) as employee_name 
        FROM payrolls p 
        JOIN employees e ON p.employee_id = e.id 
        WHERE 1=1"; // "1=1" is a trick to make appending "AND" clauses easier

$params = [];
$types = "";

// 3. Apply Date Filter if both dates are provided
if (!empty($start_date) && !empty($end_date)) {
    $sql .= " AND p.payroll_period_start >= ? AND p.payroll_period_end <= ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

// 4. Apply Search Filter if typed
if (!empty($search)) {
    $searchTerm = "%" . $search . "%";
    $sql .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_id LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

// 5. Order by newest first
$sql .= " ORDER BY p.id DESC";

// 6. Execute Query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while($row = $result->fetch_assoc()) {
    // Format data for easier display in JS
    $startFmt = date('M d', strtotime($row['payroll_period_start']));
    $endFmt = date('M d, Y', strtotime($row['payroll_period_end']));
    $row['period_display'] = "$startFmt - $endFmt";
    
    // Add formatted number strings
    $row['gross_display'] = number_format($row['gross_pay'], 2);
    $row['deductions_display'] = number_format($row['total_deductions'], 2);
    $row['net_display'] = number_format($row['net_pay'], 2);

    $data[] = $row;
}

echo json_encode($data);
?>