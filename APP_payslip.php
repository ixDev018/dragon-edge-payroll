<?php
require 'db_connection.php';
session_start();

if (!isset($_SESSION['employee_id'])) {
    header("Location: APP_index.php");
    exit();
}

// --- 1. SMART ID DETECTION ---
$session_val = $_SESSION['employee_id']; 
$real_db_id = 0; 

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

// --- 2. HANDLE PAYROLL PERIOD SELECTION ---
$periods = [];
$p_sql = "SELECT id, payroll_period_start, payroll_period_end FROM payrolls WHERE employee_id = ? ORDER BY payroll_period_end DESC";
$stmt = $conn->prepare($p_sql);
$stmt->bind_param("i", $real_db_id);
$stmt->execute();
$p_res = $stmt->get_result();
while($row = $p_res->fetch_assoc()) {
    $periods[] = $row;
}
$stmt->close();

$selected_payroll_id = $_GET['payroll_id'] ?? ($periods[0]['id'] ?? 0);

// --- 3. FETCH PAYSLIP & CALCULATE DETAILS ---
$payslip = null;
$late_mins = 0;
$late_amount = 0;

if ($selected_payroll_id) {
    $sql = "
        SELECT 
            p.*, 
            e.employee_id AS emp_string_id,
            e.first_name, 
            e.last_name, 
            e.department, 
            e.position,
            e.daily_rate
        FROM payrolls p
        LEFT JOIN employees e ON p.employee_id = e.id
        WHERE p.id = ? AND p.employee_id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $selected_payroll_id, $real_db_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payslip = $result->fetch_assoc();
    $stmt->close();

    // Fetch Attendance Stats for Tardiness Calculation
    if ($payslip) {
        $att_sql = "SELECT clock_in, attendance_date FROM attendance_logs 
                    WHERE employee_id = ? 
                    AND attendance_date BETWEEN ? AND ?";
        $stmt = $conn->prepare($att_sql);
        $stmt->bind_param("iss", $real_db_id, $payslip['payroll_period_start'], $payslip['payroll_period_end']);
        $stmt->execute();
        $att_res = $stmt->get_result();
        
        while ($row = $att_res->fetch_assoc()) {
            if ($row['clock_in']) {
                $in_time = new DateTime($row['clock_in']);
                $threshold = new DateTime($row['attendance_date'] . ' 08:00:00');
                if ($in_time > $threshold) {
                    $diff = $in_time->diff($threshold);
                    $late_mins += ($diff->h * 60) + $diff->i;
                }
            }
        }
        $stmt->close();

        $hourly_rate = ($payslip['daily_rate'] ?? 0) / 8;
        if ($hourly_rate > 0) {
            $late_amount = ($hourly_rate / 60) * $late_mins;
        }
    }
}

