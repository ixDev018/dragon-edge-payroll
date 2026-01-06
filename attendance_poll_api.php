<?php
/**
 * Real-time Attendance Polling API
 * Returns latest attendance scan for bio.php kiosk
 * 
 * BULLETPROOF FIX: Determines action by counting scans today
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once 'db_connection.php';

$last_id = (int)($_GET['last_id'] ?? 0);

// Get the very latest attendance record from biometric_attendance_raw
$stmt = $conn->prepare("
    SELECT 
        bar.id,
        bar.fingerprint_id,
        bar.scan_timestamp,
        bar.created_at,
        bar.device_id,
        e.id as employee_db_id,
        e.employee_id,
        CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
        e.department
    FROM biometric_attendance_raw bar
    JOIN employees e ON e.fingerprint_id = bar.fingerprint_id
    WHERE bar.id > ? 
        AND e.is_active = 1
    ORDER BY bar.id DESC
    LIMIT 1
");

$stmt->bind_param("i", $last_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $employee_db_id = $row['employee_db_id'];
    
    // CRITICAL FIX: Wait 500ms for stored procedure to finish (increased from 300ms)
    usleep(500000); // 500ms = 500,000 microseconds
    
    // Count how many scans this employee has made TODAY
    $count_stmt = $conn->prepare("
        SELECT COUNT(*) as scan_count
        FROM biometric_attendance_raw
        WHERE fingerprint_id = ?
        AND DATE(created_at) = CURDATE()
    ");
    $count_stmt->bind_param("i", $row['fingerprint_id']);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result()->fetch_assoc();
    $scan_count = $count_result['scan_count'];
    
    // Get current attendance_logs record
    $check_stmt = $conn->prepare("
        SELECT 
            clock_in, 
            clock_out,
            updated_at
        FROM attendance_logs 
        WHERE employee_id = ? 
        AND DATE(created_at) = CURDATE()
        ORDER BY id DESC
        LIMIT 1
    ");
    $check_stmt->bind_param("i", $employee_db_id);
    $check_stmt->execute();
    $attendance = $check_stmt->get_result()->fetch_assoc();
    
    // BULLETPROOF LOGIC: Odd scan = TIME IN, Even scan = TIME OUT
    if ($scan_count % 2 == 1) {
        // First scan, third scan, fifth scan, etc. → TIME IN
        $action = 'time_in';
        $timestamp = $attendance['clock_in'] ?? $row['created_at'];
    } else {
        // Second scan, fourth scan, sixth scan, etc. → TIME OUT
        $action = 'time_out';
        $timestamp = $attendance['clock_out'] ?? $row['created_at'];
    }
    
    // Fallback: If stored procedure hasn't finished, use raw timestamp
    if (!$timestamp) {
        $timestamp = $row['created_at'];
    }
    
    // Debug info (KEEP THIS to verify the fix works)
    $debug_info = [
        'scan_count_today' => $scan_count,
        'action_logic' => $scan_count % 2 == 1 ? 'odd = time_in' : 'even = time_out',
        'clock_in' => $attendance['clock_in'] ?? null,
        'clock_out' => $attendance['clock_out'] ?? null,
        'timestamp_used' => $timestamp,
        'delay_applied' => '500ms'
    ];
    
    echo json_encode([
        'success' => true,
        'scan' => [
            'id' => $row['id'],
            'employee_id' => $row['employee_id'],
            'employee_name' => $row['employee_name'],
            'department' => $row['department'],
            'action' => $action,
            'timestamp' => $timestamp,
            'method' => 'fingerprint'
        ],
        'debug' => $debug_info
    ]);
} else {
    echo json_encode(['success' => false]);
}
?>