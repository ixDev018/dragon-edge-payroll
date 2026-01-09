<?php
require 'db_connection.php';

$employee_id = $_GET['employee_id'] ?? '';
$date = $_GET['date'] ?? '';

$response = ["ok" => false, "logs" => []];

if (!$employee_id) {
  echo json_encode($response);
  exit;
}

$query = "
  SELECT 
      employee_id,
      DATE(`timestamp`) AS date,
      MIN(CASE WHEN action = 'time_in' THEN `timestamp` END) AS time_in,
      MAX(CASE WHEN action = 'time_out' THEN `timestamp` END) AS time_out,
      (
        MAX(CASE WHEN action = 'time_out' THEN `timestamp` END) >
        CONCAT(DATE(`timestamp`), ' ', expected_time_out)
      ) AS overtime
  FROM attendance_logs
  WHERE employee_id = ?
";

if (!empty($date)) {
  $query .= " AND DATE(`timestamp`) = ?";
}

$query .= " GROUP BY employee_id, DATE(`timestamp`) ORDER BY date DESC";

$stmt = $conn->prepare($query);

if (!empty($date)) {
  $stmt->bind_param("ss", $employee_id, $date);
} else {
  $stmt->bind_param("s", $employee_id);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $row['overtime'] = $row['overtime'] ? 'YES' : 'NO';
  $row['time_in'] = $row['time_in'] ? date("h:i A", strtotime($row['time_in'])) : null;
  $row['time_out'] = $row['time_out'] ? date("h:i A", strtotime($row['time_out'])) : null;
  $row['date'] = $row['date'] ? date("l, F j, Y", strtotime($row['date'])) : null;

  $response["logs"][] = $row;
}

$response["ok"] = true;
echo json_encode($response);
