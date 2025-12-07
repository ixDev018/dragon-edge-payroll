<?php

include 'db_connection.php';

$employeeId = $_GET['employee_id'] ?? null;
if (!$employeeId) {
    echo "<p class='text-danger text-center'>No Employee ID provided.</p>";
    exit;
}

$stmt = $conn->prepare("SELECT e.employee_name, al.action, al.timestamp, al.method FROM attendance_logs AS al LEFT JOIN employees AS e ON al.employee_id = e.employee_id WHERE al.employee_id = ? ORDER BY al.timestamp DESC LIMIT 20
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p class='text-muted text-center'>No logs found for today.</p>";
    exit;
}

echo "<table class='table table-bordered text-center'>";
echo "<thead><tr><th>Employee</th><th>Action</th><th>Date & Time</th><th>Method</th></tr></thead><tbody>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['employee_name'] ?? 'Unknown') . "</td>";
    echo "<td>" . ucfirst(htmlspecialchars($row['action'])) . "</td>";
    echo "<td>" . date("Y-m-d h:i A", strtotime($row['timestamp'])) . "</td>";
    echo "<td>" . htmlspecialchars($row['method']) . "</td>";
    echo "</tr>";
}

echo "</tbody></table>";
