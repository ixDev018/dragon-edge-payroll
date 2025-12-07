<?php

require 'db_connection.php';

header('Content-Type: application/json');

$query = "
SELECT 
    e.employee_id,
    e.employee_name,
    e.department_name,
    DATE(a.timestamp) AS date,
    MIN(CASE WHEN a.type = 'regular' AND a.action = 'time_in' THEN TIME(a.timestamp) END) AS time_in,
    MIN(CASE WHEN a.type = 'lunch' AND a.action = 'time_out' THEN TIME(a.timestamp) END) AS lunch_out,
    MAX(CASE WHEN a.type = 'lunch' AND a.action = 'time_in' THEN TIME(a.timestamp) END) AS lunch_in,
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
GROUP BY e.employee_id, DATE(a.timestamp)
ORDER BY DATE(a.timestamp) DESC, e.employee_name ASC
";

$stmt = $conn->prepare($query);
$stmt->execute();
$res = $stmt->get_result();

$logs = [];
while ($row = $res->fetch_assoc()) {
    //echo var_dump($row);
    $logs[] = $row;
}

echo json_encode(['ok' => true, 'logs' => $logs]);