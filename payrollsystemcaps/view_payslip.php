<?php

include 'db_connection.php';

session_start();

$employee_id = $_GET['employee_id'] ?? $_SESSION['employee_id'] ?? null;
if (!$employee_id) {
    die("Employee ID missing.");
}

$sql = "SELECT e.employee_name, e.designation_name, e.department_name, p.* FROM employees e JOIN payroll p ON e.employee_id = p.employee_id WHERE e.employee_id = ? ORDER BY p.date_generated DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$payslip = $result->fetch_assoc();

if (!$payslip) {
    die("No payroll record found for this employee.");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip for <?= htmlspecialchars($payslip['employee_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="payslip-card" id="payslipArea">
        <div class="company-header">
            <img src="company_logo.png" alt="Company Logo">
            <h4><strong>Your Company Name</strong></h4>
            <small>Address: 123 Business St., Metro Manila, Philippines</small><br>
            <small>Tel: (02) 1234-5678 | Email: hr@yourcompany.com</small>
        </div>

        <h2>Payslip</h2>

        <div class="mb-4">
            <strong>Employee Name:</strong> <?= htmlspecialchars($payslip['employee_name']) ?><br>
            <strong>Designation:</strong> <?= htmlspecialchars($payslip['designation_name']) ?><br>
            <strong>Department:</strong> <?= htmlspecialchars($payslip['department_name']) ?><br>
            <strong>Payroll Period:</strong> <?= htmlspecialchars($payslip['payroll_period']) ?><br>
            <strong>Date Generated:</strong> <?= date('F d, Y', strtotime($payslip['date_generated'])) ?>
        </div>

        <table class="table table-bordered">
            <thead class="table-light">
                <tr><th colspan="2" class="text-center">Earnings & Deductions</th></tr>
            </thead>
            <tbody>
                <tr><td>Basic Pay</td><td>₱<?= number_format($payslip['gross_salary'], 2) ?></td></tr>
                <tr><td>Paid Leaves</td><td>₱<?= number_format($payslip['paid_leave_days'] * 500, 2) ?></td></tr>
                <tr><td>Holiday Pay</td><td>₱<?= number_format($payslip['holiday_days'] * 1000, 2) ?></td></tr>
                <tr><td>Deductions</td><td>- ₱<?= number_format($payslip['deductions'], 2) ?></td></tr>
                <tr class="table-primary fw-bold">
                    <td>Net Salary</td><td>₱<?= number_format($payslip['net_salary'], 2) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="mt-5 text-center">
            <p><strong>_________________________</strong><br>Authorized Signature</p>
        </div>
    </div>

    <div class="text-center mt-4 btn-group">
        <button onclick="window.print()" class="btn btn-primary">
            🖨 Print Payslip
        </button>
        <a href="generate_payslip_pdf.php?employee_id=<?= $employee_id ?>" class="btn btn-danger">
            📄 Download PDF
        </a>
        <a href="export_payroll_excel.php?employee_id=<?= $employee_id ?>" class="btn btn-success">
            📊 Export Excel
        </a>
    </div>
</body>
</html>