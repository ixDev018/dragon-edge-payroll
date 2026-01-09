<?php
// attendance.php - Admin View for Attendance Logs
session_start();
include 'db_connection.php';

// --- FETCH DATA ---
$logs = [];
$dbError = null;

try {
    $query = "
        SELECT 
            e.employee_id AS emp_code,
            CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
            e.department, 
            a.attendance_date AS log_date,
            TIME(a.clock_in) AS log_time,
            'time_in' AS action
        FROM attendance_logs a
        JOIN employees e ON a.employee_id = e.id
        WHERE a.clock_in IS NOT NULL

        UNION ALL

        SELECT 
            e.employee_id AS emp_code,
            CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
            e.department, 
            a.attendance_date AS log_date,
            TIME(a.clock_out) AS log_time,
            'time_out' AS action
        FROM attendance_logs a
        JOIN employees e ON a.employee_id = e.id
        WHERE a.clock_out IS NOT NULL

        ORDER BY log_date DESC, log_time DESC
    ";
    
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    $dbError = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Logs | Dragon Edge Group</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    
    <link rel="stylesheet" href="styles.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 280px; /* Aligns with your sidebar width */
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
            margin-bottom: 20px;
        }

        .title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 26px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        .title i {
            color: #4facfe;
        }

        /* Container for the top buttons */
        .header-actions {
            display: flex;
            gap: 15px;
        }

        /* Style for the new Enroll Button (Green Gradient) */
        .enroll-btn {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .enroll-btn:hover {
            transform: translateY(-2px);
            color: white;
        }

        /* Existing Kiosk Button (Purple Gradient) */
        .kiosk-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .kiosk-btn:hover {
            transform: translateY(-2px);
            color: white;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
            border: 1px solid #eee;
        }

        /* Table Styling */
        table.dataTable { width: 100% !important; border-collapse: collapse; }
        
        table.dataTable thead th {
            background-color: #f8f9fa;
            color: #555;
            font-weight: 600;
            padding: 15px;
            border-bottom: 2px solid #eee;
        }
        
        table.dataTable tbody td {
            padding: 15px;
            border-bottom: 1px solid #f1f1f1;
            vertical-align: middle;
            color: #444;
        }

        .badge-in { background-color: #d1e7dd; color: #0f5132; padding: 6px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 600; border: 1px solid #badbcc; }
        .badge-out { background-color: #f8d7da; color: #842029; padding: 6px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 600; border: 1px solid #f5c2c7; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    
    <div class="header-container header-row">
        <div class="title">
            <i class="fas fa-fingerprint"></i>
            Attendance Logs
        </div>
        
        <div class="header-actions">
            <a href="enroll_fingerprint.php" class="enroll-btn">
                <i class="fas fa-plus-circle"></i> Enroll Fingerprint
            </a>

            <a href="bio.php" target="_blank" class="kiosk-btn">
                <i class="fas fa-external-link-alt"></i> Launch Kiosk
            </a>
        </div>
    </div>

    <div class="card">
        <?php if ($dbError): ?>
            <div style="background:#fff5f5; border:1px solid #fc8181; color:#c53030; padding:15px; border-radius:6px; margin-bottom:20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Database Error:</strong> <?= htmlspecialchars($dbError) ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table class="table hover" id="attendanceTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Emp ID</th>
                        <th>Employee Name</th>
                        <th>Department</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['log_date']) ?></td>
                            <td style="font-weight:600; color:#2c3e50;"><?= htmlspecialchars($row['log_time']) ?></td>
                            <td><?= htmlspecialchars($row['emp_code'] ?? 'N/A') ?></td>
                            <td style="font-weight: 500;">
                                <?= htmlspecialchars($row['employee_name'] ?? 'Unknown') ?>
                            </td>
                            <td><?= htmlspecialchars($row['department'] ?? '-') ?></td>
                            <td class="text-center">
                                <?php if ($row['action'] == 'time_in'): ?>
                                    <span class="badge-in">TIME IN</span>
                                <?php else: ?>
                                    <span class="badge-out">TIME OUT</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#attendanceTable').DataTable({
            "order": [[ 0, "desc" ], [ 1, "desc" ]], // Sort by Date then Time (Newest first)
            "language": {
                "emptyTable": "No attendance records found.",
                "search": "Search Logs:"
            },
            "pageLength": 10
        });
    });
</script>

</body>
</html>