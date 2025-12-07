<?php
    include 'sidebar.php'; 
    include 'db_connection.php'; 


$department_query = "SELECT dept_name FROM departments";
$department_result = mysqli_query($conn, $department_query);
$branch_query = "SELECT branch_name FROM branches";
$branch_result = mysqli_query($conn, $branch_query);
$designation_query = "SELECT designation_name FROM designations";
$designation_result = mysqli_query($conn, $designation_query);

if (isset($_GET['id'])) {
    $employee_id = $_GET['id'];

    $sql = "SELECT * FROM employees WHERE employee_id = '$employee_id'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
    } else {
        echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Employee not found',
                    text: 'The employee you are looking for does not exist.',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'employees.php';
                    }
                });
              </script>";
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee_id = $_POST['employee_id']; 
    $employee_name = $_POST['employee_name'];
    $role = $_POST['role'];
    $gender = $_POST['gender'];
    $email = $_POST['email'];
    $dob = $_POST['dob'];
    $mobile_number = $_POST['mobile_number'];
    $current_address = $_POST['current_address'];
    $permanent_address = $_POST['permanent_address'];
    $emergency_contact_name = $_POST['emergency_contact_name'];
    $emergency_contact_number = $_POST['emergency_contact_number'];
    $relationship_to_employee = $_POST['relationship_to_employee'];
    $joining_date = $_POST['joining_date'];
    $registration_date = $_POST['registration_date'];
    $department_name = $_POST['department_name'];
    $branch_name = $_POST['branch_name'];
    $designation_name = $_POST['designation_name'];
    $basic_salary = $_POST['basic_salary'];
    $gross_salary = $_POST['gross_salary'];
    $net_salary = $_POST['net_salary'];
    $sss_number = $_POST['sss_number'];
    $pagibig_number = $_POST['pagibig_number'];
    $philhealth_number = $_POST['philhealth_number'];
    $tin_number = $_POST['tin_number'];
    $bank_name = $_POST['bank_name'];
    $bank_account_number = $_POST['bank_account_number'];

    $update_sql = "UPDATE employees SET 
        employee_name = ?, 
        role = ?, 
        gender = ?, 
        email = ?, 
        dob = ?, 
        mobile_number = ?, 
        current_address = ?, 
        permanent_address = ?, 
        emergency_contact_name = ?, 
        emergency_contact_number = ?, 
        relationship_to_employee = ?, 
        joining_date = ?, 
        registration_date = ?, 
        department_name = ?, 
        branch_name = ?, 
        designation_name = ?, 
        basic_salary = ?, 
        gross_salary = ?, 
        net_salary = ?, 
        sss_number = ?, 
        pagibig_number = ?, 
        philhealth_number = ?, 
        tin_number = ?, 
        bank_name = ?, 
        bank_account_number = ? 
        WHERE employee_id = ?";

    $stmt = $conn->prepare($update_sql);

    $stmt->bind_param("sssssssssssssssssssssssssi", 
        $employee_name, $role, $gender, $email, $dob, 
        $mobile_number, $current_address, $permanent_address, 
        $emergency_contact_name, $emergency_contact_number, 
        $relationship_to_employee, $joining_date, $registration_date, 
        $department_name, $branch_name, $designation_name, 
        $basic_salary, $gross_salary, $net_salary, 
        $sss_number, $pagibig_number, $philhealth_number, 
        $tin_number, $bank_name, $bank_account_number, 
        $employee_id
    );

    if ($stmt->execute()) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Employee updated successfully',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'employees.php';
                }
            });
        </script>";
        die();
    }        

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/png" href="images/logo.png">
    
</head>
<body>
    
