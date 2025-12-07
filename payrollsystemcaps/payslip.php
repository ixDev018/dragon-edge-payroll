<?php
// payslip.php
require 'db_connection.php';

$payroll_id = $_GET['id'];

// --- FIX APPLIED HERE ---
// 1. Changed JOIN to: ON p.employee_id = e.id (Matching Integer to Integer)
// 2. Added 'e.employee_id AS emp_code' to get the string ID (e.g., 2025-12-001) for display
$sql = "SELECT p.*, e.first_name, e.last_name, e.position, e.department, e.employee_id AS emp_code
        FROM payrolls p 
        JOIN employees e ON p.employee_id = e.id 
        WHERE p.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $payroll_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if(!$data) die("Payslip not found. (ID: $payroll_id)");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #eee; font-family: 'Courier New', Courier, monospace; }
        .slip-container { background: white; max-width: 800px; margin: 20px auto; padding: 30px; border: 1px solid black; }
        .amount-col { text-align: right; }
        .section-title { background: #f0f0f0; border-bottom: 1px solid black; font-weight: bold; text-align: center; }
        @media print { body { background: white; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="container text-center mt-2 no-print">
        <button onclick="window.print()" class="btn btn-primary">Print</button>
    </div>

    <div class="slip-container">
        <h4 class="text-center fw-bold">DRAGON EDGE GROUP</h4>
        <div class="text-center mb-4">PAYSLIP: <?= date('M d, Y', strtotime($data['payroll_period_start'])) ?> - <?= date('M d, Y', strtotime($data['payroll_period_end'])) ?></div>

        <div class="row mb-3 fw-bold">
            <div class="col-6">Name: <?= strtoupper($data['last_name'] . ', ' . $data['first_name']) ?></div>
            <div class="col-6 text-end">ID: <?= $data['emp_code'] ?></div>
            <div class="col-6">Position: <?= $data['position'] ?></div>
            <div class="col-6 text-end">Rate: ₱<?= number_format($data['daily_rate'], 2) ?>/day</div>
        </div>

        <div class="row border border-dark">
            <div class="col-6 border-end border-dark p-0">
                <div class="section-title">EARNINGS</div>
                <table class="table table-borderless table-sm">
                    <tr>
                        <td>Basic Pay (<?= $data['days_worked'] ?> days)</td>
                        <td class="amount-col"><?= number_format($data['basic_pay'], 2) ?></td>
                    </tr>
                    <tr>
                        <td>Overtime (<?= $data['overtime_hours'] ?> hrs)</td>
                        <td class="amount-col"><?= number_format($data['overtime_pay'], 2) ?></td>
                    </tr>
                    <tr class="border-top border-dark fw-bold">
                        <td>GROSS PAY</td>
                        <td class="amount-col"><?= number_format($data['gross_pay'], 2) ?></td>
                    </tr>
                </table>
            </div>

            <div class="col-6 p-0">
                <div class="section-title">DEDUCTIONS</div>
                <table class="table table-borderless table-sm">
                    <tr><td>Late Deduction</td><td class="amount-col"><?= number_format($data['late_deduction'], 2) ?></td></tr>
                    <tr><td>SSS</td><td class="amount-col"><?= number_format($data['sss_contribution'], 2) ?></td></tr>
                    <tr><td>PhilHealth</td><td class="amount-col"><?= number_format($data['philhealth_contribution'], 2) ?></td></tr>
                    <tr><td>Pag-IBIG</td><td class="amount-col"><?= number_format($data['pagibig_contribution'], 2) ?></td></tr>
                    <tr><td>Others</td><td class="amount-col"><?= number_format($data['other_deductions'], 2) ?></td></tr>
                    <tr class="border-top border-dark fw-bold text-danger">
                        <td>TOTAL DEDUCTIONS</td>
                        <td class="amount-col"><?= number_format($data['total_deductions'], 2) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="alert alert-dark mt-3 text-center fw-bold fs-4">
            NET PAY: ₱<?= number_format($data['net_pay'], 2) ?>
        </div>
    </div>
</body>
</html>