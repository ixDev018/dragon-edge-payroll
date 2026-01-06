<?php
/**
 * Get Today's Attendance API (FIXED FOR ESP32 CLOCK DRIFT)
 * Uses server created_at instead of ESP32 attendance_date
 * Returns all attendance records for today in chronological order
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once 'db_connection.php';

try {
    // Use created_at (server time) instead of attendance_date (ESP32 time)
    // This fixes the 8-day clock drift issue
    $query = "
        SELECT 
            e.employee_id,
            CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
            e.department,
            al.clock_in,
            al.clock_out,
            al.created_at,
            al.updated_at
        FROM attendance_logs al
        JOIN employees e ON e.id = al.employee_id
        WHERE DATE(al.created_at) = CURDATE()
        ORDER BY al.created_at DESC
        LIMIT 50
    ";
    
    $result = $conn->query($query);
    $logs = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // ALWAYS add time_in first (every record has clock_in)
            $logs[] = [
                'employee_id' => $row['employee_id'],
                'employee_name' => $row['employee_name'],
                'department' => $row['department'],
                'action' => 'time_in',
                'timestamp' => $row['clock_in'],
                'method' => 'fingerprint',
                'sort_time' => strtotime($row['created_at']) // Use server time for sorting
            ];
            
            // If they clocked out, add time_out entry AFTER time_in
            if ($row['clock_out'] && $row['clock_out'] != '0000-00-00 00:00:00') {
                $logs[] = [
                    'employee_id' => $row['employee_id'],
                    'employee_name' => $row['employee_name'],
                    'department' => $row['department'],
                    'action' => 'time_out',
                    'timestamp' => $row['clock_out'],
                    'method' => 'fingerprint',
                    'sort_time' => strtotime($row['updated_at']) // Use server time for sorting
                ];
            }
        }
    }
    
    // Sort by actual server timestamp descending (most recent action first)
    usort($logs, function($a, $b) {
        return $b['sort_time'] - $a['sort_time'];
    });
    
    // Remove sort_time from output
    foreach ($logs as &$log) {
        unset($log['sort_time']);
    }
    
    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'count' => count($logs),
        'debug' => [
            'query_date' => date('Y-m-d'),
            'server_time' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>