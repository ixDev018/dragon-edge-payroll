<?php

require 'db_connection.php';

header('Content-Type: application/json');

$search = $_GET['search'] ?? '';
// $department = $_GET['department'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');

$query = "
SELECT 
    e.employee_id,
    e.employee_name,
    e.department_name,
    DATE(a.timestamp) AS date,
    MAX(CASE WHEN a.type = 'regular' AND a.action = 'time_in' THEN TIME(a.timestamp) END) AS time_in,
    MAX(CASE WHEN a.type = 'regular' AND a.action = 'time_out' THEN TIME(a.timestamp) END) AS time_out,
    CASE 
        WHEN TIMESTAMPDIFF(HOUR, 
            MIN(CASE WHEN a.type='regular' AND a.action='time_in' THEN a.timestamp END),
            MAX(CASE WHEN a.type='regular' AND a.action='time_out' THEN a.timestamp END)
        ) > 9 THEN 'YES' 
        ELSE 'NO' 
    END AS overtime_status
FROM attendance_logs a
JOIN employees e ON e.employee_id = a.employee_id
WHERE DATE(a.timestamp) = ?
";

$params = [$date];
$types = "s";

if (!empty($search)) {
    $query .= " AND (e.employee_name LIKE ? OR e.employee_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

// if (!empty($department)) {
//     $query .= " AND e.department_name LIKE ?";
//     $params[] = "%$department%";
//     $types .= "s";
// }

$query .= " GROUP BY e.employee_id, e.employee_name, e.department_name, DATE(a.timestamp) ORDER BY e.employee_name ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$logs = [];
while ($row = $res->fetch_assoc()) {
    $logs[] = $row;
}

echo json_encode(['ok' => true, 'logs' => $logs]);
