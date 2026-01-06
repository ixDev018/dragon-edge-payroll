<?php
// bio.php - TRUE Biometric Attendance Kiosk (Tap & Go)
require 'db_connection.php';
date_default_timezone_set('Asia/Manila');

// API: Get today's attendance (for live table updates)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_today') {
    header('Content-Type: application/json');
    
    $logs = [];
    $query = "
    SELECT 
        e.employee_id,
        CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
        e.department,
        al.clock_in,
        al.clock_out,
        al.status,
        al.created_at,
        al.id
    FROM attendance_logs al
    JOIN employees e ON e.id = al.employee_id 
    WHERE al.attendance_date = CURDATE()
    ORDER BY al.created_at DESC
    LIMIT 20
    ";
    
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Determine action based on clock_in and clock_out
            if ($row['clock_in'] && !$row['clock_out']) {
                $action = 'time_in';
                $timestamp = $row['clock_in'];
            } else if ($row['clock_out']) {
                $action = 'time_out';
                $timestamp = $row['clock_out'];
            } else {
                continue; // Skip incomplete records
            }
            
            $logs[] = [
                'employee_id' => $row['employee_id'],
                'employee_name' => $row['employee_name'],
                'department' => $row['department'],
                'action' => $action,
                'timestamp' => $timestamp,
                'method' => 'fingerprint',
                'id' => $row['id']
            ];
        }
    }
    
    echo json_encode(['success' => true, 'logs' => $logs]);
    exit;
}

// API: Get latest attendance scan (for real-time notifications)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_latest') {
    header('Content-Type: application/json');
    
    $last_check = $_GET['last_check'] ?? date('Y-m-d H:i:s', strtotime('-10 seconds'));
    
    $stmt = $conn->prepare("
    SELECT 
        al.id,
        e.employee_id,
        CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
        e.department,
        al.clock_in,
        al.clock_out,
        al.updated_at
    FROM attendance_logs al
    JOIN employees e ON e.id = al.employee_id
    WHERE al.attendance_date = CURDATE()
      AND (al.updated_at > ? OR al.created_at > ?)
    ORDER BY GREATEST(COALESCE(al.updated_at, '1970-01-01'), COALESCE(al.created_at, '1970-01-01')) DESC
    LIMIT 1
    ");
    
    $stmt->bind_param("ss", $last_check, $last_check);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Determine if this is time_in or time_out
        $action = 'time_in';
        $timestamp = $row['clock_in'];
        
        // If clock_out exists and was updated recently, it's a time_out
        if ($row['clock_out'] && $row['updated_at'] > $last_check) {
            $action = 'time_out';
            $timestamp = $row['clock_out'];
        }
        
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
            ]
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// Get initial logs for page load
$logs = [];
$query = "
SELECT 
    e.employee_id,
    CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
    e.department,
    al.clock_in,
    al.clock_out,
    al.status,
    al.created_at,
    al.id
FROM attendance_logs al
JOIN employees e ON e.id = al.employee_id 
WHERE al.attendance_date = CURDATE()
ORDER BY al.created_at DESC
LIMIT 20
";

