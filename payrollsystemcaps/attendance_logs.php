<?php
// attendance_logs.php (Admin Side)
require 'db_connection.php'; 



// --- 1. HANDLE FILTERS ---
$search = $_GET['search'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// --- 2. SQL QUERY ---
// We start with "WHERE 1=1" so we can easily append "AND" conditions dynamically
$sql = "SELECT 
            a.attendance_date, 
            a.clock_in, 
            a.clock_out, 
            e.employee_id AS emp_id_str, 
            e.first_name, 
            e.last_name
        FROM attendance_logs a
        LEFT JOIN employees e ON a.employee_id = e.id
        WHERE 1=1";

$params = [];
$types = "";

// Apply Date Range Filter ONLY if inputs are filled
if (!empty($start_date) && !empty($end_date)) {
    $sql .= " AND a.attendance_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

// Apply Search Filter
if (!empty($search)) {
    $sql .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_id LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

// Order by Latest Date first, then Alphabetical
$sql .= " ORDER BY a.attendance_date DESC, e.last_name ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$logs = [];

// --- 3. CALCULATE METRICS ---
while ($row = $result->fetch_assoc()) {
    $time_in_display = '--';
    $time_out_display = '--';
    $late_mins = 0;
    $ot_str = "00:00";
    $ut_str = "00:00";
    $status = "Present";

    $in_obj = $row['clock_in'] ? new DateTime($row['clock_in']) : null;
    $out_obj = $row['clock_out'] ? new DateTime($row['clock_out']) : null;

    // Late Calculation (After 8:00 AM)
    if ($in_obj) {
        $time_in_display = $in_obj->format('h:i A');
        $shift_start = new DateTime($in_obj->format('Y-m-d') . ' 08:00:00');
        if ($in_obj > $shift_start) {
            $diff = $in_obj->diff($shift_start);
            $late_mins = ($diff->h * 60) + $diff->i; 
            $status = "Late";
        }
    }

    // Undertime/Overtime Calculation (5:00 PM)
    if ($out_obj) {
        $time_out_display = $out_obj->format('h:i A');
        $shift_end = new DateTime($out_obj->format('Y-m-d') . ' 17:00:00');
        
        // Undertime
        if ($out_obj < $shift_end) {
            $diff = $shift_end->diff($out_obj);
            $ut_str = $diff->format('%H:%I');
            if ($status !== "Late") $status = "Undertime";
        }
        // Overtime
        if ($out_obj > $shift_end) {
            $diff = $out_obj->diff($shift_end);
            $ot_str = $diff->format('%H:%I');
        }
    }

    $logs[] = [
        'emp_id' => $row['emp_id_str'],
        'name' => $row['first_name'] . ' ' . $row['last_name'],
        'date' => date('M d, Y', strtotime($row['attendance_date'])),
        'in' => $time_in_display,
        'out' => $time_out_display,
        'late' => $late_mins,
        'ut' => $ut_str,
        'ot' => $ot_str,
        'status' => $status
    ];
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Employee Attendance Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');
        
        body { font-family: 'Poppins', sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
        .main-content { margin-left: 280px; padding: 40px; display: flex; flex-direction: column; align-items: center; min-height: calc(100vh - 80px); }
        .header-container { width: 100%; max-width: 1200px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .title { display: flex; align-items: center; gap: 12px; font-size: 26px; font-weight: 600; color: #2c3e50; margin: 0; }
        .title i { color: #4facfe; }
        .card { width: 100%; max-width: 1200px; background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05); border: 1px solid #eee; }
        .table-primary { --bs-table-bg: #bff8c0; --bs-table-border-color: #a8bb0a; }
        .table-bordered tbody tr > td { border: 1px solid #dee2e6; vertical-align: middle; }
        .filter-row { background: #f8f9fa; border-radius: 10px; padding: 20px; border: 1px solid #eee; margin-bottom: 20px;}
        .metric-ot { color: #198754; font-weight: 600; }
        .metric-late { color: #dc3545; font-weight: 600; }
        .metric-under { color: #ffc107; font-weight: 600; }
        
        [data-bs-theme="dark"] body { background-color: #121212; }
        [data-bs-theme="dark"] .main-content { background-color: #121212; }
        [data-bs-theme="dark"] .card { background-color: #2c3034; border-color: #444; color: #fff; }
        [data-bs-theme="dark"] .title { color: #fff; }
        [data-bs-theme="dark"] .filter-row { background-color: #2c3034; border-color: #444; }
        [data-bs-theme="dark"] .table-primary { --bs-table-bg: #2f4f2f; --bs-table-border-color: #8fcf8f; color: white; }
        [data-bs-theme="dark"] tbody tr > td { background-color: #454546ff; border-color: #555; }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        
        <div class="header-container">
            <div class="title">
                <i class="fas fa-clipboard-list"></i>
                Attendance Sheet
            </div>
            <!-- <button id="themeToggle" class="btn btn-outline-secondary">
                <i class="fas fa-moon"></i> Dark Mode
            </button> -->
        </div>

        <div class="card">
            <form method="GET" class="row g-3 filter-row">
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">Search Employee</label>
                    <input type="text" name="search" class="form-control" placeholder="Name or ID..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-50 fw-bold"><i class="fas fa-search"></i> Filter</button>
                    <a href="attendance_logs.php" class="btn btn-secondary w-50 fw-bold"><i class="fas fa-undo"></i> Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped text-center align-middle mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th>Date</th>
                            <th>Employee ID</th>
                            <th>Employee Name</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Overtime <small>(Hr:Min)</small></th>
                            <th>Late <small>(Min)</small></th>
                            <th>Undertime <small>(Hr:Min)</small></th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $row): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($row['date']) ?></td>
                                <td><?= htmlspecialchars($row['emp_id'] ?? 'N/A') ?></td>
                                <td class="fw-semibold text-start ps-3"><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['in']) ?></td>
                                <td><?= htmlspecialchars($row['out']) ?></td>
                                <td class="metric-ot"><?= $row['ot'] ?></td>
                                <td class="metric-late"><?= $row['late'] > 0 ? $row['late'] : '0' ?></td>
                                <td class="metric-under"><?= $row['ut'] ?></td>
                                <td>
                                    <?php 
                                        $badge = 'success';
                                        if ($row['status'] == 'Late') $badge = 'warning text-dark';
                                        if ($row['status'] == 'Undertime') $badge = 'info text-dark';
                                    ?>
                                    <span class="badge bg-<?= $badge ?>"><?= $row['status'] ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-muted p-4">No attendance records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="scripts/toggleTheme.js"></script>
</body>
</html>