<?php
session_start();
include 'db_connection.php';

// --- 1. AUTH & SMART ID DETECTION (Synced with Dashboard) ---
if (!isset($_SESSION['employee_id'])) {
    header("Location: APP_index.php");
    exit;
}

$session_val = $_SESSION['employee_id']; 
$real_db_id = 0; 

// Resolve the correct Integer ID (908)
if (is_numeric($session_val)) {
    $stmt = $conn->prepare("SELECT id FROM employees WHERE id = ?");
    $stmt->bind_param("i", $session_val);
} else {
    $stmt = $conn->prepare("SELECT id FROM employees WHERE employee_id = ?");
    $stmt->bind_param("s", $session_val);
}
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $real_db_id = $res->fetch_assoc()['id'];
} else {
    $real_db_id = intval($session_val); 
}
$stmt->close();

// --- 2. HANDLE DATE FILTER ---
// Default to Current Month (1st to Today)
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-d');

// --- 3. FETCH ATTENDANCE LOGS ---
$logs = [];
$query = "SELECT attendance_date, clock_in, clock_out, status 
          FROM attendance_logs 
          WHERE employee_id = ? 
          AND attendance_date BETWEEN ? AND ? 
          ORDER BY attendance_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $real_db_id, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// --- 4. CALCULATE LATE / UT / OT (The Math) ---
while ($row = $result->fetch_assoc()) {
    
    // Extract Times
    $date = date('M d, Y', strtotime($row['attendance_date']));
    
    // Handle Time In
    $time_in_display = '-';
    $time_in_obj = null;
    if ($row['clock_in']) {
        // Fix for DATETIME vs TIME format issues
        $time_in_obj = new DateTime($row['clock_in']); 
        $time_in_display = $time_in_obj->format('h:i A');
    }

    // Handle Time Out
    $time_out_display = '-';
    $time_out_obj = null;
    if ($row['clock_out']) {
        $time_out_obj = new DateTime($row['clock_out']);
        $time_out_display = $time_out_obj->format('h:i A');
    }

    // --- CALCULATIONS (8:00 AM - 5:00 PM) ---
    $late_str = "00:00";
    $ut_str = "00:00";
    $ot_str = "00:00";
    $status_badge = "Present";
    $badge_color = "success";

    if ($time_in_obj) {
        // 1. LATE CALCULATION (Threshold 8:00 AM)
        // We set the threshold date to match the attendance date to calculate diff correctly
        $shift_start = new DateTime($time_in_obj->format('Y-m-d') . ' 08:00:00');
        
        if ($time_in_obj > $shift_start) {
            $diff = $time_in_obj->diff($shift_start);
            $late_str = $diff->format('%H:%I'); // Hours:Minutes
            $status_badge = "Late";
            $badge_color = "warning text-dark";
        }
    }

    if ($time_out_obj) {
        // Thresholds
        $shift_end = new DateTime($time_out_obj->format('Y-m-d') . ' 17:00:00');

        // 2. UNDERTIME (Before 5:00 PM)
        if ($time_out_obj < $shift_end) {
            $diff = $shift_end->diff($time_out_obj);
            $ut_str = $diff->format('%H:%I');
            if ($status_badge !== "Late") {
                $status_badge = "Undertime";
                $badge_color = "info text-dark";
            }
        }

        // 3. OVERTIME (After 5:00 PM)
        if ($time_out_obj > $shift_end) {
            $diff = $time_out_obj->diff($shift_end);
            $ot_str = $diff->format('%H:%I');
            // OT usually doesn't override "Late" status visually, but you can change logic here
        }
    }

    // Push processed row to array
    $logs[] = [
        'date' => $date,
        'in' => $time_in_display,
        'out' => $time_out_display,
        'late' => $late_str,
        'ut' => $ut_str,
        'ot' => $ot_str,
        'status' => $status_badge,
        'color' => $badge_color
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <title>My Attendance</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: var(--bs-body-bg); }
    .table-primary { --bs-table-bg: #bff8c0; --bs-table-border-color: #a8bb0a; }
    .table-bordered tbody tr > td { border: 1px solid #dee2e6; vertical-align: middle; }
    
    /* Dark Mode Support */
    [data-bs-theme="dark"] .table-primary { --bs-table-bg: #2f4f2f; --bs-table-border-color: #8fcf8f; color: white; }
    [data-bs-theme="dark"] tbody tr > td { background-color: #454546; border-color: #555; }
    
    .filter-box { background: #f8f9fa; border-radius: 10px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    [data-bs-theme="dark"] .filter-box { background: #2d2d2d; }
  </style>
</head>

<body>
  <div class="exit-btn-container p-3 d-flex flex-row justify-content-between align-items-center border-bottom">
    <a href="APP_dashboard.php" class="btn btn-outline-secondary fw-semibold">← Back to Dashboard</a>
    <button id="themeToggle" class="btn btn-outline-secondary">Dark Mode</button>
  </div>

  <div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center fw-bold text-success">My Attendance Record</h2>

    <div class="filter-box mb-4">
        <form method="GET" class="row g-3 align-items-end justify-content-center">
            <div class="col-md-4">
                <label class="form-label fw-bold">From Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">To Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-success w-100 fw-bold">
                    Filter <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
    </div>

    <div class="table-responsive shadow-sm rounded">
      <table class="table table-bordered table-striped text-center align-middle mb-0" id="attendanceTable">
        <thead class="table-primary">
          <tr>
            <th>Date</th>
            <th>Time In</th>
            <th>Time Out</th>
            <th>Late<br><small>(Hr:Min)</small></th>
            <th>Undertime<br><small>(Hr:Min)</small></th>
            <th>Overtime<br><small>(Hr:Min)</small></th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($logs)): ?>
              <?php foreach ($logs as $log): ?>
              <tr>
                <td class="fw-bold"><?= $log['date'] ?></td>
                <td><?= $log['in'] ?></td>
                <td><?= $log['out'] ?></td>
                <td class="<?= $log['late'] != '00:00' ? 'text-danger fw-bold' : 'text-muted' ?>"><?= $log['late'] ?></td>
                <td class="<?= $log['ut'] != '00:00' ? 'text-warning fw-bold' : 'text-muted' ?>"><?= $log['ut'] ?></td>
                <td class="<?= $log['ot'] != '00:00' ? 'text-primary fw-bold' : 'text-muted' ?>"><?= $log['ot'] ?></td>
                <td><span class="badge bg-<?= $log['color'] ?>"><?= $log['status'] ?></span></td>
              </tr>
              <?php endforeach; ?>
          <?php else: ?>
              <tr><td colspan="7" class="text-muted py-4">No attendance records found for this period.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="scripts/toggleTheme.js"></script>
</body>
</html>