$result = $conn->query($query);
$last_check_time = date('Y-m-d H:i:s');

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Determine action based on clock_in and clock_out
        if ($row['clock_in'] && !$row['clock_out']) {
            $action = 'time_in';
            $timestamp = $row['clock_in'];
        } else if ($row['clock_out']) {
            $action = 'time_out';
            $timestamp = $row['clock_out'];
        } else {
            continue;
        }
        
        $logs[] = [
            'employee_id' => $row['employee_id'],
            'employee_name' => $row['employee_name'],
            'department' => $row['department'],
            'action' => $action,
            'timestamp' => $timestamp,
            'method' => 'fingerprint',
            'id' => $row['id']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Biometric Attendance Kiosk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;900&display=swap');
        
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --bg-light: #f8fafc;
            --bg-dark: #0f172a;
            --card-light: #ffffff;
            --card-dark: #1e293b;
            --text-light: #1e293b;
            --text-dark: #f1f5f9;
        }
        
        [data-theme="dark"] {
            --bg-light: var(--bg-dark);
            --card-light: var(--card-dark);
            --text-light: var(--text-dark);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-light);
            color: var(--text-light);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            opacity: 0.1;
            pointer-events: none;
        }
        
        .bg-circle {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            animation: float 20s infinite ease-in-out;
        }
        
        .bg-circle:nth-child(1) {
            width: 300px;
            height: 300px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .bg-circle:nth-child(2) {
            width: 200px;
            height: 200px;
            top: 60%;
            right: 10%;
            animation-delay: 5s;
        }
        
        .bg-circle:nth-child(3) {
            width: 150px;
            height: 150px;
            bottom: 10%;
            left: 50%;
            animation-delay: 10s;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }
        
        /* Top Bar */
        .top-bar {
            position: relative;
            z-index: 10;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--card-light);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .logo {
            font-size: 24px;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .theme-toggle {
            background: var(--card-light);
            border: 2px solid #e2e8f0;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 20px;
        }
        
        .theme-toggle:hover {
            transform: scale(1.1);
            border-color: var(--primary);
        }
        
        /* Main Kiosk */
        .kiosk-container {
            position: relative;
            z-index: 1;
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 40px;
        }
        
        /* Hero Section */
        .hero-section {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .hero-title {
            font-size: 48px;
            font-weight: 900;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .hero-subtitle {
            font-size: 20px;
            color: #64748b;
            font-weight: 400;
        }
        
        /* Scanner Display */
        .scanner-display {
            background: var(--card-light);
            border-radius: 30px;
            padding: 60px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            margin-bottom: 50px;
            position: relative;
            overflow: hidden;
        }
        
        .scanner-display::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent 30%, rgba(102, 126, 234, 0.1) 50%, transparent 70%);
            animation: shine 3s infinite;
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .fingerprint-icon {
            width: 200px;
            height: 200px;
            margin: 0 auto 30px;
            position: relative;
        }
        
        .fingerprint-icon i {
            font-size: 180px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.05); }
        }
        
        .scanner-status {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text-light);
        }
        
        .scanner-instruction {
            font-size: 18px;
            color: #64748b;
        }
        
        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #10b981;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            margin-top: 20px;
        }
        
        .live-dot {
            width: 10px;
            height: 10px;
            background: white;
            border-radius: 50%;
            animation: blink 1.5s infinite;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        /* Success Toast */
        .toast-notification {
            position: fixed;
            top: 100px;
            left: 50%;
            transform: translateX(-50%) translateY(-150%);
            background: white;
            padding: 30px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            z-index: 1000;
            min-width: 400px;
            text-align: center;
            transition: transform 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        .toast-notification.show {
            transform: translateX(-50%) translateY(0);
        }
        
        .toast-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
        }
        
        .toast-icon.time-in {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .toast-icon.time-out {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .toast-icon.time-out {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

/* ⬇️ ADD THIS: */
.toast-icon.unknown {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    animation: shake 0.5s;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}
        
        .toast-name {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .toast-action {
            font-size: 18px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .toast-time {
            font-size: 14px;
            color: #94a3b8;
            margin-top: 10px;
        }
        
        /* Attendance Table */
        .attendance-section {
            background: var(--card-light);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-light);
        }
        
        .table-wrapper {
            overflow-x: auto;
        }
        
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .attendance-table thead th {
            background: #f1f5f9;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 14px;
            text-transform: uppercase;
            border-bottom: 2px solid #e2e8f0;
        }
        
        [data-theme="dark"] .attendance-table thead th {
            background: #0f172a;
            color: #94a3b8;
        }
        
        .attendance-table tbody td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            color: var(--text-light);
        }
        
        [data-theme="dark"] .attendance-table tbody td {
            border-bottom-color: #334155;
        }
        
        .attendance-table tbody tr {
            transition: background 0.2s;
        }
        
        .attendance-table tbody tr:hover {
            background: #f8fafc;
        }
        
        [data-theme="dark"] .attendance-table tbody tr:hover {
            background: #1e293b;
        }
        
        .employee-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .employee-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
        }
        
        .employee-info {
            flex: 1;
        }
        
        .employee-name {
            font-weight: 600;
            color: var(--text-light);
        }
        
        .employee-id {
            font-size: 12px;
            color: #94a3b8;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge.time-in {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge.time-out {
            background: #fed7aa;
            color: #92400e;
        }
        
        .badge.fingerprint {
            background: #e0e7ff;
            color: #3730a3;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        /* New Row Animation */
        @keyframes slideInRow {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .new-row {
            animation: slideInRow 0.5s ease-out;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .kiosk-container {
                padding: 0 20px;
            }
            
            .hero-title {
                font-size: 32px;
            }
            
            .scanner-display {
                padding: 40px 20px;
            }
            
            .fingerprint-icon i {
                font-size: 120px;
            }
            
            .toast-notification {
                min-width: 90%;
            }
        }
    </style>
</head>
<body>

<!-- Animated Background -->
<div class="bg-animation">
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
</div>

<!-- Top Bar -->
<div class="top-bar">
    <div class="logo">
        <i class="fas fa-fingerprint"></i>
        Dragon Edge
    </div>
    <button class="theme-toggle" onclick="toggleTheme()">
        <i class="fas fa-moon" id="themeIcon"></i>
    </button>
</div>

<!-- Main Kiosk -->
<div class="kiosk-container">
    <!-- Hero Section -->
    <div class="hero-section">
        <h1 class="hero-title">Biometric Attendance</h1>
        <p class="hero-subtitle">Tap your finger to clock in or out</p>
    </div>
    
    <!-- Scanner Display -->
    <div class="scanner-display">
        <div class="fingerprint-icon">
            <i class="fas fa-fingerprint"></i>
        </div>
        <div class="scanner-status">Place Your Finger</div>
        <div class="scanner-instruction">Waiting for fingerprint scan...</div>
        <div class="live-indicator">
            <span class="live-dot"></span>
            LIVE
        </div>
    </div>
    
    <!-- Attendance Table -->
    <div class="attendance-section">
        <div class="section-header">
            <h2 class="section-title">Today's Attendance</h2>
            <span class="badge fingerprint">
                <i class="fas fa-calendar-day"></i>
                <?php echo date('F d, Y'); ?>
            </span>
        </div>
        
        <div class="table-wrapper">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Action</th>
                        <th>Time</th>
                        <th>Method</th>
                    </tr>
                </thead>
                <tbody id="attendanceTableBody">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <div>No attendance records yet today</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <div class="employee-cell">
                                        <div class="employee-avatar">
                                            <?php echo strtoupper(substr($log['employee_name'], 0, 1)); ?>
                                        </div>
                                        <div class="employee-info">
                                            <div class="employee-name"><?php echo htmlspecialchars($log['employee_name']); ?></div>
                                            <div class="employee-id">ID: <?php echo htmlspecialchars($log['employee_id']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($log['department'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge <?php echo $log['action']; ?>">
                                        <i class="fas fa-<?php echo $log['action'] === 'time_in' ? 'sign-in-alt' : 'sign-out-alt'; ?>"></i>
                                        <?php echo str_replace('_', ' ', $log['action']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('g:i A', strtotime($log['timestamp'])); ?></td>
                                <td>
                                    <span class="badge fingerprint">
                                        <i class="fas fa-fingerprint"></i>
                                        <?php echo ucfirst($log['method']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="toast-notification" id="toast">
    <div class="toast-icon time-in" id="toastIcon">
        <i class="fas fa-check" id="toastIconSymbol"></i>
    </div>
    <div class="toast-name" id="toastName">John Doe</div>
    <div class="toast-action" id="toastAction">Time In</div>
    <div class="toast-time" id="toastTime">9:00 AM</div>
</div>

<script>
// ═══════════════════════════════════════════════════════════
//  REAL-TIME ATTENDANCE MONITORING (COMPLETE FIX)
// ═══════════════════════════════════════════════════════════

let lastRawId = 0;
let pollingInterval = null;
let isInitialized = false;
let lastUnknownId = 0;

// Start real-time polling
function startPolling() {
    console.log('🚀 Starting polling system...');
    
    // Initialize lastRawId properly
    initializeLastId().then(() => {
        // Initialize unknown scan tracking
        fetch('get_unknown_scans.php?last_id=0')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.scan) {
                    lastUnknownId = data.scan.id;
                    console.log('✅ Initialized lastUnknownId:', lastUnknownId);
                }
            })
            .catch(err => {
                console.log('ℹ️ No unknown scans yet');
                lastUnknownId = 0;
            });
        
        // Start polling every 1 second
        pollingInterval = setInterval(() => {
            checkForNewScans();
            checkForUnknownScans();
        }, 1000);
        
        console.log('✅ Polling active - checking every 1 second');
    });
}

// Initialize the last ID correctly
async function initializeLastId() {
    try {
        // Get the most recent scan to start tracking from
        const response = await fetch('attendance_poll_api.php?last_id=0');
        const data = await response.json();
        
        if (data.success && data.scan) {
            lastRawId = data.scan.id;
            console.log('✅ Initialized lastRawId:', lastRawId);
            isInitialized = true;
        } else {
            // No scans exist yet, start from 0
            lastRawId = 0;
            console.log('ℹ️ No existing scans, starting from ID 0');
            isInitialized = true;
        }
    } catch (error) {
        console.error('❌ Initialization error:', error);
        // Fallback: start from 0
        lastRawId = 0;
        isInitialized = true;
    }
}

// Check for new attendance scans
async function checkForNewScans() {
    // Don't poll until initialized
    if (!isInitialized) {
        return;
    }
    
    try {
        const url = `attendance_poll_api.php?last_id=${lastRawId}`;
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success && data.scan) {
            console.log('═══════════════════════════════════════');
            console.log('🎯 NEW SCAN DETECTED!');
            console.log('Employee:', data.scan.employee_name);
            console.log('Action:', data.scan.action);
            console.log('Previous ID:', lastRawId, '→ New ID:', data.scan.id);
            console.log('Debug:', data.debug);
            console.log('═══════════════════════════════════════');
            
            // Update tracking ID
            lastRawId = data.scan.id;
            
            // Show toast notification
            displayNewScan(data.scan);
            
            // Wait 500ms before refreshing table (let DB update finish)
            setTimeout(() => {
                refreshAttendanceTable();
            }, 500);
        } else {
            // No new scans (this is normal)
            // Only log every 10 seconds to avoid spam
            if (Date.now() % 10000 < 1000) {
                console.log('⏳ Waiting for scans... (lastRawId:', lastRawId + ')');
            }
        }
    } catch (error) {
        console.error('❌ Polling error:', error);
    }
}

// Check for unknown fingerprint scans
async function checkForUnknownScans() {
    try {
        const url = `get_unknown_scans.php?last_id=${lastUnknownId}`;
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success && data.scan) {
            console.log('⚠️ UNKNOWN FINGERPRINT DETECTED!');
            console.log('Device:', data.scan.device);
            console.log('Time:', data.scan.timestamp);
            
            lastUnknownId = data.scan.id;
            displayUnknownScan(data.scan);
        }
    } catch (error) {
        console.error('❌ Unknown scan check error:', error);
    }
}

// Display unknown fingerprint warning
function displayUnknownScan(scan) {
    const toast = document.getElementById('toast');
    const toastIcon = document.getElementById('toastIcon');
    const toastIconSymbol = document.getElementById('toastIconSymbol');
    const toastName = document.getElementById('toastName');
    const toastAction = document.getElementById('toastAction');
    const toastTime = document.getElementById('toastTime');
    
    toastName.textContent = 'Unknown Fingerprint';
    toastAction.textContent = 'NOT RECOGNIZED';
    toastTime.textContent = formatTime(scan.timestamp);
    
    toastIcon.className = 'toast-icon unknown';
    toastIconSymbol.className = 'fas fa-exclamation-triangle';
    
    toast.classList.add('show');
    playErrorSound();
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 5000);
}

// Play error/warning sound
function playErrorSound() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 400;
        oscillator.type = 'square';
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.5);
    } catch (e) {
        console.log('Audio not supported');
    }
}

// Display new scan with toast notification
function displayNewScan(scan) {
    const toast = document.getElementById('toast');
    const toastIcon = document.getElementById('toastIcon');
    const toastIconSymbol = document.getElementById('toastIconSymbol');
    const toastName = document.getElementById('toastName');
    const toastAction = document.getElementById('toastAction');
    const toastTime = document.getElementById('toastTime');
    
    console.log('📢 Displaying toast:', {
        action: scan.action,
        employee: scan.employee_name,
        time: scan.timestamp
    });
    
    // Set toast content
    toastName.textContent = scan.employee_name;
    toastAction.textContent = scan.action.replace('_', ' ').toUpperCase();
    toastTime.textContent = formatTime(scan.timestamp);
    
    // Set icon and color based on action
    const action = scan.action.toLowerCase().replace('-', '_');
    
    if (action === 'time_in') {
        console.log('🟢 Showing TIME IN toast');
        toastIcon.className = 'toast-icon time-in';
        toastIconSymbol.className = 'fas fa-sign-in-alt';
    } else if (action === 'time_out') {
        console.log('🟠 Showing TIME OUT toast');
        toastIcon.className = 'toast-icon time-out';
        toastIconSymbol.className = 'fas fa-sign-out-alt';
    } else {
        console.warn('⚠️ Unknown action:', scan.action);
        toastIcon.className = 'toast-icon time-in';
        toastIconSymbol.className = 'fas fa-check';
    }
    
    // Show toast with animation
    toast.classList.add('show');
    
    // Play success sound
    playSuccessSound();
    
    // Hide toast after 4 seconds
    setTimeout(() => {
        toast.classList.remove('show');
    }, 4000);
}

// Refresh attendance table
async function refreshAttendanceTable() {
    try {
        const response = await fetch('get_today_attendance.php');
        const data = await response.json();
        
        if (data.success) {
            console.log('📋 Refreshing table with', data.logs.length, 'records');
            updateTable(data.logs);
        } else {
            console.error('❌ Failed to get attendance data:', data);
        }
    } catch (error) {
        console.error('❌ Table refresh error:', error);
    }
}

// Update table with new data
function updateTable(logs) {
    const tbody = document.getElementById('attendanceTableBody');
    
    if (logs.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5">
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <div>No attendance records yet today</div>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = logs.map((log, index) => {
        const actionClass = log.action.replace('_', '-');
        const iconName = log.action === 'time_in' ? 'sign-in-alt' : 'sign-out-alt';
        const actionText = log.action.replace('_', ' ').toUpperCase();
        
        return `
            <tr class="${index === 0 ? 'new-row' : ''}">
                <td>
                    <div class="employee-cell">
                        <div class="employee-avatar">
                            ${escapeHtml(log.employee_name).charAt(0).toUpperCase()}
                        </div>
                        <div class="employee-info">
                            <div class="employee-name">${escapeHtml(log.employee_name)}</div>
                            <div class="employee-id">ID: ${escapeHtml(log.employee_id)}</div>
                        </div>
                    </div>
                </td>
                <td>${escapeHtml(log.department || 'N/A')}</td>
                <td>
                    <span class="badge ${actionClass}">
                        <i class="fas fa-${iconName}"></i>
                        ${actionText}
                    </span>
                </td>
                <td>${formatTime(log.timestamp)}</td>
                <td>
                    <span class="badge fingerprint">
                        <i class="fas fa-fingerprint"></i>
                        ${capitalizeFirst(log.method)}
                    </span>
                </td>
            </tr>
        `;
    }).join('');
}

// Helper: Format timestamp
function formatTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
    });
}

