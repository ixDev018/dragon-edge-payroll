<?php
/**
 * Biometric REST API
 * Handles fingerprint enrollment and attendance from ESP32
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connection.php';

// =====================================================
// HELPER FUNCTIONS
// =====================================================

function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit;
}

function get_employee_by_fingerprint($conn, $fingerprint_id) {
    $stmt = $conn->prepare("
        SELECT e.*, s.shift_name, s.start_time, s.end_time
        FROM employees e
        LEFT JOIN shifts s ON e.shift_id = s.id
        WHERE e.fingerprint_id = ? AND e.is_active = 1
    ");
    $stmt->bind_param("i", $fingerprint_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// =====================================================
// ROUTES
// =====================================================

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['endpoint'] ?? '';

// GET /biometric_api.php?endpoint=employees
if ($method === 'GET' && $path === 'employees') {
    $stmt = $conn->prepare("
        SELECT id, employee_id, first_name, last_name, email, 
               department, position, fingerprint_id, is_active
        FROM employees
        WHERE is_active = 1
        ORDER BY first_name ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    
    json_response(['success' => true, 'employees' => $employees]);
}

// GET /biometric_api.php?endpoint=employee&fingerprint_id=123
if ($method === 'GET' && $path === 'employee') {
    $fingerprint_id = (int)($_GET['fingerprint_id'] ?? 0);
    
    if (!$fingerprint_id) {
        json_response(['success' => false, 'message' => 'fingerprint_id required'], 400);
    }
    
    $employee = get_employee_by_fingerprint($conn, $fingerprint_id);
    
    if ($employee) {
        json_response(['success' => true, 'employee' => $employee]);
    } else {
        json_response(['success' => false, 'message' => 'Employee not found'], 404);
    }
}

// POST /biometric_api.php?endpoint=enroll
if ($method === 'POST' && $path === 'enroll') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $employee_id = (int)($data['employee_id'] ?? 0);
    $fingerprint_id = (int)($data['fingerprint_id'] ?? 0);
    $enrolled_by = $data['enrolled_by'] ?? 'admin';
    $device_id = $data['device_id'] ?? null;
    
    if (!$employee_id || !$fingerprint_id) {
        json_response([
            'success' => false, 
            'message' => 'employee_id and fingerprint_id are required'
        ], 400);
    }
    
    // Check if employee exists
    $stmt = $conn->prepare("SELECT id, first_name, last_name FROM employees WHERE id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $employee = $stmt->get_result()->fetch_assoc();
    
    if (!$employee) {
        json_response(['success' => false, 'message' => 'Employee not found'], 404);
    }
    
    // Check if fingerprint_id is already taken
    $stmt = $conn->prepare("SELECT id, first_name, last_name FROM employees WHERE fingerprint_id = ? AND id != ?");
    $stmt->bind_param("ii", $fingerprint_id, $employee_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    
    if ($existing) {
        json_response([
            'success' => false, 
            'message' => 'Fingerprint already assigned to ' . $existing['first_name'] . ' ' . $existing['last_name']
        ], 409);
    }
    
    // Update employee with fingerprint_id
    $stmt = $conn->prepare("UPDATE employees SET fingerprint_id = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $fingerprint_id, $employee_id);
    
    if (!$stmt->execute()) {
        json_response(['success' => false, 'message' => 'Failed to update employee'], 500);
    }
    
    // Record enrollment
    $enrollment_date = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("
        INSERT INTO biometric_enrollments 
        (employee_id, fingerprint_id, enrolled_by, device_id, enrollment_date)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisss", $employee_id, $fingerprint_id, $enrolled_by, $device_id, $enrollment_date);
    $stmt->execute();
    
    json_response([
        'success' => true,
        'message' => 'Employee enrolled successfully',
        'employee' => [
            'id' => $employee_id,
            'name' => $employee['first_name'] . ' ' . $employee['last_name'],
            'fingerprint_id' => $fingerprint_id
        ]
    ]);
}

// POST /biometric_api.php?endpoint=attendance
if ($method === 'POST' && $path === 'attendance') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $fingerprint_id = (int)($data['fingerprint_id'] ?? 0);
    $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
    $device_id = $data['device_id'] ?? 'unknown';
    
    if (!$fingerprint_id) {
        json_response(['success' => false, 'message' => 'fingerprint_id required'], 400);
    }
    
    // Get employee
    $employee = get_employee_by_fingerprint($conn, $fingerprint_id);
    
    if (!$employee) {
        json_response(['success' => false, 'message' => 'Employee not found'], 404);
    }
    
    $employee_id = $employee['id'];
    
    // Save raw attendance
    $stmt = $conn->prepare("
        INSERT INTO biometric_attendance_raw 
        (fingerprint_id, device_id, scan_timestamp, raw_data)
        VALUES (?, ?, ?, ?)
    ");
    $raw_json = json_encode($data);
    $stmt->bind_param("isss", $fingerprint_id, $device_id, $timestamp, $raw_json);
    $stmt->execute();
    
    // Process attendance
    try {
        $stmt = $conn->prepare("CALL process_biometric_attendance(?, ?, ?)");
        $stmt->bind_param("iss", $fingerprint_id, $device_id, $timestamp);
        
        if ($stmt->execute()) {
            // Get the attendance record
            $attendance_date = date('Y-m-d', strtotime($timestamp));
            $stmt = $conn->prepare("
                SELECT * FROM attendance_logs 
                WHERE employee_id = ? AND attendance_date = ?
            ");
            $stmt->bind_param("is", $employee_id, $attendance_date);
            $stmt->execute();
            $attendance = $stmt->get_result()->fetch_assoc();
            
            json_response([
                'success' => true,
                'message' => 'Attendance recorded',
                'employee' => [
                    'id' => $employee_id,
                    'name' => $employee['first_name'] . ' ' . $employee['last_name'],
                    'department' => $employee['department']
                ],
                'attendance' => $attendance
            ]);
        } else {
            json_response(['success' => false, 'message' => 'Failed to process attendance'], 500);
        }
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// GET /biometric_api.php?endpoint=attendance_today
if ($method === 'GET' && $path === 'attendance_today') {
    $stmt = $conn->query("SELECT * FROM v_biometric_attendance_today");
    
    $attendance = [];
    while ($row = $stmt->fetch_assoc()) {
        $attendance[] = $row;
    }
    
    json_response(['success' => true, 'attendance' => $attendance]);
}

// GET /biometric_api.php?endpoint=devices
if ($method === 'GET' && $path === 'devices') {
    $stmt = $conn->query("
        SELECT * FROM biometric_devices 
        ORDER BY last_seen DESC
    ");
    
    $devices = [];
    while ($row = $stmt->fetch_assoc()) {
        $devices[] = $row;
    }
    
    json_response(['success' => true, 'devices' => $devices]);
}

// POST /biometric_api.php?endpoint=device_status
if ($method === 'POST' && $path === 'device_status') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $device_id = $data['device_id'] ?? null;
    $ip_address = $data['ip_address'] ?? null;
    $status = $data['status'] ?? 'online';
    
    if (!$device_id) {
        json_response(['success' => false, 'message' => 'device_id required'], 400);
    }
    
    $stmt = $conn->prepare("
        INSERT INTO biometric_devices 
        (device_id, device_name, ip_address, last_seen, status, is_active)
        VALUES (?, ?, ?, NOW(), ?, 1)
        ON DUPLICATE KEY UPDATE
            ip_address = VALUES(ip_address),
            last_seen = NOW(),
            status = VALUES(status)
    ");
    
    $device_name = $device_id;
    $stmt->bind_param("ssss", $device_id, $device_name, $ip_address, $status);
    
    if ($stmt->execute()) {
        json_response(['success' => true, 'message' => 'Device status updated']);
    } else {
        json_response(['success' => false, 'message' => 'Failed to update device status'], 500);
    }
}

// Default 404
json_response([
    'success' => false,
    'message' => 'Endpoint not found',
    'available_endpoints' => [
        'GET /biometric_api.php?endpoint=employees',
        'GET /biometric_api.php?endpoint=employee&fingerprint_id=X',
        'POST /biometric_api.php?endpoint=enroll',
        'POST /biometric_api.php?endpoint=attendance',
        'GET /biometric_api.php?endpoint=attendance_today',
        'GET /biometric_api.php?endpoint=devices',
        'POST /biometric_api.php?endpoint=device_status'
    ]
], 404);