<div class="container mt-5">
    <a href="employees.php" class="btn btn-secondary mb-4">Back</a>
    <div id="editEmployeeModal" class="modal">
        <div class="modal-content">
            <h2>Update Employee Information</h2>

            <form id="updateEmployeeForm" method="POST">
                <input type="hidden" name="employee_id" value="<?php echo $row['employee_id']; ?>">

                <!-- Basic Information -->
                <h2>Basic Information</h2>
                <label>Employee Name:</label>
                <input type="text" class="form-control" name="employee_name" value="<?php echo $row['employee_name']; ?>" required style="width: 100%;"><br>

                <label>Role:</label>
                <input type="text" class="form-control" name="role" value="<?php echo $row['role']; ?>" required style="width: 100%;"><br>

                <label>Gender:</label><br>
                <input type="text" class="form-control" name="gender" value="<?php echo $row['gender']; ?>" required style="width: 100%;"><br>

                <label>Email:</label><br>
                <input type="email" class="form-control" name="email" value="<?php echo $row['email']; ?>" required style="width: 100%;"><br>

                <label>Date of Birth:</label><br>
                <input type="date" class="form-control" name="dob" value="<?php echo $row['dob']; ?>" required style="width: 100%;"><br>

                <label>Mobile Number:</label><br>
                <input type="text" class="form-control" name="mobile_number" value="<?php echo $row['mobile_number']; ?>" required style="width: 100%;"><br>

                <!-- Address & Emergency Contact -->
                <h2>Address & Emergency Contact</h2>
                <label>Current Address:</label><br>
                <input type="text" class="form-control" name="current_address" value="<?php echo $row['current_address']; ?>" required style="width: 100%;"><br>

                <label>Permanent Address:</label><br>
                <input type="text" class="form-control" name="permanent_address" value="<?php echo $row['permanent_address']; ?>" required style="width: 100%;"><br>

                <label>Emergency Contact Name:</label><br>
                <input type="text" class="form-control" name="emergency_contact_name" value="<?php echo $row['emergency_contact_name']; ?>" required style="width: 100%;"><br>

                <label>Emergency Contact Number:</label><br>
                <input type="text" class="form-control" name="emergency_contact_number" value="<?php echo $row['emergency_contact_number']; ?>" required style="width: 100%;"><br>

                <label>Relationship to Employee:</label><br>
                <input type="text" class="form-control" name="relationship_to_employee" value="<?php echo $row['relationship_to_employee']; ?>" required style="width: 100%;"><br>

                <!-- Employment Details -->
                <h2>Employment Details</h2>
                <label>Joining Date:</label><br>
                <input type="date" class="form-control" name="joining_date" value="<?php echo $row['joining_date']; ?>" required style="width: 100%;"><br>

                <label>Registration Date:</label><br>
                <input type="date" class="form-control" name="registration_date" value="<?php echo $row['registration_date']; ?>" required style="width: 100%;"><br>

                <label>Department Name:</label><br>
                <select id="department_name" name="department_name" class="form-control" required style="width: 100%;"></select><br>

                <label>Branch Name:</label><br>
                <select id="branch_name" name="branch_name" class="form-control" required style="width: 100%;"></select><br>


                <label>Designation Name:</label><br>
                <select id="designation_name" name="designation_name" class="form-control" required style="width: 100%;"></select><br>

                <!-- Salary & Benefits -->
                <h2>Salary & Benefits</h2>
                <label>Basic Salary:</label><br>
                <input type="number" class="form-control" name="basic_salary" value="<?php echo $row['basic_salary']; ?>" required style="width: 100%;"><br>

                <label>Gross Salary:</label><br>
                <input type="number" class="form-control" name="gross_salary" value="<?php echo $row['gross_salary']; ?>" required style="width: 100%;"><br>

                <label>Net Salary:</label><br>
                <input type="number" class="form-control" name="net_salary" value="<?php echo $row['net_salary']; ?>" required style="width: 100%;"><br>

                <!-- Government & Bank Information -->
                <h2>Government & Bank Information</h2>
                <label>SSS Number:</label><br>
                <input type="text" class="form-control" name="sss_number" value="<?php echo $row['sss_number']; ?>" required style="width: 100%;"><br>

                <label>PAG-IBIG Number:</label><br>
                <input type="text" class="form-control" name="pagibig_number" value="<?php echo $row['pagibig_number']; ?>" required style="width: 100%;"><br>

                <label>PhilHealth Number:</label><br>
                <input type="text" class="form-control" name="philhealth_number" value="<?php echo $row['philhealth_number']; ?>" required style="width: 100%;"><br>

                <label>TIN Number:</label><br>
                <input type="text" class="form-control" name="tin_number" value="<?php echo $row['tin_number']; ?>" required style="width: 100%;"><br>

                <label>Bank Name:</label><br>
                <input type="text" class="form-control" name="bank_name" value="<?php echo $row['bank_name']; ?>" required style="width: 100%;"><br>

                <label>Bank Account Number:</label><br>
                <input type="text" class="form-control" name="bank_account_number" value="<?php echo $row['bank_account_number']; ?>" required style="width: 100%;"><br>

                <!-- Submit Button -->
                <br><button type="submit" class="btn btn-primary btn-block">Update Employee</button>
            </form>
        </div>
    </div>
