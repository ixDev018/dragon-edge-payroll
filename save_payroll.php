<?php
// save_payroll.php - UPDATED FOR AUTOMATED PAYROLL
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_id = $conn->real_escape_string($_POST['employee_id']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // --- Find the correct System ID (Primary Key) ---
    $sql = "SELECT id, basic_salary, daily_rate FROM employees 
            WHERE id = '$input_id' OR employee_id = '$input_id' LIMIT 1";
    
    $empQuery = $conn->query($sql);
    
    if ($empQuery->num_rows == 0) {
        die("Error: Employee not found. The system could not find an employee with ID: " . $input_id);
    }

    $empData = $empQuery->fetch_assoc();
    
    // Use the real database ID for the insert
    $real_emp_id = $empData['id']; 
    $daily_rate = $empData['daily_rate'];
    $basic_salary = $empData['basic_salary'];
    
    $hourly_rate = $daily_rate / 8;

    // --- CHECK IF DATA IS FROM AUTOMATED CALCULATOR OR MANUAL ENTRY ---
    $is_automated = isset($_POST['total_hours']); // Only automated form sends total_hours
    
    if ($is_automated) {
        // ✅ AUTOMATED CALCULATION (from attendance)
        $days_worked = floatval($_POST['days_worked']);
        $total_hours = floatval($_POST['total_hours']);
        $ot_hours = floatval($_POST['ot_hours']);
        $late_hours = floatval($_POST['late_hours']);
        
        // Use pre-calculated values from API
        $gross_pay = floatval($_POST['gross_pay']);
        $total_deductions = floatval($_POST['deductions']);
        $net_pay = floatval($_POST['net_pay']);
        
        // Calculate individual components for database
        $basic_pay = $days_worked * $daily_rate;
        $ot_pay = $ot_hours * ($hourly_rate * 1.25);
        $late_deduction = $late_hours * $hourly_rate;
        
        // Government deductions
        $sss = $gross_pay * 0.045;
        $philhealth = 100; // From API calculation
        $pagibig = 200; // From API calculation
        
        $other_deductions = 0; // Automated doesn't include other deductions yet
        
    } else {
        // ✅ MANUAL ENTRY (old method, for backward compatibility)
        $days_worked = floatval($_POST['days_worked']);
        $ot_hours = floatval($_POST['ot_hours'] ?? 0);
        $late_hours = floatval($_POST['late_hours'] ?? 0);
        $other_deductions = floatval($_POST['other_deductions'] ?? 0);
        
        // Calculate earnings
        $basic_pay = $days_worked * $daily_rate;
        $ot_pay = $ot_hours * ($hourly_rate * 1.25);
        $gross_pay = $basic_pay + $ot_pay;
        
        // Calculate deductions
        $late_deduction = $late_hours * $hourly_rate;
        $sss = $gross_pay * 0.045;
        $philhealth = ($basic_salary * 0.05) / 2;
        $pagibig = 100.00;
        
        $total_deductions = $late_deduction + $sss + $philhealth + $pagibig + $other_deductions;
        $net_pay = $gross_pay - $total_deductions;
        
        $total_hours = $days_worked * 8; // Estimate
    }

    // --- INSERT INTO DATABASE ---
    $insert_sql = "INSERT INTO payrolls 
            (employee_id, payroll_period_start, payroll_period_end, basic_salary, daily_rate, 
            days_worked, basic_pay, overtime_hours, overtime_pay, late_hours, late_deduction,
            sss_contribution, philhealth_contribution, pagibig_contribution, other_deductions,
            gross_pay, total_deductions, net_pay, created_at)
            VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($insert_sql);
    
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    // Bind parameters
    $stmt->bind_param("issddddddddddddddd", 
        $real_emp_id, 
        $start_date, 
        $end_date, 
        $basic_salary, 
        $daily_rate,
        $days_worked, 
        $basic_pay, 
        $ot_hours, 
        $ot_pay, 
        $late_hours, 
        $late_deduction,
        $sss, 
        $philhealth, 
        $pagibig, 
        $other_deductions,
        $gross_pay, 
        $total_deductions, 
        $net_pay
    );

    if ($stmt->execute()) {
        // Success - redirect with message
        header("Location: payroll_master.php?msg=success");
        exit;
    } else {
        // Show detailed error
        die("Database Error: " . $stmt->error . "<br><br>SQL: " . $insert_sql);
    }
    
    $stmt->close();
    $conn->close();
} else {
    // Not a POST request
    header("Location: payroll_master.php");
    exit;
}
?>