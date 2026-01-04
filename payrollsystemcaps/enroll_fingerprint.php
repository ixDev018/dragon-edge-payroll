<?php
session_start();
include 'db_connection.php';
include 'sidebar.php';

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

// Handle manual assignment
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
    <title>Live Enrollment | Dragon Edge</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .main-content {
            margin-left: 280px;
            padding: 40px;
        }
        
        .page-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .page-header h1 i {
            color: #667eea;
        }
        
        .live-badge {
            background: #28a745;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            animation: pulse 2s infinite;
        }
        
        .live-badge.disconnected {
            background: #dc3545;
            animation: none;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .employees-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .employees-table thead th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .employees-table tbody td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            color: #2c3e50;
        }
        
        .employees-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .employee-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-badge.enrolled {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        /* Live Enrollment Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            max-width: 600px;
            width: 90%;
            animation: modalSlideIn 0.3s;
            position: relative;
        }
        
        @keyframes modalSlideIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #f8f9fa;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .modal-close:hover {
            background: #e9ecef;
            transform: rotate(90deg);
        }
        
        .enrollment-status {
            text-align: center;
            padding: 20px;
        }
        
        .status-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            animation: iconPulse 1.5s infinite;
        }
        
        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .status-icon.waiting {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .status-icon.scanning {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .status-icon.processing {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .status-icon.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            animation: successBounce 0.6s;
        }
        
        @keyframes successBounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }
        
        .status-icon.error {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .status-message {
            font-size: 24px;
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .status-detail {
            font-size: 16px;
            color: #7f8c8d;
            margin-bottom: 30px;
        }
        
        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }
        
        .quick-link-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            display: none;
            align-items: center;
            gap: 15px;
            z-index: 2000;
            animation: slideInRight 0.3s;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .toast.show {
            display: flex;
        }
        
        .toast.success { border-left: 4px solid #28a745; }
        .toast.error { border-left: 4px solid #dc3545; }
        .toast.info { border-left: 4px solid #17a2b8; }
    </style>
</head>
<body>

<div class="main-content">
    <div class="page-header">
        <h1>
            <i class="fas fa-fingerprint"></i>
            Live Fingerprint Enrollment
            <span class="live-badge" id="connectionStatus">
                <span style="width: 8px; height: 8px; background: white; border-radius: 50%; display: inline-block;"></span>
                <span id="statusText">CONNECTING...</span>
            </span>
        </h1>
        <p>Real-time enrollment with ESP32 - Fully synchronized!</p>
    </div>
    
    <!-- Quick Link Section -->
    <div class="card quick-link-section">
        <h3 style="margin-bottom: 15px; color: #2c3e50;">
            <i class="fas fa-link"></i> Quick Link Existing Fingerprint
        </h3>
        <p style="color: #7f8c8d; margin-bottom: 15px; font-size: 14px;">
            Already enrolled a fingerprint on ESP32? Link it to an employee here:
        </p>
        <form method="POST" action="" style="display: flex; gap: 10px; align-items: end;">
            <div style="flex: 1;">
                <label style="display: block; margin-bottom: 5px; font-size: 13px; color: #2c3e50;">Employee</label>
                <select name="employee_id" required style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px;">
                    <option value="">Select Employee</option>
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
                <label style="display: block; margin-bottom: 5px; font-size: 13px; color: #2c3e50;">FP ID</label>
                <input type="number" name="fingerprint_id" min="1" placeholder="e.g., 1" required style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px;">
            </div>
            <button type="submit" name="assign_existing" class="btn btn-primary">
                <i class="fas fa-link"></i> Link
            </button>
        </form>
    </div>
    
    <!-- Employees List -->
    <div class="card">
        <h2 style="margin-bottom: 20px; color: #2c3e50; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-users"></i>
            Employees
            <span style="color: #7f8c8d; font-size: 14px; font-weight: normal; margin-left: 10px;">
                Next FP ID: <strong style="color: #667eea;"><?php echo $next_fp_id; ?></strong>
            </span>
        </h2>
        
        <table class="employees-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>FP ID</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td>
                            <div style="display: inline-flex; align-items: center;">
                                <div class="employee-avatar">
                                    <?php echo strtoupper(substr($emp['first_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></strong>
                                    <br>
                                    <small style="color: #7f8c8d;">ID: <?php echo htmlspecialchars($emp['employee_id']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($emp['department'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($emp['position'] ?? 'N/A'); ?></td>
                        <td>
                            <?php if ($emp['fingerprint_id']): ?>
                                <strong style="color: #667eea;">#<?php echo $emp['fingerprint_id']; ?></strong>
                            <?php else: ?>
                                <span style="color: #7f8c8d;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($emp['fingerprint_id']): ?>
                                <span class="status-badge enrolled">
                                    <i class="fas fa-check"></i> Enrolled
                                </span>
                            <?php else: ?>
                                <span class="status-badge pending">
                                    <i class="fas fa-clock"></i> Pending
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$emp['fingerprint_id']): ?>
                                <button class="btn btn-primary" id="enrollBtn<?php echo $emp['id']; ?>" onclick='startEnrollment(<?php echo json_encode([
                                    "id" => $emp["id"],
                                    "name" => $emp["first_name"] . " " . $emp["last_name"],
                                    "emp_id" => $emp["employee_id"]
                                ]); ?>)'>
                                    <i class="fas fa-fingerprint"></i> Enroll Now
                                </button>
                            <?php else: ?>
                                <span style="color: #28a745; font-weight: 600;">
                                    <i class="fas fa-check-circle"></i> Done
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Live Enrollment Modal -->
<div class="modal" id="enrollmentModal">
    <div class="modal-content">
        <button class="modal-close" onclick="cancelEnrollment()">×</button>
        <div class="enrollment-status" id="enrollmentStatus">
            <!-- Dynamic content -->
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="toast" id="toast">
    <i class="fas fa-check-circle" style="font-size: 24px; color: #28a745;"></i>
    <div>
        <strong id="toastTitle">Success</strong>
        <div id="toastMessage" style="font-size: 14px; color: #7f8c8d;"></div>
    </div>
</div>

<script>
// ═══════════════════════════════════════════════════════════════
//  REAL-TIME POLLING SYSTEM (Fallback for MQTT WebSocket issues)
// ═══════════════════════════════════════════════════════════════

let pollingInterval = null;
let currentEmployee = null;
let enrollmentInProgress = false;
let enrollmentStartTime = 0;
let currentProgress = 0;

// Start polling for real-time updates
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
    }, 500); // Poll every 500ms for smooth updates
}

function stopPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
}

// Handle enrollment status updates
function handleEnrollmentUpdate(data) {
    const status = data.status;
    const message = data.message || '';
    
    switch(status) {
        case 'waiting_admin':
            showStatus('waiting', 'Waiting for Admin...', message, 20);
            break;
        case 'admin_authorized':
            showStatus('scanning', 'Admin Authorized!', message, 40);
            showToast('Admin authorized!', 'Employee can now scan finger', 'success');
            break;
        case 'scanning':
            showStatus('scanning', 'Scanning Fingerprint...', message, 60);
            break;
        case 'processing':
            showStatus('processing', 'Processing...', message, 80);
            break;
        case 'storing':
            showStatus('processing', 'Saving Fingerprint...', message, 90);
            break;
        case 'success':
            showStatus('success', 'Enrollment Complete!', `${currentEmployee.name} is now enrolled!`, 100);
            showToast('Success!', `${currentEmployee.name} enrolled successfully`, 'success');
            setTimeout(() => {
                location.reload();
            }, 2000);
            break;
        case 'error':
            showStatus('error', 'Error Occurred', message, 0);
            showToast('Error', message, 'error');
            setTimeout(() => {
                closeEnrollment();
            }, 3000);
            break;
        case 'timeout':
            showStatus('error', 'Timeout', message, 0);
            showToast('Timeout', 'Enrollment timed out', 'error');
            setTimeout(() => {
                closeEnrollment();
            }, 3000);
            break;
        case 'cancelled':
            showStatus('error', 'Cancelled', 'Enrollment was cancelled', 0);
            setTimeout(() => {
                closeEnrollment();
            }, 2000);
            break;
    }
}

// Start enrollment
function startEnrollment(employee) {
    currentEmployee = employee;
    enrollmentInProgress = true;
    enrollmentStartTime = Date.now();
    currentProgress = 0;
    
    // Disable all enroll buttons
    document.querySelectorAll('[id^="enrollBtn"]').forEach(btn => btn.disabled = true);
    
    document.getElementById('enrollmentModal').classList.add('active');
    showStatus('waiting', 'Starting Enrollment...', `Enrolling: ${employee.name}`, 10);
    
    // Send enrollment request to backend
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
              showStatus('waiting', 'Waiting for Admin...', 'Admin must scan finger to authorize', 20);
              startPolling();
          } else {
              showStatus('error', 'Connection Error', data.message || 'Failed to start enrollment', 0);
          }
      });
}

// Cancel enrollment
function cancelEnrollment() {
    if (!enrollmentInProgress) {
        closeEnrollment();
        return;
    }
    
    if (confirm('Are you sure you want to cancel this enrollment?')) {
        enrollmentInProgress = false;
        stopPolling();
        
        // Send cancellation to ESP32
        fetch('enrollment_cancel_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                employee_id: currentEmployee.id
            })
        });
        
        showToast('Cancelled', 'Enrollment cancelled', 'info');
        closeEnrollment();
    }
}

// Close enrollment modal
function closeEnrollment() {
    enrollmentInProgress = false;
    currentEmployee = null;
    stopPolling();
    document.getElementById('enrollmentModal').classList.remove('active');
    
    // Re-enable all enroll buttons
    document.querySelectorAll('[id^="enrollBtn"]').forEach(btn => btn.disabled = false);
}

// Show status in modal
function showStatus(type, title, message, progress) {
    currentProgress = progress;
    
    const icons = {
        waiting: '<i class="fas fa-clock"></i>',
        scanning: '<i class="fas fa-fingerprint"></i>',
        processing: '<i class="fas fa-cog fa-spin"></i>',
        success: '<i class="fas fa-check-circle"></i>',
        error: '<i class="fas fa-exclamation-triangle"></i>'
    };
    
    const html = `
        <div class="status-icon ${type}">
            ${icons[type]}
        </div>
        <div class="status-message">${title}</div>
        <div class="status-detail">${message}</div>
        <div class="progress-bar">
            <div class="progress-bar-fill" style="width: ${progress}%"></div>
        </div>
        ${type === 'error' || type === 'success' ? `
            <button class="btn btn-primary" onclick="closeEnrollment()" style="margin-top: 20px;">
                <i class="fas fa-times"></i> Close
            </button>
        ` : `
            <button class="btn btn-danger" onclick="cancelEnrollment()" style="margin-top: 20px;">
                <i class="fas fa-ban"></i> Cancel
            </button>
        `}
    `;
    
    document.getElementById('enrollmentStatus').innerHTML = html;
}

// Show toast notification
function showToast(title, message, type) {
    const toast = document.getElementById('toast');
    document.getElementById('toastTitle').textContent = title;
    document.getElementById('toastMessage').textContent = message;
    
    toast.className = 'toast ' + type + ' show';
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 4000);
}

// Update connection status
function updateConnectionStatus(connected) {
    const badge = document.getElementById('connectionStatus');
    const text = document.getElementById('statusText');
    
    if (connected) {
        badge.classList.remove('disconnected');
        text.textContent = 'LIVE';
    } else {
        badge.classList.add('disconnected');
        text.textContent = 'OFFLINE';
    }
}

// Check connection status
async function checkConnection() {
    try {
        const response = await fetch('check_connection.php');
        const data = await response.json();
        updateConnectionStatus(data.connected);
    } catch {
        updateConnectionStatus(false);
    }
}

// Initialize
window.addEventListener('load', function() {
    checkConnection();
    setInterval(checkConnection, 5000);
    
    updateConnectionStatus(true); // Assume connected initially
});

// Prevent accidental page close during enrollment
window.addEventListener('beforeunload', function(e) {
    if (enrollmentInProgress) {
        e.preventDefault();
        e.returnValue = 'Enrollment in progress. Are you sure you want to leave?';
    }
});
</script>

</body>
</html>