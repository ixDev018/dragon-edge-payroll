<?php
/**
 * Biometric Integration Test Script
 * Run this to verify your setup is working correctly
 */

require_once 'db_connection.php';

// ANSI color codes for terminal
class Color {
    const GREEN = "\033[32m";
    const RED = "\033[31m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const RESET = "\033[0m";
}

function print_header($text) {
    echo "\n" . Color::BLUE . str_repeat("=", 60) . Color::RESET . "\n";
    echo Color::BLUE . $text . Color::RESET . "\n";
    echo Color::BLUE . str_repeat("=", 60) . Color::RESET . "\n\n";
}

function print_test($name, $passed, $message = "") {
    $status = $passed ? Color::GREEN . "✓ PASS" : Color::RED . "✗ FAIL";
    echo sprintf("%-50s %s" . Color::RESET . "\n", $name, $status);
    if ($message) {
        echo "  " . Color::YELLOW . $message . Color::RESET . "\n";
    }
}

function test_result($passed, $total) {
    echo "\n" . str_repeat("-", 60) . "\n";
    $color = ($passed === $total) ? Color::GREEN : Color::RED;
    echo $color . sprintf("Result: %d/%d tests passed", $passed, $total) . Color::RESET . "\n\n";
}

// =====================================================
// START TESTS
// =====================================================

print_header("DRAGON EDGE BIOMETRIC INTEGRATION TEST");

$passed = 0;
$total = 0;

// Test 1: Database Connection
$total++;
if ($conn && !$conn->connect_error) {
    print_test("Database connection", true);
    $passed++;
} else {
    print_test("Database connection", false, "Cannot connect to database");
}

// Test 2: Check for biometric_devices table
$total++;
$result = $conn->query("SHOW TABLES LIKE 'biometric_devices'");
if ($result && $result->num_rows > 0) {
    print_test("Table: biometric_devices", true);
    $passed++;
} else {
    print_test("Table: biometric_devices", false, "Table does not exist - run database updates");
}

// Test 3: Check for biometric_enrollments table
$total++;
$result = $conn->query("SHOW TABLES LIKE 'biometric_enrollments'");
if ($result && $result->num_rows > 0) {
    print_test("Table: biometric_enrollments", true);
    $passed++;
} else {
    print_test("Table: biometric_enrollments", false, "Table does not exist");
}

// Test 4: Check for biometric_attendance_raw table
$total++;
$result = $conn->query("SHOW TABLES LIKE 'biometric_attendance_raw'");
if ($result && $result->num_rows > 0) {
    print_test("Table: biometric_attendance_raw", true);
    $passed++;
} else {
    print_test("Table: biometric_attendance_raw", false, "Table does not exist");
}

// Test 5: Check for fingerprint_id column in employees
$total++;
$result = $conn->query("SHOW COLUMNS FROM employees LIKE 'fingerprint_id'");
if ($result && $result->num_rows > 0) {
    print_test("Column: employees.fingerprint_id", true);
    $passed++;
} else {
    print_test("Column: employees.fingerprint_id", false, "Column does not exist");
}

// Test 6: Check for stored procedure
$total++;
$result = $conn->query("SHOW PROCEDURE STATUS WHERE Name = 'process_biometric_attendance'");
if ($result && $result->num_rows > 0) {
    print_test("Stored Procedure: process_biometric_attendance", true);
    $passed++;
} else {
    print_test("Stored Procedure: process_biometric_attendance", false, "Procedure does not exist");
}

// Test 7: Check for view
$total++;
$result = $conn->query("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_payrollcapsdb = 'v_biometric_attendance_today'");
if ($result && $result->num_rows > 0) {
    print_test("View: v_biometric_attendance_today", true);
    $passed++;
} else {
    print_test("View: v_biometric_attendance_today", false, "View does not exist");
}

// Test 8: Check if biometric_api.php exists
$total++;
if (file_exists('biometric_api.php')) {
    print_test("File: biometric_api.php", true);
    $passed++;
} else {
    print_test("File: biometric_api.php", false, "File not found");
}

// Test 9: Check if mqtt_bridge.php exists
$total++;
if (file_exists('mqtt_bridge.php')) {
    print_test("File: mqtt_bridge.php", true);
    $passed++;
} else {
    print_test("File: mqtt_bridge.php", false, "File not found");
}

// Test 10: Check if biometric_dashboard.php exists
$total++;
if (file_exists('biometric_dashboard.php')) {
    print_test("File: biometric_dashboard.php", true);
    $passed++;
} else {
    print_test("File: biometric_dashboard.php", false, "File not found");
}

// Test 11: Check if enroll_fingerprint.php exists
$total++;
if (file_exists('enroll_fingerprint.php')) {
    print_test("File: enroll_fingerprint.php", true);
    $passed++;
} else {
    print_test("File: enroll_fingerprint.php", false, "File not found");
}

// Test 12: Check if composer vendor directory exists (for MQTT library)
$total++;
if (file_exists('vendor/autoload.php')) {
    print_test("Composer dependencies", true);
    $passed++;
} else {
    print_test("Composer dependencies", false, "Run: composer require bluerhinos/phpmqtt");
}

// Test 13: Check for active employees
$total++;
$result = $conn->query("SELECT COUNT(*) as count FROM employees WHERE is_active = 1");
$row = $result->fetch_assoc();
if ($row['count'] > 0) {
    print_test("Active employees found", true, "{$row['count']} employees");
    $passed++;
} else {
    print_test("Active employees found", false, "No employees in database");
}

// Test 14: Check for enrolled employees
$total++;
$result = $conn->query("SELECT COUNT(*) as count FROM employees WHERE fingerprint_id IS NOT NULL");
$row = $result->fetch_assoc();
print_test("Enrolled employees", true, "{$row['count']} enrolled");
$passed++;

// Test 15: API endpoint test
$total++;
$api_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/biometric_api.php?endpoint=employees';
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200 && $response) {
    print_test("API endpoint accessible", true, "HTTP 200 OK");
    $passed++;
} else {
    print_test("API endpoint accessible", false, "API not responding");
}

