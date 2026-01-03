<?php
session_start();
include 'db_connection.php';
include 'sidebar.php';

// Fetch employees without fingerprints
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

// Handle enrollment submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_employee'])) {
    $employee_id = (int)$_POST['employee_id'];
    $fingerprint_id = (int)$_POST['fingerprint_id'];
    
    // Update employee
    $stmt = $conn->prepare("UPDATE employees SET fingerprint_id = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $fingerprint_id, $employee_id);
    
    if ($stmt->execute()) {
        // Record enrollment
        $enrolled_by = $_SESSION['user_name'] ?? 'admin';
        $stmt = $conn->prepare("
            INSERT INTO biometric_enrollments 
            (employee_id, fingerprint_id, enrolled_by, enrollment_date)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("iis", $employee_id, $fingerprint_id, $enrolled_by);
        $stmt->execute();
        
        $message = 'Employee enrolled successfully! They can now use the biometric scanner.';
        $message_type = 'success';
        
        // Refresh employee list
        header("Location: enroll_fingerprint.php?success=1");
        exit;
    } else {
        $message = 'Error enrolling employee: ' . $conn->error;
        $message_type = 'error';
    }
}

// Check for success message
if (isset($_GET['success'])) {
    $message = 'Enrollment completed successfully!';
    $message_type = 'success';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fingerprint Enrollment | Dragon Edge</title>
    
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
        
        .page-header p {
            color: #7f8c8d;
            margin: 0;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .card-header h2 {
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        
        .info-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .info-box h3 {
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-box p {
            margin: 5px 0;
            opacity: 0.9;
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
        
        .employee-name {
            display: inline-flex;
            align-items: center;
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
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h2 {
            margin: 0;
            color: #2c3e50;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #7f8c8d;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .steps {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .steps h4 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .steps ol {
            margin: 0;
            padding-left: 20px;
        }
        
        .steps li {
            margin-bottom: 10px;
            color: #495057;
        }
        
        .fingerprint-id-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }
        
        .fingerprint-id-display h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            opacity: 0.9;
        }
        
        .fingerprint-id-display .fp-id {
            font-size: 48px;
            font-weight: 700;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="page-header">
        <h1>
            <i class="fas fa-fingerprint"></i>
            Fingerprint Enrollment
        </h1>
        <p>Register employees for biometric attendance</p>
    </div>
    
    <?php if ($message): ?>
        <div class="alert <?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="info-box">
            <h3><i class="fas fa-info-circle"></i> How to Enroll</h3>
            <p><strong>1.</strong> Click "Enroll" button next to an employee</p>
            <p><strong>2.</strong> Note the Fingerprint ID shown</p>
            <p><strong>3.</strong> Use ESP32 serial command: <code>enroll [employee_id]</code></p>
            <p><strong>4.</strong> Scan admin finger to authorize</p>
            <p><strong>5.</strong> Employee scans their finger twice</p>
            <p><strong>6.</strong> Confirm enrollment on this page</p>
        </div>
        
        <div class="card-header">
            <h2>
                <i class="fas fa-users"></i>
                Employee List
            </h2>
            <div>
                <span style="color: #7f8c8d; margin-right: 15px;">
                    Next FP ID: <strong style="color: #667eea;"><?php echo $next_fp_id; ?></strong>
                </span>
            </div>
        </div>
        
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search employees...">
            <i class="fas fa-search"></i>
        </div>
        
        <table class="employees-table" id="employeesTable">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Fingerprint ID</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td>
                            <div class="employee-name">
                                <div class="employee-avatar">
                                    <?php echo strtoupper(substr($emp['first_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></strong>
                                    <br>
                                    <small style="color: #7f8c8d;"><?php echo htmlspecialchars($emp['employee_id']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($emp['department'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($emp['position'] ?? 'N/A'); ?></td>
                        <td>
                            <?php if ($emp['fingerprint_id']): ?>
                                <strong style="color: #667eea;">#<?php echo $emp['fingerprint_id']; ?></strong>
                            <?php else: ?>
                                <span style="color: #7f8c8d;">Not assigned</span>
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
                                <button class="btn btn-primary" onclick="showEnrollModal(<?php echo $emp['id']; ?>, '<?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>', '<?php echo $emp['employee_id']; ?>', <?php echo $next_fp_id; ?>)">
                                    <i class="fas fa-fingerprint"></i> Enroll
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary" onclick="showDetailsModal(<?php echo $emp['fingerprint_id']; ?>, '<?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>')">
                                    <i class="fas fa-eye"></i> Details
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Enrollment Modal -->
<div class="modal" id="enrollModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-fingerprint"></i> Enroll Fingerprint</h2>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        
        <div id="enrollContent">
            <!-- Content will be filled by JavaScript -->
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal" id="detailsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-info-circle"></i> Enrollment Details</h2>
            <button class="close-btn" onclick="closeDetailsModal()">&times;</button>
        </div>
        
        <div id="detailsContent">
            <!-- Content will be filled by JavaScript -->
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#employeesTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

function showEnrollModal(empId, empName, empCode, fpId) {
    const content = `
        <form method="POST" action="">
            <div class="fingerprint-id-display">
                <h3>Assign Fingerprint ID</h3>
                <div class="fp-id">${fpId}</div>
            </div>
            
            <div class="form-group">
                <label>Employee</label>
                <input type="text" value="${empName} (${empCode})" readonly>
            </div>
            
            <input type="hidden" name="employee_id" value="${empId}">
            <input type="hidden" name="fingerprint_id" value="${fpId}">
            
            <div class="steps">
                <h4>Enrollment Steps:</h4>
                <ol>
                    <li>Open ESP32 Serial Monitor (115200 baud)</li>
                    <li>Type command: <strong>enroll ${empId}</strong></li>
                    <li>Press Enter</li>
                    <li>Scan an admin fingerprint to authorize</li>
                    <li>Employee scans their finger twice</li>
                    <li>Wait for success beep</li>
                    <li>Click "Confirm Enrollment" below</li>
                </ol>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" name="enroll_employee" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-check"></i> Confirm Enrollment
                </button>
                <button type="button" onclick="closeModal()" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    `;
    
    document.getElementById('enrollContent').innerHTML = content;
    document.getElementById('enrollModal').classList.add('active');
}

function showDetailsModal(fpId, empName) {
    const content = `
        <p><strong>Employee:</strong> ${empName}</p>
        <p><strong>Fingerprint ID:</strong> #${fpId}</p>
        <p><strong>Status:</strong> <span class="status-badge enrolled"><i class="fas fa-check"></i> Enrolled</span></p>
        <br>
        <button type="button" onclick="closeDetailsModal()" class="btn btn-primary">Close</button>
    `;
    
    document.getElementById('detailsContent').innerHTML = content;
    document.getElementById('detailsModal').classList.add('active');
}

function closeModal() {
    document.getElementById('enrollModal').classList.remove('active');
}

function closeDetailsModal() {
    document.getElementById('detailsModal').classList.remove('active');
}

// Close modal on outside click
window.onclick = function(event) {
    const enrollModal = document.getElementById('enrollModal');
    const detailsModal = document.getElementById('detailsModal');
    
    if (event.target === enrollModal) {
        closeModal();
    }
    if (event.target === detailsModal) {
        closeDetailsModal();
    }
}
</script>

</body>
</html>