<?php
/**
 * Auto-Calculate Payroll from Biometric Attendance (CORRECTED VERSION)
 * Philippine Labor Code Compliant
 * Returns: days worked, total hours, overtime, late hours, etc.
 */

header('Content-Type: application/json');
require_once 'db_connection.php';

$employee_id = (int)($_GET['employee_id'] ?? 0);
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

if (!$employee_id || !$start_date || !$end_date) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// Get employee details
$stmt = $conn->prepare("
    SELECT id, employee_id, first_name, last_name, daily_rate, shift_id
    FROM employees 
    WHERE id = ?
");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

if (!$employee) {
    echo json_encode(['error' => 'Employee not found']);
    exit;
}

// Get shift schedule (work hours per day)
$shift_hours = 8; // Default
$expected_time_in = '09:00:00'; // Default shift start
$expected_time_out = '17:00:00'; // Default shift end
$shift = null;

if ($employee['shift_id']) {
    $shift_stmt = $conn->prepare("SELECT time_in, time_out FROM shifts WHERE id = ?");
    $shift_stmt->bind_param("i", $employee['shift_id']);
    $shift_stmt->execute();
    $shift = $shift_stmt->get_result()->fetch_assoc();
    
    if ($shift) {
        $time_in = strtotime($shift['time_in']);
        $time_out = strtotime($shift['time_out']);
        $shift_hours = ($time_out - $time_in) / 3600;
        $expected_time_in = $shift['time_in'];
        $expected_time_out = $shift['time_out'];
    }
}

// Get attendance records for the period
$stmt = $conn->prepare("
    SELECT 
        DATE(created_at) as work_date,
        clock_in,
        clock_out,
        TIMESTAMPDIFF(MINUTE, clock_in, clock_out) as total_minutes,
        DAYOFWEEK(DATE(created_at)) as day_of_week
    FROM attendance_logs
    WHERE employee_id = ?
      AND DATE(created_at) BETWEEN ? AND ?
      AND clock_in IS NOT NULL
      AND clock_out IS NOT NULL
    ORDER BY created_at ASC
");

$stmt->bind_param("iss", $employee_id, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Calculate hourly rate
$hourly_rate = $employee['daily_rate'] / $shift_hours;

// Initialize totals
$days_worked = 0;
$total_hours_worked = 0;
$regular_hours = 0;
$overtime_hours = 0;
$rest_day_hours = 0;
$rest_day_ot_hours = 0;
$late_minutes = 0;
$undertime_minutes = 0;
$night_diff_hours = 0;
$attendance_details = [];

while ($row = $result->fetch_assoc()) {
    $days_worked++;
    
    // Calculate hours worked this day
    $minutes = $row['total_minutes'];
    $hours = $minutes / 60;
    $total_hours_worked += $hours;
    
    // Check if rest day (Sunday = 1, Saturday = 7)
    $is_rest_day = ($row['day_of_week'] == 1); // Sunday
    
    // Calculate regular vs overtime hours
    if ($hours <= $shift_hours) {
        // Worked within shift hours
        if ($is_rest_day) {
            $rest_day_hours += $hours;
        } else {
            $regular_hours += $hours;
        }
    } else {
        // Worked overtime
        if ($is_rest_day) {
            $rest_day_hours += $shift_hours;
            $rest_day_ot_hours += ($hours - $shift_hours);
        } else {
            $regular_hours += $shift_hours;
            $overtime_hours += ($hours - $shift_hours);
        }
    }
    
    // Calculate late (minutes after expected time_in)
    $actual_time_in = strtotime($row['clock_in']);
    $expected_in = strtotime($row['work_date'] . ' ' . $expected_time_in);
    
    $late_this_day = 0;
    if ($actual_time_in > $expected_in) {
        $late_this_day = ($actual_time_in - $expected_in) / 60;
        $late_minutes += $late_this_day;
    }
    
    // Calculate undertime (left before expected time_out)
    $actual_time_out = strtotime($row['clock_out']);
    $expected_out = strtotime($row['work_date'] . ' ' . $expected_time_out);
    
    $undertime_this_day = 0;
    if ($actual_time_out < $expected_out) {
        $undertime_this_day = ($expected_out - $actual_time_out) / 60;
        $undertime_minutes += $undertime_this_day;
    }
    
    // Calculate night differential (10pm - 6am = +10%)
    // Simplified: Count hours between 22:00 and 06:00
    $clock_in_hour = (int)date('H', $actual_time_in);
    $clock_out_hour = (int)date('H', $actual_time_out);
    
    $night_hours = 0;
    if ($clock_out_hour >= 22 || $clock_out_hour <= 6 || 
        $clock_in_hour >= 22 || $clock_in_hour <= 6) {
        // Simplified: If any part of shift is in night hours
        if ($clock_in_hour >= 22 || $clock_in_hour <= 6) {
            $night_hours = min($hours, 2); // Approximate
        }
    }
    $night_diff_hours += $night_hours;
    
    $attendance_details[] = [
        'date' => $row['work_date'],
        'clock_in' => $row['clock_in'],
        'clock_out' => $row['clock_out'],
        'hours' => round($hours, 2),
        'overtime' => $hours > $shift_hours ? round($hours - $shift_hours, 2) : 0,
        'late_minutes' => round($late_this_day),
        'undertime_minutes' => round($undertime_this_day),
        'is_rest_day' => $is_rest_day
    ];
}

// ============================================
// PAY CALCULATIONS (Philippine Labor Code)
// ============================================

// 1. Basic Pay (pay for actual regular hours worked)
$basic_pay = ($regular_hours * $hourly_rate);

// 2. Overtime Pay (1.25x for regular days)
$overtime_pay = ($overtime_hours * $hourly_rate * 1.25);

// 3. Rest Day Pay (1.30x)
$rest_day_pay = ($rest_day_hours * $hourly_rate * 1.30);

// 4. Rest Day Overtime (1.69x = 1.30 × 1.30)
$rest_day_ot_pay = ($rest_day_ot_hours * $hourly_rate * 1.69);

// 5. Night Differential (+10% of hourly rate)
$night_diff_pay = ($night_diff_hours * $hourly_rate * 0.10);

// 6. Gross Pay
$gross_pay = $basic_pay + $overtime_pay + $rest_day_pay + $rest_day_ot_pay + $night_diff_pay;

// ============================================
// DEDUCTIONS
// ============================================

// Late Deduction (deduct actual late hours from pay)
$late_hours = $late_minutes / 60;
$late_deduction = $late_hours * $hourly_rate;

// Undertime Deduction (already accounted for in basic_pay calculation)
// We pay only for hours worked, so no additional deduction needed
$undertime_deduction = 0;

// ============================================
// GOVERNMENT CONTRIBUTIONS (2026 Rates)
// ============================================

// SSS Contribution Table (Employee Share 4.5%)
function calculate_sss($monthly_salary) {
    // SSS 2026 Contribution Table (simplified brackets)
    if ($monthly_salary <= 4250) return 191.25;
    if ($monthly_salary <= 4750) return 213.75;
    if ($monthly_salary <= 5250) return 236.25;
    if ($monthly_salary <= 5750) return 258.75;
    if ($monthly_salary <= 6250) return 281.25;
    if ($monthly_salary <= 6750) return 303.75;
    if ($monthly_salary <= 7250) return 326.25;
    if ($monthly_salary <= 7750) return 348.75;
    if ($monthly_salary <= 8250) return 371.25;
    if ($monthly_salary <= 8750) return 393.75;
    if ($monthly_salary <= 9250) return 416.25;
    if ($monthly_salary <= 9750) return 438.75;
    if ($monthly_salary <= 10250) return 461.25;
    if ($monthly_salary <= 10750) return 483.75;
    if ($monthly_salary <= 11250) return 506.25;
    if ($monthly_salary <= 11750) return 528.75;
    if ($monthly_salary <= 12250) return 551.25;
    if ($monthly_salary <= 12750) return 573.75;
    if ($monthly_salary <= 13250) return 596.25;
    if ($monthly_salary <= 13750) return 618.75;
    if ($monthly_salary <= 14250) return 641.25;
    if ($monthly_salary <= 14750) return 663.75;
    if ($monthly_salary <= 15250) return 686.25;
    if ($monthly_salary <= 15750) return 708.75;
    if ($monthly_salary <= 16250) return 731.25;
    if ($monthly_salary <= 16750) return 753.75;
    if ($monthly_salary <= 17250) return 776.25;
    if ($monthly_salary <= 17750) return 798.75;
    if ($monthly_salary <= 18250) return 821.25;
    if ($monthly_salary <= 18750) return 843.75;
    if ($monthly_salary <= 19250) return 866.25;
    if ($monthly_salary <= 19750) return 888.75;
    if ($monthly_salary <= 20250) return 911.25;
    if ($monthly_salary <= 20750) return 933.75;
    if ($monthly_salary <= 21250) return 956.25;
    if ($monthly_salary <= 21750) return 978.75;
    if ($monthly_salary <= 22250) return 1001.25;
    if ($monthly_salary <= 22750) return 1023.75;
    if ($monthly_salary <= 23250) return 1046.25;
    if ($monthly_salary <= 23750) return 1068.75;
    if ($monthly_salary <= 24250) return 1091.25;
    if ($monthly_salary <= 24750) return 1113.75;
    if ($monthly_salary <= 25250) return 1136.25;
    if ($monthly_salary <= 25750) return 1158.75;
    if ($monthly_salary <= 26250) return 1181.25;
    if ($monthly_salary <= 26750) return 1203.75;
    if ($monthly_salary <= 27250) return 1226.25;
    if ($monthly_salary <= 27750) return 1248.75;
    if ($monthly_salary <= 28250) return 1271.25;
    if ($monthly_salary <= 28750) return 1293.75;
    if ($monthly_salary <= 29250) return 1316.25;
    if ($monthly_salary <= 29750) return 1338.75;
    return 1350.00; // Maximum (salary >= 30,000)
}

// PhilHealth (2.5% employee share, max ₱5,000 salary base)
function calculate_philhealth($monthly_salary) {
    $base = min($monthly_salary, 100000); // 2026 cap
    return $base * 0.025; // 2.5% employee share
}

// Pag-IBIG (1-2% based on salary, max ₱100)
function calculate_pagibig($monthly_salary) {
    if ($monthly_salary <= 1500) {
        return $monthly_salary * 0.01; // 1%
    } else {
        return min($monthly_salary * 0.02, 100); // 2%, max ₱100
    }
}

// Estimate monthly salary from gross pay
// Assuming semi-monthly (2 pay periods per month)
$estimated_monthly_salary = $gross_pay * 2;

$sss = calculate_sss($estimated_monthly_salary);
$philhealth = calculate_philhealth($estimated_monthly_salary);
$pagibig = calculate_pagibig($estimated_monthly_salary);

// For semi-monthly payroll, divide monthly contributions by 2
$sss = $sss / 2;
$philhealth = $philhealth / 2;
$pagibig = $pagibig / 2;

$gov_deductions = $sss + $philhealth + $pagibig;

// Total Deductions
$total_deductions = $late_deduction + $undertime_deduction + $gov_deductions;

// Net Pay
$net_pay = $gross_pay - $total_deductions;

// ============================================
// RETURN CALCULATION RESULTS
// ============================================

echo json_encode([
    'success' => true,
    'employee' => [
        'id' => $employee['id'],
        'name' => $employee['first_name'] . ' ' . $employee['last_name'],
        'employee_id' => $employee['employee_id'],
        'daily_rate' => $employee['daily_rate'],
        'hourly_rate' => round($hourly_rate, 2)
    ],
    'period' => [
        'start' => $start_date,
        'end' => $end_date,
        'days' => $days_worked
    ],
    'attendance' => [
        'days_worked' => $days_worked,
        'total_hours' => round($total_hours_worked, 2),
        'regular_hours' => round($regular_hours, 2),
        'overtime_hours' => round($overtime_hours, 2),
        'rest_day_hours' => round($rest_day_hours, 2),
        'rest_day_ot_hours' => round($rest_day_ot_hours, 2),
        'night_diff_hours' => round($night_diff_hours, 2),
        'late_minutes' => round($late_minutes),
        'late_hours' => round($late_minutes / 60, 2),
        'undertime_minutes' => round($undertime_minutes),
        'details' => $attendance_details
    ],
    'calculation' => [
        'basic_pay' => round($basic_pay, 2),
        'overtime_pay' => round($overtime_pay, 2),
        'rest_day_pay' => round($rest_day_pay, 2),
        'rest_day_ot_pay' => round($rest_day_ot_pay, 2),
        'night_diff_pay' => round($night_diff_pay, 2),
        'gross_pay' => round($gross_pay, 2),
        'late_deduction' => round($late_deduction, 2),
        'undertime_deduction' => round($undertime_deduction, 2),
        'sss' => round($sss, 2),
        'philhealth' => round($philhealth, 2),
        'pagibig' => round($pagibig, 2),
        'gov_deductions' => round($gov_deductions, 2),
        'total_deductions' => round($total_deductions, 2),
        'net_pay' => round($net_pay, 2)
    ],
    'breakdown' => [
        'shift_hours' => $shift_hours,
        'hourly_rate' => round($hourly_rate, 2),
        'ot_rate' => round($hourly_rate * 1.25, 2),
        'rest_day_rate' => round($hourly_rate * 1.30, 2),
        'rest_day_ot_rate' => round($hourly_rate * 1.69, 2),
        'night_diff_rate' => round($hourly_rate * 0.10, 2),
        'estimated_monthly_salary' => round($estimated_monthly_salary, 2)
    ]
]);
?>