<?php
// bio.php - Attendance Kiosk
require 'db_connection.php';
date_default_timezone_set('Asia/Manila'); // Adjust to your timezone

// --- [BACKEND] HANDLER FOR "DUMMY" / MANUAL LOGGING ---
// This block simulates what the ESP32 or the JS buttons will send to the server.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $input_id = $_POST['employee_id'] ?? '';
    $action   = $_POST['action']; // 'time_in' or 'time_out'
    
    if (empty($input_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter an Employee ID.']);
        exit;
    }

    // 1. Verify Employee Exists
    // We check against the 'employee_id' string column (e.g., "EMP001") based on your schema
    $check = $conn->prepare("SELECT id, first_name, last_name FROM employees WHERE employee_id = ?");
    $check->bind_param("s", $input_id);
    $check->execute();
    $res = $check->get_result();
    
    if ($res->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Employee ID not found.']);
        exit;
    }
    
    $emp = $res->fetch_assoc();
    $emp_name = $emp['first_name'] . ' ' . $emp['last_name'];

    // 2. Insert Log (Simulating the Hardware Scan)
    // Assuming table 'attendance_logs' has: employee_id, action, type, timestamp
    // We use the VARCHAR employee_id here to match your previous join logic
    $insert = $conn->prepare("INSERT INTO attendance_logs (employee_id, action, type, timestamp) VALUES (?, ?, 'regular', NOW())");
    $insert->bind_param("ss", $input_id, $action);
    
    if ($insert->execute()) {
        $msg = ($action === 'time_in') ? "Welcome, $emp_name!" : "Goodbye, $emp_name!";
        echo json_encode(['status' => 'success', 'message' => $msg]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
    exit;
}

// --- [FRONTEND] DATA FETCHING ---

// Fetch today's logs for the table below
// FIX: Using 'e.department' as per your database schema image
// FIX: Using CONCAT for name
$query = "
SELECT 
    e.employee_id,
    CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
    e.department,
    DATE(a.timestamp) AS date,
    MIN(CASE WHEN a.type = 'regular' AND a.action = 'time_in' THEN TIME(a.timestamp) END) AS time_in,
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
WHERE DATE(a.timestamp) = CURDATE()
GROUP BY e.employee_id
ORDER BY MAX(a.timestamp) DESC
LIMIT 10
";

// Execute query safely
$logs = [];
try {
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $logs[] = $row;
        }
    }
} catch (Exception $e) {
    // Silent fail for kiosk UI, logs array will be empty
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Attendance Kiosk | Dragon Edge Group</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');

        :root {
            --primary-color: #0d6efd;
            --success-color: #198754;
            --danger-color: #dc3545;
            --bg-light: #f8f9fa;
            --card-bg: #ffffff;
            --text-main: #212529;
        }

        [data-bs-theme="dark"] {
            --bg-light: #121212;
            --card-bg: #1e1e1e;
            --text-main: #f8f9fa;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: 0.3s;
        }

        /* --- Top Bar --- */
        .top-bar {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* --- Main Kiosk Container --- */
        .kiosk-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding-top: 40px;
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
        }

        .hero-title {
            font-weight: 700;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .sub-text {
            color: #6c757d;
            text-align: center;
            margin-bottom: 40px;
            font-size: 1.1rem;
        }

        /* --- Input Section --- */
        .input-area {
            width: 100%;
            max-width: 600px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-radius: 12px;
            overflow: hidden;
        }

        .form-control-lg {
            border: none;
            padding: 15px 20px;
            font-size: 1.2rem;
            text-align: center;
            letter-spacing: 2px;
            font-weight: 600;
        }
        
        .form-control-lg:focus {
            box-shadow: none;
            background-color: #fff;
        }

        /* --- Buttons --- */
        .action-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            width: 100%;
            max-width: 600px;
        }

        .btn-kiosk {
            padding: 15px;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 10px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-kiosk:active {
            transform: scale(0.98);
        }

        .btn-in { background-color: var(--primary-color); color: white; }
        .btn-out { background-color: var(--danger-color); color: white; }
        .btn-reg { background-color: var(--success-color); color: white; }

        /* --- Table --- */
        .table-container {
            width: 100%;
            margin-top: 50px;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 20px;
            overflow-x: auto;
        }

        .table-heading {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        [data-bs-theme="dark"] .table-heading {
            background-color: #0f5132;
            color: #d1e7dd;
        }
    </style>
</head>

<body>

    <!-- Top Navigation -->
    <div class="top-bar">
        <a href="employees.php" class="btn btn-outline-info fw-bold">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <button id="themeToggle" class="btn btn-outline-secondary">
            <i class="fas fa-moon"></i> Dark Mode
        </button>
    </div>

    <div class="kiosk-wrapper">
        <h1 class="hero-title">Employee Attendance Kiosk</h1>
        <p class="sub-text">
            Enter your Employee ID, then click an action.<br>
            <small class="text-info">(Hardware simulation active)</small>
        </p>

        <!-- Input Group -->
        <div class="input-group input-area">
            <span class="input-group-text bg-white border-0">
                <i class="fas fa-id-card text-muted"></i>
            </span>
            <input type="text" class="form-control form-control-lg" 
                   id="employeeID" placeholder="EMPLOYEE ID" autocomplete="off" autofocus>
            <button class="btn btn-secondary px-4" type="button" 
                    data-bs-toggle="modal" data-bs-target="#findIdModal">
                Find my ID
            </button>
        </div>

        <!-- Action Buttons -->
        <div class="action-grid">
            <button class="btn-kiosk btn-in" onclick="submitAttendance('time_in')">
                <i class="fas fa-sign-in-alt"></i> Time In
            </button>
            <button class="btn-kiosk btn-out" onclick="submitAttendance('time_out')">
                <i class="fas fa-sign-out-alt"></i> Time Out
            </button>
            <button class="btn-kiosk btn-reg" id="register">
                <i class="fas fa-fingerprint"></i> Register Fingerprint (Admin)
            </button>
        </div>

        <!-- System Message Output -->
        <div class="mt-3 w-100" style="max-width:600px;">
            <div id="alertBox" class="alert d-none text-center fw-bold" role="alert"></div>
        </div>

        <!-- Recent Logs Table -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold m-0">Recent Logs (Live Feed)</h5>
                <span class="badge bg-success" id="liveIndicator">LIVE</span>
            </div>
            
            <table class="table table-hover align-middle text-center">
                <thead class="table-heading">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="attendanceBody">
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="6" class="text-muted">No attendance records yet today.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['employee_id']) ?></td>
                                <td class="fw-bold text-start"><?= htmlspecialchars($log['employee_name']) ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars($log['department'] ?? '-') ?></small></td>
                                <td><?= !empty($log['time_in']) ? date("g:i A", strtotime($log['time_in'])) : '-' ?></td>
                                <td><?= !empty($log['time_out']) ? date("g:i A", strtotime($log['time_out'])) : '-' ?></td>
                                <td>
                                    <?php if ($log['overtime_status'] === 'YES'): ?>
                                        <span class="badge bg-warning text-dark">OT</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border">Regular</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Find ID Modal -->
    <div class="modal fade" id="findIdModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Search Employee ID</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" id="searchName" class="form-control mb-3" placeholder="Type name to search...">
                    <div id="searchResults" class="list-group">
                        <div class="list-group-item text-center text-muted">Start typing...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="webauthn/webauthn-client.js"></script>
    <script src="scripts/findMyID.js"></script> 

    <script>
        // 1. Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;
        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            html.setAttribute('data-bs-theme', newTheme);
            themeToggle.innerHTML = newTheme === 'light' 
                ? '<i class="fas fa-moon"></i> Dark Mode' 
                : '<i class="fas fa-sun"></i> Light Mode';
        });

        // 2. Submit Attendance (Simulation for Development)
        function submitAttendance(action) {
            const empId = document.getElementById('employeeID').value.trim();
            const alertBox = document.getElementById('alertBox');
            
            if (!empId) {
                Swal.fire('Required', 'Please enter your Employee ID first.', 'warning');
                return;
            }

            // Show loading state
            alertBox.className = 'alert alert-info d-block';
            alertBox.innerText = 'Processing...';

            // Send POST request to self
            const formData = new FormData();
            formData.append('action', action);
            formData.append('employee_id', empId);

            fetch('bio.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Success UI
                    alertBox.className = 'alert alert-success d-block';
                    alertBox.innerText = data.message;
                    
                    // Play success sound if desired
                    // new Audio('success.mp3').play();
                    
                    // Clear input
                    document.getElementById('employeeID').value = '';
                    
                    // Reload table immediately
                    location.reload(); 
                } else {
                    // Error UI
                    alertBox.className = 'alert alert-danger d-block';
                    alertBox.innerText = data.message;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alertBox.className = 'alert alert-danger d-block';
                alertBox.innerText = 'System Error. Check console.';
            });
        }

        // 3. Auto Refresh (Polling every 5 seconds)
        // Note: For a smoother experience, we could fetch just the table body, 
        // but reloading the page is safer for "Dummy" phase to ensure PHP re-runs.
        setInterval(() => {
            // Only reload if user is NOT typing
            if (document.activeElement.id !== 'employeeID' && !document.querySelector('.modal.show')) {
                location.reload();
            }
        }, 15000); // 15 seconds auto-refresh
    </script>
</body>
</html>