// Helper: Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Helper: Capitalize first letter
function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Helper: Play success sound
function playSuccessSound() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.3);
    } catch (e) {
        console.log('Audio not supported');
    }
}

// Theme toggle
function toggleTheme() {
    const html = document.documentElement;
    const icon = document.getElementById('themeIcon');
    const currentTheme = html.getAttribute('data-theme');
    
    if (currentTheme === 'dark') {
        html.setAttribute('data-theme', 'light');
        icon.className = 'fas fa-moon';
        localStorage.setItem('theme', 'light');
    } else {
        html.setAttribute('data-theme', 'dark');
        icon.className = 'fas fa-sun';
        localStorage.setItem('theme', 'dark');
    }
}

// Load saved theme
function loadTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    const html = document.documentElement;
    const icon = document.getElementById('themeIcon');
    
    html.setAttribute('data-theme', savedTheme);
    icon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
}

// Initialize on page load
window.addEventListener('load', () => {
    console.log('═══════════════════════════════════════');
    console.log('🚀 BIOMETRIC KIOSK INITIALIZING...');
    console.log('═══════════════════════════════════════');
    
    loadTheme();
    startPolling();
    requestWakeLock();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        console.log('🛑 Polling stopped');
    }
});

// Prevent page from sleeping
let wakeLock = null;

async function requestWakeLock() {
    try {
        if ('wakeLock' in navigator) {
            wakeLock = await navigator.wakeLock.request('screen');
            console.log('🔒 Wake lock enabled - screen will stay on');
        }
    } catch (err) {
        console.log('Wake lock not supported or denied');
    }
}

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        requestWakeLock();
    }
});

// Auto-refresh page every 12 hours
setTimeout(() => {
    console.log('🔄 Auto-refresh triggered (12 hours)');
    location.reload();
}, 12 * 60 * 60 * 1000);
</script>

</body>
</html>