function formatMoney($amount) {
    return number_format((float)$amount, 2);
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <title>My Payslip</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        /* Modern Font for UI */
        body { 
            background-color: #f0f2f5; 
            font-family: 'Poppins', sans-serif; 
        }
        
        /* Monospace Font for Slip */
        .slip-container {
            background: #fff;
            border: 2px solid #000;
            max-width: 900px;
            margin: 40px auto;
            padding: 0;
            box-shadow: 5px 5px 15px rgba(0,0,0,0.15);
            font-family: 'Courier New', Courier, monospace;
            /* Added min-height to ensure it doesn't look squashed even on screen */
            min-height: 500px; 
            display: flex;
            flex-direction: column;
        }

        .slip-header {
            border-bottom: 2px solid #000;
            padding: 20px 25px; /* Increased padding */
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .slip-sub-header {
            border-bottom: 2px solid #000;
            padding: 15px 25px; /* Increased padding */
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .slip-body {
            display: flex;
            flex-grow: 1; /* Pushes footer down */
        }
        
        .col-payout, .col-expenses {
            padding: 0;
            display: flex;
            flex-direction: column;
        }
        
        .col-payout {
            border-right: 2px solid #000;
            flex: 1;
        }
        
        .col-expenses {
            flex: 1;
        }

        .section-title {
            border-bottom: 1px solid #000;
            text-align: center;
            font-weight: bold;
            background: #eee;
            padding: 8px; /* Increased padding */
            font-size: 1rem;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 15px; /* More vertical breathing room */
            font-size: 0.95rem;
        }

        .item-row span:first-child { font-weight: 600; color: #333; }
        
        .footer-row {
            border-top: 2px solid #000;
            padding: 15px 25px; /* Increased padding */
            background: #fff;
            font-weight: bold;
            font-size: 1.2rem;
            display: flex;
            justify-content: space-between;
            margin-top: auto; /* Ensures footer sticks to bottom if body is short */
        }

        /* Dark Mode Adjustments */
        [data-bs-theme="dark"] body { background-color: #121212; color: #fff; }
        [data-bs-theme="dark"] .slip-container { background: #2d2d2d; border-color: #777; color: #fff; }
        [data-bs-theme="dark"] .slip-header, [data-bs-theme="dark"] .slip-sub-header, [data-bs-theme="dark"] .col-payout, [data-bs-theme="dark"] .section-title, [data-bs-theme="dark"] .footer-row { border-color: #777; }
        [data-bs-theme="dark"] .section-title { background: #444; }
        [data-bs-theme="dark"] .footer-row { background: #2d2d2d; }
        [data-bs-theme="dark"] .item-row span:first-child { color: #ccc; }

        /* PRINT STYLES - The Key Fixes */
        @media print {
            .no-print { display: none !important; }
            body { 
                background: #fff; 
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .slip-container { 
                box-shadow: none; 
                margin: 20px auto; /* Center on page */
                width: 95%; /* Use almost full width */
                max-width: 100%; 
                border: 2px solid #000; 
                /* Ensure background colors print */
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }
            .slip-body {
                min-height: 400px; /* Force minimum height on paper */
            }
            .item-row {
                padding: 8px 15px; /* Even more breathing room on paper */
            }
            .section-title {
                background-color: #eee !important; /* Force gray background */
            }
        }
    </style>
</head>
<body>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <a href="APP_dashboard.php" class="btn btn-outline-secondary fw-semibold">← Back to Dashboard</a>
        
        <form method="GET" class="d-inline-block">
            <select name="payroll_id" class="form-select form-select-sm fw-semibold" onchange="this.form.submit()" style="min-width: 250px;">
                <?php if (empty($periods)): ?>
                    <option value="">No Payslips Found</option>
                <?php else: ?>
                    <?php foreach ($periods as $p): ?>
                        <?php $label = date('M d', strtotime($p['payroll_period_start'])) . ' - ' . date('M d, Y', strtotime($p['payroll_period_end'])); ?>
                        <option value="<?= $p['id'] ?>" <?= $selected_payroll_id == $p['id'] ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </form>
    </div>

    <?php if ($payslip): ?>
        <?php 
            $basic = $payslip['basic_salary'];
            $net = $payslip['net_pay'];
            
            $sss = $basic * 0.045; 
            $philhealth = $basic * 0.05;
            $pagibig = 200.00;
            
            $calculated_deductions = $sss + $philhealth + $pagibig + $late_amount;
            $calculated_net = $basic - $calculated_deductions;
            
            $adjustment = $calculated_net - $net;
            
            $daily_rate = $payslip['daily_rate'] > 0 ? $payslip['daily_rate'] : 1;
            $days_worked = $basic / $daily_rate;
        ?>

        <div class="slip-container" id="printableArea">
            
            <div class="slip-header">
                <div><strong>Client: DRAGON EDGE GROUP</strong></div>
                <div class="text-end"><strong>*** SERVICE SLIP ***</strong></div>
            </div>

            <div class="slip-sub-header">
                <div class="row">
                    <div class="col-md-7">
                        Member: <strong><?= strtoupper($payslip['last_name'] . ', ' . $payslip['first_name']) ?></strong> (<?= $payslip['emp_string_id'] ?>)<br>
                        Task Assigned: <?= strtoupper($payslip['department'] . ' - ' . $payslip['position']) ?>
                    </div>
                    <div class="col-md-5 text-end">
                        Period: <?= date('m/d/Y', strtotime($payslip['payroll_period_start'])) ?> - <?= date('m/d/Y', strtotime($payslip['payroll_period_end'])) ?><br>
                        Payout Date: <?= date('m/d/Y', strtotime($payslip['created_at'])) ?> &nbsp;|&nbsp; Basic Rate: <?= formatMoney($payslip['daily_rate']) ?>
                    </div>
                </div>
            </div>

            <div class="slip-body">
                <div class="col-payout">
                    <div class="section-title">AMOUNT OF PAYOUT</div>
                    
                    <div style="flex-grow: 1; padding-top: 10px;">
                        <div class="item-row">
                            <span>Regular Day (<?= number_format($days_worked, 2) ?> days)</span>
                            <span><?= formatMoney($basic) ?></span>
                        </div>
                        <div class="item-row">
                            <span>Overtime (0.00 Hrs)</span>
                            <span>0.00</span>
                        </div>
                    </div>

                    <div class="item-row" style="border-top: 1px dashed #ccc; margin-top: 20px; padding-top: 10px; padding-bottom: 20px;">
                        <span><strong>Total Payout:</strong></span>
                        <span><strong><?= formatMoney($basic) ?></strong></span>
                    </div>
                </div>

                <div class="col-expenses">
                    <div class="section-title">E X P E N S E S</div>
                    
                    <div style="flex-grow: 1; padding-top: 10px;">
                        <div class="item-row">
                            <span>S.S.S.</span>
                            <span><?= formatMoney($sss) ?></span>
                        </div>
                        <div class="item-row">
                            <span>Philhealth</span>
                            <span><?= formatMoney($philhealth) ?></span>
                        </div>
                        <div class="item-row">
                            <span>Pag-IBIG</span>
                            <span><?= formatMoney($pagibig) ?></span>
                        </div>
                        <div class="item-row">
                            <span>Tardiness (<?= round($late_mins/60, 2) ?> Hrs)</span>
                            <span><?= formatMoney($late_amount) ?></span>
                        </div>
                        <?php if ($adjustment > 0): ?>
                        <div class="item-row">
                            <span>Withholding Tax / Adjs.</span>
                            <span><?= formatMoney($adjustment) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="item-row" style="border-top: 1px dashed #ccc; margin-top: 20px; padding-top: 10px; padding-bottom: 20px;">
                        <span><strong>Total Expenses:</strong></span>
                        <span><strong><?= formatMoney($sss + $philhealth + $pagibig + $late_amount + $adjustment) ?></strong></span>
                    </div>
                </div>
            </div>

            <div class="footer-row">
                <span>NET PAYOUT :</span>
                <span><?= formatMoney($net) ?></span>
            </div>

        </div>
        
        <div class="text-center mb-5 no-print">
            <button onclick="window.print()" class="btn btn-primary fw-semibold px-4 py-2">
                <i class="bi bi-printer"></i> Print Slip
            </button>
        </div>

    <?php else: ?>
        <div class="alert alert-warning text-center m-5 shadow-sm border-0">
            <h4 class="fw-bold">No Payslip Records Found</h4>
            <p>Please wait for the admin to generate the payroll for this period.</p>
        </div>
    <?php endif; ?>
</div>

<script src="scripts/toggleTheme.js"></script>
</body>
</html>