</div>
        <a href="employees.php">Cancel</a>
            </form>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  fetchData('departments', 'department_name');
  fetchData('branches', 'branch_name');
  fetchData('designations', 'designation_name');
  fetchData('shifts', 'shift_name');
});
function fetchData(type, selectId) {
  fetch(`fetch_designations1.php?type=${type}`)
    .then(response => response.json())
    .then(data => populateDropdown(data, selectId))
    .catch(error => console.error('Error:', error));
}

function populateDropdown(data, selectId) {
  const selectElement = document.getElementById(selectId);

  data.forEach(item => {
    const option = document.createElement('option');
    option.value = item.name;
    option.textContent = item.name;
    selectElement.appendChild(option);
  });
}

</script>
    
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

    body {
        font-family: 'Poppins', sans-serif;
        margin: 0;
        padding: 20px;
        background: linear-gradient(135deg, rgb(92, 92, 92), rgb(92, 92, 92));
        color: #333;
    }

    body.dark-theme {
        background: #181818;
        color: white;
    }

    .container {
        margin-left: 300px;
        padding: 40px;
        max-width: 1200px;
    }

    .form-control {
        background-color: transparent;
        border: 1px solid #ccc;
        padding: 12px;
        margin-bottom: 20px;
        border-radius: 8px;
        transition: background-color 0.3s ease, border 0.3s ease;
    }

    body.dark-theme .form-control {
        background-color: #333;
        color: white;
        border: 1px solid #444;
    }

    .form-control:focus {
        outline: none;
        border-color: #FF6B6B;
    }

    .btn {
        background-color: #FF6B6B;
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        width: 100%;
        font-weight: bold;
        transition: background-color 0.3s ease;
    }

    .btn:hover {
        background-color: #E63946;
    }

    body.dark-theme .btn {
        background-color: #444;
    }

    body.dark-theme .btn:hover {
        background-color: #333;
    }

    label {
        font-weight: 600;
    }

    body.dark-theme label {
        color: white;
    }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg,rgb(92, 92, 92), rgb(92, 92, 92));
            color: #333;
        }

        .container {
            margin-left: 300px;
            padding: 40px;
            max-width: 1200px;
        }

        .card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s;
        }

        .card:hover {
            transform: scale(1.03);
        }

        .title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 26px;
            font-weight: 600;
            color: #2c3e50;
        }

        .title i {
            font-size: 30px;
            color: #FF6B6B;
        }

 
        body.dark-theme .title {
            color: white;
        }
        .modal button:hover {
            background: #E63946;
        }
        body.dark-theme .card, 
        body.dark-theme .table-container table, 
        body.dark-theme .table-container , 
        body.dark-theme .table-container table td {
            background: #333333;
            color: white;
        }

        body.dark-theme .table-container,
        body.dark-theme .table-container table td {
            border-color: #444444;
        }


        body.dark-theme .add-Employee-btn:hover, 
        body.dark-theme .view-btn:hover, 
        body.dark-theme .update-btn:hover, 
        body.dark-theme .delete-btn:hover {
            background: #444444;
        }

        body.dark-theme .dataTables_length label,
        body.dark-theme .dataTables_filter label,
        body.dark-theme .dataTables_info {
            color: white;
        }



    </style>
</body>
</html>