// Final result
test_result($passed, $total);

// =====================================================
// SYSTEM INFO
// =====================================================

print_header("SYSTEM INFORMATION");

echo Color::YELLOW . "PHP Version:" . Color::RESET . " " . phpversion() . "\n";
echo Color::YELLOW . "MySQL Version:" . Color::RESET . " " . $conn->server_info . "\n";

$extensions = ['mysqli', 'json', 'curl', 'sockets'];
echo Color::YELLOW . "\nPHP Extensions:" . Color::RESET . "\n";
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? Color::GREEN . "✓ Loaded" : Color::RED . "✗ Missing";
    echo sprintf("  %-20s %s" . Color::RESET . "\n", $ext, $status);
}

// =====================================================
// RECOMMENDATIONS
// =====================================================

if ($passed < $total) {
    print_header("RECOMMENDATIONS");
    
    if (!extension_loaded('sockets')) {
        echo Color::RED . "→ Install sockets extension for MQTT support\n" . Color::RESET;
    }
    
    if (!file_exists('vendor/autoload.php')) {
        echo Color::RED . "→ Run: composer require bluerhinos/phpmqtt\n" . Color::RESET;
    }
    
    $missing_tables = [];
    foreach (['biometric_devices', 'biometric_enrollments', 'biometric_attendance_raw'] as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if (!$result || $result->num_rows === 0) {
            $missing_tables[] = $table;
        }
    }
    
    if (!empty($missing_tables)) {
        echo Color::RED . "→ Run the database update SQL script\n" . Color::RESET;
        echo "  Missing tables: " . implode(", ", $missing_tables) . "\n";
    }
    
    echo "\n";
} else {
    print_header("✓ ALL TESTS PASSED!");
    echo Color::GREEN . "Your biometric system is ready to use!\n\n" . Color::RESET;
    echo "Next steps:\n";
    echo "1. Start MQTT Bridge: " . Color::YELLOW . "php mqtt_bridge.php\n" . Color::RESET;
    echo "2. Upload ESP32 code\n";
    echo "3. Enroll employees: " . Color::BLUE . "http://localhost/payrollsystemcaps/enroll_fingerprint.php\n" . Color::RESET;
    echo "4. View dashboard: " . Color::BLUE . "http://localhost/payrollsystemcaps/biometric_dashboard.php\n" . Color::RESET;
    echo "\n";
}

// =====================================================
// QUICK STATS
// =====================================================

print_header("QUICK STATS");

$stats = [
    "Total Employees" => "SELECT COUNT(*) FROM employees WHERE is_active = 1",
    "Enrolled Employees" => "SELECT COUNT(*) FROM employees WHERE fingerprint_id IS NOT NULL AND is_active = 1",
    "Pending Enrollment" => "SELECT COUNT(*) FROM employees WHERE fingerprint_id IS NULL AND is_active = 1",
    "Today's Attendance" => "SELECT COUNT(DISTINCT employee_id) FROM attendance_logs WHERE attendance_date = CURDATE()",
    "Total Devices" => "SELECT COUNT(*) FROM biometric_devices",
    "Online Devices" => "SELECT COUNT(*) FROM biometric_devices WHERE status = 'online'"
];

foreach ($stats as $label => $query) {
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_row();
        echo sprintf("%-25s %s\n", $label . ":", Color::BLUE . $row[0] . Color::RESET);
    }
}

echo "\n";