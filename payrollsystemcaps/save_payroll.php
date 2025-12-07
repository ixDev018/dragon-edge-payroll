<?php
// save_payroll.php
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_id = $conn->real_escape_string($_POST['employee_id']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // --- SMART FIX: Find the correct System ID (Primary Key) ---
    // We check if the input matches the 'id' OR the 'employee_id' text code
    $sql = "SELECT id, basic_salary, daily_rate FROM employees 
            WHERE id = '$input_id' OR employee_id = '$input_id' LIMIT 1";
    
    $empQuery = $conn->query($sql);
    
    if ($empQuery->num_rows == 0) {
        die("Error: Employee not found. The system could not find an employee with ID: " . $input_id);
    }

    $empData = $empQuery->fetch_assoc();
    
    // THIS IS THE KEY FIX: We use the real database ID for the insert
    $real_emp_id = $empData['id']; 
    $daily_rate = $empData['daily_rate'];
    $basic_salary = $empData['basic_salary'];
    
    $hourly_rate = $daily_rate / 8;

    // 2. Get Inputs
    $days_worked = floatval($_POST['days_worked']);
    $ot_hours = floatval($_POST['ot_hours']);
    $late_hours = floatval($_POST['late_hours']);
    
    // 3. Earnings Calculation
    $basic_pay = $days_worked * $daily_rate;
    $ot_pay = $ot_hours * ($hourly_rate * 1.25); 
    $gross_pay = $basic_pay + $ot_pay;

    // 4. Deductions
    $late_deduction = $late_hours * $hourly_rate;
    
    // Gov Deductions
    $sss = $gross_pay * 0.045; 
    $philhealth = ($basic_salary * 0.05) / 2; 
    $pagibig = 100.00; 
    
    $other_deductions = floatval($_POST['other_deductions']);
    
    $total_deductions = $late_deduction + $sss + $philhealth + $pagibig + $other_deductions;
    $net_pay = $gross_pay - $total_deductions;

    // 5. Insert (Using the corrected $real_emp_id)
    $insert_sql = "INSERT INTO payrolls 
            (employee_id, payroll_period_start, payroll_period_end, basic_salary, daily_rate, 
            days_worked, basic_pay, overtime_hours, overtime_pay, late_hours, late_deduction,
            sss_contribution, philhealth_contribution, pagibig_contribution, other_deductions,
            gross_pay, total_deductions, net_pay, created_at)
            VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($insert_sql);
    
    // Bind parameters (Note: 'i' for the first ID)
    $stmt->bind_param("issddddddddddddddd", 
        $real_emp_id, $start_date, $end_date, $basic_salary, $daily_rate,
        $days_worked, $basic_pay, $ot_hours, $ot_pay, $late_hours, $late_deduction,
        $sss, $philhealth, $pagibig, $other_deductions,
        $gross_pay, $total_deductions, $net_pay
    );

    if ($stmt->execute()) {
        header("Location: payroll_master.php?msg=success");
    } else {
        // If it fails, show the specific SQL error
        echo "Database Error: " . $conn->error;
    }
}
?>