<?php
include 'sidebar.php';
include 'db_connection.php';

// Get all payroll records joining with employee names
$query = "SELECT p.*, e.first_name, e.last_name, e.department 
          FROM payrolls p 
          JOIN employees e ON p.employee_id = e.id 
          ORDER BY p.payroll_period_end DESC, e.last_name ASC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payroll History</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container { margin-left: 300px; padding: 40px; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #d9534f; color: white; }
        tr:hover { background-color: #f1f1f1; }
        .btn-generate { background: #5cb85c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Payroll History</h1>
        
        <!-- This button will eventually trigger the generator -->
        <a href="run_payroll.php" class="btn-generate">+ Generate New Payroll</a>

        <table>
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Days Worked</th>
                    <th>Gross Pay</th>
                    <th>Deductions</th>
                    <th>Net Pay</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['payroll_period_start'] . ' to ' . $row['payroll_period_end']; ?></td>
                        <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                        <td><?php echo $row['department']; ?></td>
                        <td><?php echo $row['days_worked']; ?></td>
                        <td>₱<?php echo number_format($row['gross_pay'], 2); ?></td>
                        <td style="color: red;">-₱<?php echo number_format($row['total_deductions'], 2); ?></td>
                        <td style="font-weight: bold; color: green;">₱<?php echo number_format($row['net_pay'], 2); ?></td>
                        <td><?php echo $row['payment_status']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center;">No payroll records found. Click Generate to start.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>