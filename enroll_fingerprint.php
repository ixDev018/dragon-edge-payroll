<?php
session_start();
include 'db_connection.php';
include 'sidebar.php';

// --- PHP LOGIC: Fetch Data & Handle Manual Assignment ---

// Fetch employees
$employees = [];
$sql = "SELECT id, employee_id, first_name, last_name, email, department, position, fingerprint_id 
        FROM employees WHERE is_active = 1 ORDER BY first_name ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}

// Get next available fingerprint ID
$next_fp_id = 1;
$stmt = $conn->query("SELECT MAX(fingerprint_id) as max_id FROM employees WHERE fingerprint_id IS NOT NULL");
if ($row = $stmt->fetch_assoc()) {
    $next_fp_id = ($row['max_id'] ?? 0) + 1;
}

// Handle manual assignment (Quick Link)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_existing'])) {
    $employee_id = (int)$_POST['employee_id'];
    $fingerprint_id = (int)$_POST['fingerprint_id'];
    
    $stmt = $conn->prepare("UPDATE employees SET fingerprint_id = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $fingerprint_id, $employee_id);
    
    if ($stmt->execute()) {
        $enrolled_by = $_SESSION['user_name'] ?? 'admin';
        $stmt = $conn->prepare("
            INSERT INTO biometric_enrollments 
            (employee_id, fingerprint_id, enrolled_by, enrollment_date)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("iis", $employee_id, $fingerprint_id, $enrolled_by);
        $stmt->execute();
        
        header("Location: enroll_fingerprint.php?success=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Fingerprint Enrollment | Dragon Edge Group</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="styles.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 280px;
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center; 
            min-height: calc(100vh - 80px);
        }

        .header-container, .card {
            width: 100%;
            max-width: 1200px;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 26px;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0;
        }
        .title i {
            color: #d90429;
        }

        .enhanced-badge {
            background: linear-gradient(135deg, #d90429, #b0021e);
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: 15px;
            box-shadow: 0 2px 8px rgba(217, 4, 41, 0.3);
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
            border: 1px solid #eee;
            border-top: 4px solid #d90429;
            margin-bottom: 30px;
        }

        .btn-primary {
            background-color: #d90429;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(217, 4, 41, 0.3);
            font-size: 13px;
        }
        .btn-primary:hover:not(:disabled) {
            background-color: #b0021e;
            transform: translateY(-2px);
        }
        .btn-primary:disabled {
            background-color: #fab1b1;
            cursor: not-allowed;
            box-shadow: none;
        }

        .btn-black {
            background-color: #212529;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-black:hover {
            background-color: #000;
        }

        .live-badge {
            background: #212529;
            color: white;
            padding: 6px 15px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #333;
        }
        
        .live-dot {
            width: 8px; 
            height: 8px; 
            background: #d90429;
            border-radius: 50%; 
            display: inline-block;
            box-shadow: 0 0 8px #d90429;
            animation: pulse-red 2s infinite;
        }
        
        .live-badge.disconnected .live-dot {
            background: #6c757d;
            box-shadow: none;
            animation: none;
        }

        @keyframes pulse-red {
            0% { opacity: 1; box-shadow: 0 0 0 0 rgba(217, 4, 41, 0.7); }
            70% { opacity: 1; box-shadow: 0 0 0 6px rgba(217, 4, 41, 0); }
            100% { opacity: 1; box-shadow: 0 0 0 0 rgba(217, 4, 41, 0); }
        }

        .employees-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .employees-table thead th {
            background-color: #f8f9fa;
            color: #444;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 15px;
            border-bottom: 2px solid #e9ecef;
            text-align: left;
        }
        
        .employees-table tbody td {
            padding: 15px;
            border-bottom: 1px solid #f1f1f1;
            color: #333;
            vertical-align: middle;
        }
        
        .employees-table tbody tr:hover {
            background-color: #fafafa;
        }

        .employee-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #212529;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            margin-right: 12px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-badge.enrolled {
            background: #e6f9ed;
            color: #2ecc71;
            border: 1px solid #d5f5e0;
        }
        .status-badge.pending {
            background: #fff5f5;
            color: #d90429;
            border: 1px solid #ffe3e3;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(2px);
        }
        .modal.active { display: flex; }
        
        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            text-align: center;
            border-top: 5px solid #d90429;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .status-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
        }
        
        .status-icon.waiting { background: #6c757d; }
        .status-icon.scanning { background: #d90429; animation: pulse-red 1.5s infinite; }
        .status-icon.processing { background: #212529; }
        .status-icon.verifying { background: #f39c12; animation: pulse-orange 1.5s infinite; }
        .status-icon.verify-failed { background: #e74c3c; animation: shake-continuous 0.5s infinite; }
        .status-icon.success { background: #2ecc71; }
        .status-icon.error { background: #e74c3c; }
        .status-icon.retry { background: #f39c12; animation: shake 0.5s; }
        .status-icon.ready { background: #3498db; animation: pulse-blue 1.5s infinite; }

        @keyframes pulse-orange {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }

        @keyframes pulse-blue {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        @keyframes shake-continuous {
            0%, 100% { transform: translateX(0) rotate(0deg); }
            25% { transform: translateX(-3px) rotate(-2deg); }
            50% { transform: translateX(3px) rotate(2deg); }
            75% { transform: translateX(-3px) rotate(-2deg); }
        }

        .progress-bar {
            width: 100%; 
            height: 8px; 
            background: #eee; 
            border-radius: 10px; 
            overflow: hidden; 
            margin: 20px 0;
            position: relative;
        }
        .progress-bar-fill {
            height: 100%; 
            background: linear-gradient(90deg, #d90429, #ff1744);
            transition: width 0.4s ease;
            box-shadow: 0 0 10px rgba(217, 4, 41, 0.5);
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin: 25px 0 15px;
            padding: 0 10px;
        }
        
        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #999;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 8px;
            transition: all 0.3s;
        }
        
        .step.active .step-circle {
            background: #d90429;
            color: white;
            box-shadow: 0 0 15px rgba(217, 4, 41, 0.5);
            transform: scale(1.15);
        }
        
        .step.completed .step-circle {
            background: #2ecc71;
            color: white;
        }
        
        .step.retry .step-circle {
            background: #f39c12;
            color: white;
            animation: shake 0.5s;
        }
        
        .step-label {
            font-size: 11px;
            color: #666;
            font-weight: 600;
        }
        
        .step.active .step-label {
            color: #d90429;
            font-weight: 700;
        }
        
        .step.completed .step-label {
            color: #2ecc71;
        }
        
        .step.retry .step-label {
            color: #f39c12;
        }

        /* Instruction box for finger placement */
        .instruction-box {
            background: #f8f9fa;
            border-left: 4px solid #d90429;
            padding: 15px 20px;
            border-radius: 6px;
            margin: 20px 0;
            text-align: left;
        }
        
        .instruction-box h4 {
            margin: 0 0 10px 0;
            color: #212529;
            font-size: 14px;
            font-weight: 700;
        }
        
        .instruction-box p {
            margin: 5px 0;
            color: #666;
            font-size: 13px;
            line-height: 1.6;
        }

        .modal-close {
            position: absolute; 
            top: 15px; 
            right: 15px; 
            background: transparent; 
            border: none; 
            font-size: 24px; 
            cursor: pointer; 
            color: #999;
        }
        .modal-close:hover { color: #d90429; }
        
        .toast {
            position: fixed; 
            bottom: 30px; 
            right: 30px;
            background: #212529; 
            color: white;
            padding: 15px 25px; 
            border-radius: 8px;
            display: none; 
            align-items: center; 
            gap: 15px;
            z-index: 2000; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            border-left: 4px solid #d90429;
        }
        .toast.show { display: flex; animation: slideIn 0.3s; }
        @keyframes slideIn { 
            from { transform: translateX(100px); opacity: 0; } 
            to { transform: translateX(0); opacity: 1; } 
        }

        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #d90429;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .info-box i {
            color: #d90429;
            font-size: 20px;
            margin-top: 2px;
        }
        
        .info-box-content {
            flex: 1;
        }
        
        .info-box h4 {
            margin: 0 0 8px 0;
            color: #212529;
            font-size: 15px;
        }
        
        .info-box p {
            margin: 0;
            color: #666;
            font-size: 13px;
            line-height: 1.6;
        }

        /* Verification warning box */
        .verification-warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            text-align: left;
        }
        
        .verification-warning h4 {
            margin: 0 0 10px 0;
            color: #856404;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .verification-warning p {
            margin: 5px 0;
            color: #856404;
            font-size: 13px;
            line-height: 1.5;
        }

        .verification-warning ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
            color: #856404;
            font-size: 12px;
        }

        .verification-warning ul li {
            margin: 5px 0;
        }

        /* Retry counter */
        .retry-counter {
            display: inline-block;
            background: #f39c12;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            margin-left: 10px;
        }

        .verify-counter {
            display: inline-block;
            background: #e74c3c;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            margin-left: 10px;
            animation: pulse-red 1s infinite;
        }

    </style>
</head>
<body>

<div class="main-content">
    
    <div class="header-container header-row">
        <div style="display: flex; align-items: center;">
            <div class="title">
                <i class="fas fa-fingerprint"></i>
                Fingerprint Enrollment
            </div>
            <span class="enhanced-badge">
                <i class="fas fa-layer-group"></i>
                4-Angle Enhanced + Verification
            </span>
        </div>
        
        <div class="live-badge" id="connectionStatus">
            <span class="live-dot"></span>
            <span id="statusText">DEVICE ACTIVE</span>
        </div>
    </div>

    <div class="info-box">
        <i class="fas fa-info-circle"></i>
        <div class="info-box-content">
            <h4>Enhanced Multi-Angle Enrollment with Quality Verification</h4>
            <p>Our enrollment system captures <strong>4 different angles</strong> (center, upper, left-tilt, right-tilt) with <strong>quality checks</strong> and <strong>automatic retries</strong> for poor scans. After enrollment, the system performs a <strong>verification test</strong> to ensure high-quality recognition before saving.</p>
        </div>
    </div>
    
    <div class="card">
        <h3 style="margin-top:0; color: #212529; font-size: 18px; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px;">
            <i class="fas fa-link" style="color: #d90429; margin-right: 8px;"></i> 
            Quick Link Existing Fingerprint ID
        </h3>
        
        <form method="POST" action="" style="display: flex; gap: 15px; align-items: flex-end;">
            <div style="flex: 1;">
                <label style="display: block; margin-bottom: 8px; font-size: 13px; color: #555; font-weight: 600;">Select Employee</label>
                <select name="employee_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; background: #fafafa;">
                    <option value="">-- Choose Employee --</option>
                    <?php foreach ($employees as $emp): ?>
                        <?php if (!$emp['fingerprint_id']): ?>
                            <option value="<?php echo $emp['id']; ?>">
                                <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="width: 150px;">
                <label style="display: block; margin-bottom: 8px; font-size: 13px; color: #555; font-weight: 600;">Stored FP ID</label>
                <input type="number" name="fingerprint_id" min="1" placeholder="ID #" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; background: #fafafa;">
            </div>
            <button type="submit" name="assign_existing" class="btn-black">
                Link ID
            </button>
        </form>
    </div>
    
    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee;">
            <h3 style="margin: 0; font-size: 18px; color: #212529;">Enrollment List</h3>
            <span style="font-size: 13px; color: #777;">
                Next Auto ID: <strong style="color: #d90429;"><?php echo $next_fp_id; ?></strong>
            </span>
        </div>
        
        <table class="employees-table">
            <thead>
                <tr>
                    <th>Employee Details</th>
                    <th>Department</th>
                    <th>Fingerprint Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center;">
                                <div class="employee-avatar">
                                    <?php echo strtoupper(substr($emp['first_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #212529;"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></div>
                                    <div style="font-size: 12px; color: #888;">ID: <?php echo htmlspecialchars($emp['employee_id']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($emp['department'] ?? '-'); ?></td>
                        <td>
                            <?php if ($emp['fingerprint_id']): ?>
                                <span class="status-badge enrolled">
                                    <i class="fas fa-check"></i> Linked (ID: <?php echo $emp['fingerprint_id']; ?>)
                                </span>
                            <?php else: ?>
                                <span class="status-badge pending">
                                    <i class="fas fa-times"></i> Not Enrolled
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <?php if (!$emp['fingerprint_id']): ?>
                                <button class="btn-primary" id="enrollBtn<?php echo $emp['id']; ?>" onclick='startEnrollment(<?php echo json_encode([
                                    "id" => $emp["id"],
                                    "name" => $emp["first_name"] . " " . $emp["last_name"],
                                    "emp_id" => $emp["employee_id"]
                                ]); ?>)'>
                                    <i class="fas fa-fingerprint"></i> Enroll (4-Angle + Verify)
                                </button>
                            <?php else: ?>
                                <button class="btn-black" disabled style="opacity: 0.3; cursor: default;">
                                    Done
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal" id="enrollmentModal">
    <div class="modal-content">
        <button class="modal-close" onclick="cancelEnrollment()">×</button>
        <div id="enrollmentStatus"></div>
    </div>
</div>

<div class="toast" id="toast">
    <i class="fas fa-info-circle"></i>
    <div>
        <strong id="toastTitle" style="display:block; margin-bottom: 2px;">Title</strong>
        <span id="toastMessage" style="font-size: 13px; opacity: 0.9;">Message</span>
    </div>
</div>

<script>
// ═══════════════════════════════════════════════════════════════
//  SYNCHRONIZED WITH ARDUINO 4-ANGLE ENROLLMENT PROCESS
// ═══════════════════════════════════════════════════════════════

let pollingInterval = null;
let currentEmployee = null;
let enrollmentInProgress = false;
let currentStep = 0; // 0=waiting, 1-4=scans, 5=verification
let retryCount = 0;

// Step instructions matching Arduino code
const stepInstructions = {
    1: {
        title: 'SCAN 1/4: Center Position',
        desc: 'Place finger CENTERED - PRESS FIRMLY',
        detail: 'Cover 80% of sensor surface with firm pressure'
    },
    2: {
        title: 'SCAN 2/4: Upper Position',
        desc: 'Place SAME finger HIGHER - LIGHTER PRESS',
        detail: 'Shift finger 5mm upward, use less pressure'
    },
    3: {
        title: 'SCAN 3/4: Left Edge + Rotation',
        desc: 'Place finger touching LEFT EDGE - ROTATE 15° LEFT',
        detail: 'Finger touches left side of sensor, tilted left'
    },
    4: {
        title: 'SCAN 4/4: Right Edge + Rotation (FINAL)',
        desc: 'Place finger touching RIGHT EDGE - ROTATE 15° RIGHT',
        detail: 'Finger touches right side, tilted right - FINAL SCAN'
    }
};

function updateConnectionStatus(connected) {
    const badge = document.getElementById('connectionStatus');
    const text = document.getElementById('statusText');
    const dot = badge.querySelector('.live-dot');
    
    if (connected) {
        badge.classList.remove('disconnected');
        text.textContent = 'DEVICE ACTIVE';
        dot.style.background = '#d90429'; 
    } else {
        badge.classList.add('disconnected');
        text.textContent = 'OFFLINE';
        dot.style.background = '#666';
    }
}

function startPolling() {
    if (pollingInterval) return;
    
    pollingInterval = setInterval(async () => {
        if (!enrollmentInProgress) return;
        
        try {
            const response = await fetch('enrollment_status_api.php?employee_id=' + currentEmployee.id);
            const data = await response.json();
            
            if (data.status) {
                handleEnrollmentUpdate(data);
            }
        } catch (err) {
            console.error('Polling error:', err);
        }
    }, 500);
}

function stopPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
}

function handleEnrollmentUpdate(data) {
    const status = data.status;
    const message = data.message || '';
    const msgLower = message.toLowerCase();

    // Parse step and retry info from message
    if (msgLower.includes('1/4') || msgLower.includes('step 1')) currentStep = 1;
    else if (msgLower.includes('2/4') || msgLower.includes('step 2')) currentStep = 2;
    else if (msgLower.includes('3/4') || msgLower.includes('step 3')) currentStep = 3;
    else if (msgLower.includes('4/4') || msgLower.includes('step 4') || msgLower.includes('final')) currentStep = 4;
    
    // Detect retry scenarios
    if (msgLower.includes('retry') || msgLower.includes('attempt')) {
        const match = message.match(/attempt (\d+)\/(\d+)/i);
        if (match) {
            retryCount = parseInt(match[1]);
        }
    }

    switch(status) {
        case 'waiting_admin':
            showStatus('waiting', 'Waiting for Admin Authorization', message, 10, 0);
            break;
            
        case 'admin_authorized':
            showStatus('ready', 'Admin Authorized!', message, 20, 0);
            showToast('Ready', 'Admin verified! Employee can now scan (4 angles required)', 'success');
            break;
            
        case 'ready':
            if (msgLower.includes('remove')) {
                showStatus('ready', 'Remove Finger', message, 22, 0);
            } else {
                showStatus('ready', 'Ready for Employee', message, 25, 0);
            }
            break;
            
        case 'scanning':
            let progress = 30;
            let instruction = '';
            
            if (currentStep === 1) {
                progress = 35;
                instruction = stepInstructions[1];
            } else if (currentStep === 2) {
                progress = 50;
                instruction = stepInstructions[2];
            } else if (currentStep === 3) {
                progress = 65;
                instruction = stepInstructions[3];
            } else if (currentStep === 4) {
                progress = 80;
                instruction = stepInstructions[4];
            }
            
            showStatus('scanning', instruction.title, message, progress, currentStep, instruction);
            break;
            
        case 'processing':
            let procProgress = 85;
            if (msgLower.includes('creating model') || msgLower.includes('merging')) {
                procProgress = 85;
            } else if (msgLower.includes('storing') || msgLower.includes('comprehensive')) {
                procProgress = 90;
            }
            showStatus('processing', 'Processing Fingerprint', message, procProgress, currentStep);
            break;
            
        case 'storing':
            showStatus('processing', 'Storing Enhanced Template', message, 92, 4);
            break;
            
        case 'verifying':
            // Check if it's a verification failure or retry
            if (msgLower.includes('failed') || msgLower.includes('not recognized')) {
                const match = message.match(/attempt (\d+)\/(\d+)/i);
                if (match) {
                    const attempt = parseInt(match[1]);
                    const maxAttempts = parseInt(match[2]);
                    showStatus('verify-failed', `Verification Failed (Attempt ${attempt}/${maxAttempts})`, message, 95, 5, attempt);
                    showToast('Verification Failed', 'Enrolled finger not recognized - retry needed', 'error');
                } else {
                    showStatus('verify-failed', 'Verification Failed', message, 95, 5, 1);
                }
            } else if (msgLower.includes('timeout')) {
                showStatus('error', 'Verification Timeout', 'Enrollment deleted due to verification timeout', 0, 0);
                showToast('Timeout', 'Verification timeout - enrollment deleted', 'error');
                setTimeout(() => { closeEnrollment(); }, 3500);
            } else if (msgLower.includes('remove')) {
                showStatus('verifying', 'Remove Finger for Verification', message, 93, 5);
            } else if (msgLower.includes('scan again') || msgLower.includes('one more time')) {
                showStatus('verifying', 'Scan Again to Verify', message, 95, 5);
                showToast('Verification', 'Place finger again to test enrollment quality', 'info');
            } else {
                showStatus('verifying', 'Quality Verification Test', message, 95, 5);
            }
            break;
            
        case 'success':
            showStatus('success', 'Enrollment Complete!', `${currentEmployee.name} enrolled with verified 4-angle recognition!`, 100, 5);
            showToast('Success!', `${currentEmployee.name} enrolled with high-quality verification`, 'success');
            setTimeout(() => { location.reload(); }, 3000);
            break;
            
        case 'retry':
        case 'warning':
            showStatus('retry', 'Adjust Finger Position', message, currentStep === 3 ? 65 : 80, currentStep);
            break;
            
        case 'error':
            let isRetryable = msgLower.includes('adjust') || 
                             msgLower.includes('try again') || 
                             msgLower.includes("didn't match") ||
                             msgLower.includes('lift') ||
                             msgLower.includes('messy') ||
                             msgLower.includes('quality');
            
            if (isRetryable) {
                showStatus('retry', 'Quality Issue - Retry Needed', message, currentStep * 20, currentStep);
            } else {
                showStatus('error', 'Enrollment Failed', message, 0, 0);
                showToast('Error', message, 'error');
                setTimeout(() => { closeEnrollment(); }, 3500);
            }
            break;
            
        case 'timeout':
            showStatus('error', 'Timeout', message, 0, 0);
            showToast('Timeout', 'Enrollment timed out', 'error');
            setTimeout(() => { closeEnrollment(); }, 3000);
            break;
            
        case 'cancelled':
            showStatus('error', 'Cancelled', 'Enrollment was cancelled', 0, 0);
            setTimeout(() => { closeEnrollment(); }, 2000);
            break;
    }
}

function startEnrollment(employee) {
    currentEmployee = employee;
    enrollmentInProgress = true;
    currentStep = 0;
    retryCount = 0;
    
    document.querySelectorAll('[id^="enrollBtn"]').forEach(btn => btn.disabled = true);
    
    document.getElementById('enrollmentModal').classList.add('active');
    showStatus('waiting', 'Initializing Enhanced Enrollment', `Enrolling: ${employee.name}<br>System: 4-angle capture + quality verification`, 5, 0);
    
    fetch('enrollment_request_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            employee_id: employee.id,
            employee_name: employee.name,
            fingerprint_id: <?php echo $next_fp_id; ?>
        })
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              showStatus('waiting', 'Waiting for Admin Authorization', 'Admin must scan their fingerprint to authorize this enrollment', 10, 0);
              startPolling();
          } else {
              showStatus('error', 'Connection Error', data.message || 'Failed to start enrollment', 0, 0);
          }
      });
}

function cancelEnrollment() {
    if (!enrollmentInProgress) {
        closeEnrollment();
        return;
    }
    
    if (confirm('⚠️ Cancel enrollment in progress?\n\nAll captured fingerprint data will be lost and the employee will need to restart the entire 4-angle process.')) {
        enrollmentInProgress = false;
        stopPolling();
        
        fetch('enrollment_cancel_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ employee_id: currentEmployee.id })
        });
        
        showToast('Cancelled', 'Enrollment cancelled - no data saved', 'info');
        closeEnrollment();
    }
}

function closeEnrollment() {
    enrollmentInProgress = false;
    currentEmployee = null;
    currentStep = 0;
    retryCount = 0;
    stopPolling();
    document.getElementById('enrollmentModal').classList.remove('active');
    document.querySelectorAll('[id^="enrollBtn"]').forEach(btn => btn.disabled = false);
}

function showStatus(type, title, message, progress, step, instruction = null) {
    const icons = {
        waiting: '<i class="fas fa-hand-paper"></i>',
        ready: '<i class="fas fa-check-circle"></i>',
        scanning: '<i class="fas fa-fingerprint"></i>',
        processing: '<i class="fas fa-cog fa-spin"></i>',
        verifying: '<i class="fas fa-clipboard-check"></i>',
        'verify-failed': '<i class="fas fa-times-circle"></i>',
        success: '<i class="fas fa-check"></i>',
        error: '<i class="fas fa-times"></i>',
        retry: '<i class="fas fa-exclamation-triangle"></i>'
    };
    
    // Build step indicator (0=wait, 1-4=scans, 5=verify)
    let stepIndicatorHTML = '';
    if (step > 0 && type !== 'error') {
        const stepClass = (s) => {
            if (s < step) return 'completed';
            if (s === step) {
                if (type === 'retry') return 'retry';
                if (type === 'verify-failed') return 'retry';
                return 'active';
            }
            return '';
        };
        
        stepIndicatorHTML = `
            <div class="step-indicator">
                <div class="step ${stepClass(1)}">
                    <div class="step-circle">${step > 1 ? '✓' : '1'}</div>
                    <div class="step-label">Center</div>
                </div>
                <div class="step ${stepClass(2)}">
                    <div class="step-circle">${step > 2 ? '✓' : '2'}</div>
                    <div class="step-label">Upper</div>
                </div>
                <div class="step ${stepClass(3)}">
                    <div class="step-circle">${step > 3 ? '✓' : '3'}</div>
                    <div class="step-label">Left Tilt</div>
                </div>
                <div class="step ${stepClass(4)}">
                    <div class="step-circle">${step > 4 ? '✓' : '4'}</div>
                    <div class="step-label">Right Tilt</div>
                </div>
                ${step >= 5 ? `
                <div class="step ${type === 'verify-failed' ? 'retry' : 'active'}">
                    <div class="step-circle">${type === 'success' ? '✓' : '<i class="fas fa-check"></i>'}</div>
                    <div class="step-label">Verify</div>
                </div>
                ` : ''}
            </div>
        `;
    }
    
    // Build instruction box if provided
    let instructionHTML = '';
    if (instruction && type === 'scanning') {
        instructionHTML = `
            <div class="instruction-box">
                <h4><i class="fas fa-hand-point-right"></i> ${instruction.desc}</h4>
                <p><i class="fas fa-info-circle"></i> ${instruction.detail}</p>
            </div>
        `;
    }
    
    // Verification failure warning
    if (type === 'verify-failed' && typeof instruction === 'number') {
        const attempt = instruction;
        instructionHTML = `
            <div class="verification-warning">
                <h4>
                    <i class="fas fa-exclamation-triangle"></i>
                    Verification Test Failed
                </h4>
                <p><strong>Your just-enrolled finger was NOT recognized!</strong></p>
                <p>The fingerprint was saved to the sensor but failed the quality verification test. This means it might not work reliably during attendance.</p>
                <ul>
                    <li><strong>Try placing your finger differently</strong> (change angle/pressure)</li>
                    <li><strong>Ensure finger is clean and dry</strong></li>
                    <li><strong>Cover more of the sensor surface</strong></li>
                    ${attempt >= 3 ? '<li><strong style="color:#e74c3c;">Final attempt - enrollment will be deleted if this fails</strong></li>' : ''}
                </ul>
            </div>
        `;
    }
    
    // Show retry counter if applicable
    let counterHTML = '';
    if (type === 'retry' && retryCount > 0) {
        counterHTML = `<span class="retry-counter">Attempt ${retryCount}/3</span>`;
    } else if (type === 'verify-failed' && typeof instruction === 'number') {
        counterHTML = `<span class="verify-counter">Verify Attempt ${instruction}/3</span>`;
    }
    
    const html = `
        <div class="status-icon ${type}">
            ${icons[type] || icons.waiting}
        </div>
        <h3 style="margin: 0 0 5px 0; color: #212529; font-size: 20px;">
            ${title}${counterHTML}
        </h3>
        <p style="margin: 0 0 15px 0; color: #666; font-size: 14px; line-height: 1.6;">${message}</p>
        ${instructionHTML}
        ${stepIndicatorHTML}
        <div class="progress-bar">
            <div class="progress-bar-fill" style="width: ${progress}%"></div>
        </div>
        ${(type === 'error' || type === 'success') ? `
            <button class="btn-black" onclick="closeEnrollment()" style="margin-top: 20px; width: 100%;">
                ${type === 'success' ? 'Done' : 'Close'}
            </button>
        ` : `
            <button onclick="cancelEnrollment()" style="margin-top: 20px; width: 100%; padding: 10px; border-radius: 6px; background: white; color: #d90429; border: 2px solid #d90429; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                ${type === 'verify-failed' ? 'Cancel & Delete Enrollment' : 'Cancel Enrollment'}
            </button>
        `}
    `;
    
    document.getElementById('enrollmentStatus').innerHTML = html;
}

function showToast(title, message, type) {
    const toast = document.getElementById('toast');
    document.getElementById('toastTitle').textContent = title;
    document.getElementById('toastMessage').textContent = message;
    
    toast.className = 'toast'; 
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 4000);
}

async function checkConnection() {
    try {
        const response = await fetch('check_connection.php');
        const data = await response.json();
        updateConnectionStatus(data.connected);
    } catch {
        updateConnectionStatus(false);
    }
}

window.addEventListener('load', function() {
    checkConnection();
    setInterval(checkConnection, 5000);
});

window.addEventListener('beforeunload', function(e) {
    if (enrollmentInProgress) {
        e.preventDefault();
        e.returnValue = '⚠️ Enrollment in progress!\n\nIf you leave now, all captured fingerprint data will be lost and the employee will need to restart the entire 4-angle enrollment process.';
    }
});
</script>

</body>
</html>