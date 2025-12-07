<?php
// APP_dashboard.php
session_start();
include 'db_connection.php';

// --- SECURITY CHECK ---
// If the user is NOT logged in (no ID in session), redirect to Login Page
if (!isset($_SESSION['employee_id'])) {
    header("Location: index.php");
    exit();
}
// ----------------------

// --- 1. "Remember Me" Logic (Skipped if session is active) ---
if (!isset($_SESSION['employee_id']) && isset($_COOKIE['remember'])) {
    $token = $_COOKIE['remember'];
    $hashedToken = hash('sha256', $token);

    // Note: Assuming employee accounts are handled here
    $stmt = $conn->prepare("SELECT employee_id FROM employee_accounts WHERE remember_token = ?");
    $stmt->bind_param("s", $hashedToken);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $employee = $result->fetch_assoc();
        $_SESSION['employee_id'] = $employee['employee_id']; 
    } else {
        setcookie('remember', '', time() - 3600, '/');
        unset($_COOKIE['remember']);
    }
}

 
// =========================================================
// FIX: FETCH EMPLOYEE DETAILS USING THE CORRECT ID COLUMN
// =========================================================
$session_emp_id = $_SESSION['employee_id']; 
$real_db_id = 0; 
$employee_full_name = "Employee (Error)"; 
$display_id = "N/A";

// FIX: Change 'WHERE employee_id = ?' to 'WHERE id = ?' 
// The session holds the numeric ID (e.g. 908), so we match it against the 'id' column.
$idStmt = $conn->prepare("SELECT id, first_name, last_name, employee_id FROM employees WHERE id = ?");
$idStmt->bind_param("i", $session_emp_id); // Changed "s" to "i" because it's an integer
$idStmt->execute();
$idRes = $idStmt->get_result();

if ($idRes->num_rows > 0) {
    $row = $idRes->fetch_assoc();
    $real_db_id = $row['id']; 
    
    // Set the name correctly
    $employee_full_name = htmlspecialchars($row['first_name'] . " " . $row['last_name']);
    
    // Set the Display ID (The string like "EMP-2025-3476")
    $display_id = htmlspecialchars($row['employee_id']);
} else {
    // Fallback if not found
    $real_db_id = intval($session_emp_id);
}
$idStmt->close();
// =========================================================

// Initialize dashboard stats
$present_days = 0; $absent_days = 0; $late_count = 0; 
$latest_salary = 0; $latest_cutoff = 'N/A'; $latest_paid_date = 'N/A';

// ---------------------------------------------------------
// 3. ATTENDANCE QUERY (Uses $real_db_id)
// ---------------------------------------------------------
$currentMonth = date('Y-m');

$stmt = $conn->prepare("SELECT attendance_date as date, MIN(TIME(clock_in)) as time_in 
                        FROM attendance_logs 
                        WHERE employee_id = ? AND DATE_FORMAT(attendance_date, '%Y-%m') = ? 
                        GROUP BY attendance_date");
$stmt->bind_param("is", $real_db_id, $currentMonth);
$stmt->execute();
$result = $stmt->get_result();

$total_days = 0;
while ($row = $result->fetch_assoc()) {
    $total_days++;
    if (!empty($row['time_in'])) {
        $time_in = strtotime($row['time_in']);
        if ($time_in > strtotime('09:00:00')) $late_count++;
    }
}
$present_days = $total_days;
$absent_days = max(0, 22 - $present_days); 
$stmt->close();

// ---------------------------------------------------------
// 4. PAYROLL QUERY (Uses $real_db_id)
// ---------------------------------------------------------
$stmt2 = $conn->prepare("SELECT net_pay, 
                                payroll_period_start AS cutoff_from, 
                                payroll_period_end AS cutoff_to, 
                                created_at AS generated_at 
                         FROM payrolls 
                         WHERE employee_id = ?
                         ORDER BY payroll_period_end DESC LIMIT 1");
$stmt2->bind_param("i", $real_db_id);
$stmt2->execute();
$res2 = $stmt2->get_result();

if ($row = $res2->fetch_assoc()) {
    $latest_salary = number_format($row['net_pay'], 2);
    
    $start_date = date('M d', strtotime($row['cutoff_from']));
    $end_date   = date('M d, Y', strtotime($row['cutoff_to']));
    $latest_cutoff = $start_date . " - " . $end_date;
    
    $latest_paid_date = date('F d, Y', strtotime($row['generated_at']));
}
$stmt2->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="images/logo.png">
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .card { border: none; border-radius: 1rem; box-shadow: 0 4px 10px rgba(0,0,0,0.08); transition: transform 0.2s; }
    .card:hover { transform: translateY(-3px); }
    .card-title { font-weight: 600; }
    .fs-4 { font-weight: 500; }
    .main-content { flex-grow: 1; padding: 20px; }
  </style>
</head>
<body>
  <div class="d-flex">
      <?php include 'APP_sidebar.php'; ?>
      <div class="main-content container mt-5">
        
        <div class="text-center mb-5">
            <h2 class="fw-bold text-dark">Welcome, <?= $employee_full_name ?>!</h2>
            <span class="badge bg-secondary fs-6">ID: <?= $display_id ?></span>
        </div>

        <h4 class="mb-3 text-center text-muted">My Summary</h4>
        <div class="row text-center justify-content-center">
          <div class="col-md-6 mb-4">
            <div class="card rounded-4 border-0">
              <div class="card-body">
                <i class="bi bi-calendar-check-fill fs-1 text-primary"></i>
                <h5 class="card-title mt-3">Attendance This Month</h5>
                <p class="fs-4 text-dark mb-1"><?= $present_days ?> Days Present</p>
                <p class="text-muted mb-1"><?= $absent_days ?> Days Absent</p>
                <p class="text-muted"><?= $late_count ?> Late Entr<?= $late_count == 1 ? 'y' : 'ies' ?></p>
              </div>
            </div>
          </div>
          <div class="col-md-6 mb-4">
            <div class="card rounded-4 border-0">
              <div class="card-body">
                <i class="bi bi-cash-stack fs-1 text-success"></i>
                <h5 class="card-title mt-3">Latest Salary</h5>
                <p class="fs-4 text-success mb-1">₱<?= $latest_salary ?></p>
                <p class="text-muted mb-1">Cutoff: <?= htmlspecialchars($latest_cutoff) ?></p>
                <p class="text-muted">Paid on: <?= htmlspecialchars($latest_paid_date) ?></p>
              </div>
            </div>
          </div>
        </div>
      </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>