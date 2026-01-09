<?php
require 'db_connection.php';
date_default_timezone_set('Asia/Manila');

function computePayroll($conn, $employeeId, $fromDate, $toDate) {
    // 1. Get Employee Details (Using correct column names from your DB)
    // We link using 'id' (int) not 'employee_id' (string) because payrolls.employee_id is BIGINT
    $stmt = $conn->prepare("SELECT id, first_name, last_name, basic_salary, daily_rate FROM employees WHERE id = ?");
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $emp = $stmt->get_result()->fetch_assoc();
    
    if (!$emp) {
        return ['error' => "Employee ID $employeeId not found"];
    }

    $emp_db_id = $emp['id'];
    $daily_rate = $emp['daily_rate'];
    // Standard hourly rate formula: Daily Rate / 8 hours
    $hourly_rate = $daily_rate / 8; 

    // 2. Calculate Attendance from Logs
    // We sum up the hours directly from your 'attendance_logs' table
    $sql = "SELECT 
                COUNT(DISTINCT DATE(attendance_date)) as days_worked,
                SUM(total_hours_worked) as total_hours,
                SUM(overtime_minutes) as total_ot_mins,
                SUM(late_minutes) as total_late_mins
            FROM attendance_logs 
            WHERE employee_id = ? 
            AND attendance_date BETWEEN ? AND ?";
            
    $s = $conn->prepare($sql);
    $s->bind_param("iss", $emp_db_id, $fromDate, $toDate);
    $s->execute();
    $logs = $s->get_result()->fetch_assoc();

    // 3. The Math (Using your Database's Logic)
    $days_worked = $logs['days_worked'] ?? 0;
    $ot_hours    = ($logs['total_ot_mins'] ?? 0) / 60;
    $late_hours  = ($logs['total_late_mins'] ?? 0) / 60;

    // A. Earnings
    $basic_pay    = $days_worked * $daily_rate;
    $overtime_pay = $ot_hours * ($hourly_rate * 1.25); // Assumes 125% OT rate

    // B. Deductions
    $late_deduction = $late_hours * $hourly_rate;
    
    // Hardcoded Government Deductions (As per your request for 'No Deduction Table')
    // You can adjust these numbers later
    $sss = 500.00;       
    $philhealth = 300.00; 
    $pagibig = 100.00;   
    $tax = 0.00;         // Add tax logic here if needed

    $total_deductions = $late_deduction + $sss + $philhealth + $pagibig + $tax;

    // C. Finals
    $gross_pay = $basic_pay + $overtime_pay;
    $net_pay   = $gross_pay - $total_deductions;

    // 4. Save to 'payrolls' Table (Matching image_f87e85.png)
    $ins = $conn->prepare("INSERT INTO payrolls 
        (employee_id, payroll_period_start, payroll_period_end, 
         basic_salary, daily_rate, days_worked, 
         basic_pay, overtime_hours, overtime_pay, 
         late_hours, late_deduction, 
         sss_contribution, philhealth_contribution, pagibig_contribution, withholding_tax,
         gross_pay, total_deductions, net_pay, payment_status, created_at) 
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");

    $ins->bind_param("issddiddiddddddddd", 
        $emp_db_id, $fromDate, $toDate,
        $emp['basic_salary'], $daily_rate, $days_worked,
        $basic_pay, $ot_hours, $overtime_pay,
        $late_hours, $late_deduction,
        $sss, $philhealth, $pagibig, $tax,
        $gross_pay, $total_deductions, $net_pay
    );

    if ($ins->execute()) {
        return ['success' => true, 'net_pay' => $net_pay];
    } else {
        return ['error' => $conn->error];
    }
}
?>