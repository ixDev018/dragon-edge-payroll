<?php

    include 'db_connection.php'; 

if (isset($_GET['id'])) {
    $employee_id = $_GET['id'];
    
    $sql = "SELECT * FROM employees WHERE employee_id = '$employee_id'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "<h2>Basic Information</h2>";
        echo "<p><strong>Employee ID:</strong> " . $row['employee_id'] . "</p>";
        echo "<p><strong>Employee Name:</strong> " . $row['employee_name'] . "</p>";
        echo "<p><strong>Role:</strong> " . $row['role'] . "</p>";
        echo "<p><strong>Gender:</strong> " . $row['gender'] . "</p>";
        echo "<p><strong>Email:</strong> " . $row['email'] . "</p>";
        echo "<p><strong>Date of Birth:</strong> " . $row['dob'] . "</p>";
        echo "<p><strong>Mobile Number:</strong> " . $row['mobile_number'] . "</p>";

        echo "<h2>Address & Emergency Contact</h2>";
        echo "<p><strong>Current Address:</strong> " . $row['current_address'] . "</p>";
        echo "<p><strong>Permanent Address:</strong> " . $row['permanent_address'] . "</p>";
        echo "<p><strong>Emergency Contact Name:</strong> " . $row['emergency_contact_name'] . "</p>";
        echo "<p><strong>Emergency Contact Number:</strong> " . $row['emergency_contact_number'] . "</p>";
        echo "<p><strong>Relationship to Employee:</strong> " . $row['relationship_to_employee'] . "</p>";

        echo "<h2>Employment Details</h2>";
        echo "<p><strong>Joining Date:</strong> " . $row['joining_date'] . "</p>";
        echo "<p><strong>Registration Date:</strong> " . $row['registration_date'] . "</p>";
        echo "<p><strong>Department Name:</strong> " . $row['department_name'] . "</p>";
        echo "<p><strong>Branch Name:</strong> " . $row['branch_name'] . "</p>";
        echo "<p><strong>Designation Name:</strong> " . $row['designation_name'] . "</p>";

        echo "<h2>Salary & Benefits</h2>";
        echo "<p><strong>Basic Salary:</strong> " . $row['basic_salary'] . "</p>";
        echo "<p><strong>Gross Salary:</strong> " . $row['gross_salary'] . "</p>";
        echo "<p><strong>Net Salary:</strong> " . $row['net_salary'] . "</p>";

        echo "<h2>Government & Bank Information</h2>";
        echo "<p><strong>SSS Number:</strong> " . $row['sss_number'] . "</p>";
        echo "<p><strong>PAG-IBIG Number:</strong> " . $row['pagibig_number'] . "</p>";
        echo "<p><strong>PhilHealth Number:</strong> " . $row['philhealth_number'] . "</p>";
        echo "<p><strong>TIN Number:</strong> " . $row['tin_number'] . "</p>";
        echo "<p><strong>Bank Name:</strong> " . $row['bank_name'] . "</p>";
        echo "<p><strong>Bank Account Number:</strong> " . $row['bank_account_number'] . "</p>";
            } else {
                echo "No details found.";
            }
        }

$conn->close();
?>
