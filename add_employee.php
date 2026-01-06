<?php
session_start();
include 'db_connection.php'; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$success_msg = "";
$error_msg = "";

// Initialize variables for the form (Empty by default)
$id = "";
$first_name = "";
$middle_name = "";
$last_name = "";
$email = "";
$phone = "";
$department = "";
$position = "";
$date_hired = "";
$sss = "";
$pagibig = "";
$philhealth = "";
$tin = "";
$basic_salary = "";

// Account variables
$user_role = "employee"; // Default
$has_account = false;

$is_edit = false;
$form_title = "Add New Employee";
$btn_text = "Register Employee";

// --- FETCH DEPARTMENTS ---
$departments_list = [];
try {
    $dept_res = $conn->query("SELECT name FROM departments ORDER BY name ASC");
    if ($dept_res) {
        while ($row = $dept_res->fetch_assoc()) {
            $departments_list[] = htmlspecialchars($row['name']);
        }
    }
} catch (Exception $e) {
    // If the departments table doesn't exist or query fails, the list remains empty.
}


// 1. GET Logic: Check if we are in "Edit Mode"
if (isset($_GET['id'])) {
    $is_edit = true;
    $form_title = "Edit Employee Details";
    $btn_text = "Update Employee";
    $id = $_GET['id'];

    // Fetch Employee Details
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $first_name = $row['first_name'];
        $middle_name = $row['middle_name'];
        $last_name = $row['last_name'];
        $email = $row['email'];
        $phone = $row['phone'];
        $department = $row['department'];
        $position = $row['position'];
        $date_hired = $row['date_hired'];
        $sss = $row['sss_number'];
        $pagibig = $row['pagibig_number'];
        $philhealth = $row['philhealth_number'];
        $tin = $row['tin_number'];
        $basic_salary = $row['basic_salary'];
    }
    $stmt->close();

    // Fetch Linked User Account Details (Role)
    $user_stmt = $conn->prepare("SELECT role FROM users WHERE employee_id = ?");
    $user_stmt->bind_param("i", $id);
    $user_stmt->execute();
    $user_res = $user_stmt->get_result();
    if ($user_row = $user_res->fetch_assoc()) {
        $user_role = $user_row['role'];
        $has_account = true;
    }
    $user_stmt->close();
}

// 2. POST Logic: Handle Form Submission (Insert or Update)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture Inputs
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['mobile_number'];
    
    $department = $_POST['department_name']; 
    $position = $_POST['designation_name']; 
    $date_hired = $_POST['joining_date'];
    
    $sss = $_POST['sss_number'];
    $pagibig = $_POST['pagibig_number'];
    $philhealth = $_POST['philhealth_number'];
    $tin = $_POST['tin_number'];
    
    $basic_salary = $_POST['basic_salary'];
    $daily_rate = $basic_salary / 22; // Auto-calc

    // Check if we are updating an existing record
    if (isset($_POST['update_id']) && !empty($_POST['update_id'])) {
        // --- UPDATE LOGIC ---
        $update_id = $_POST['update_id'];
        
        // 1. Update Employee Table
        $sql = "UPDATE employees SET 
                first_name=?, middle_name=?, last_name=?, email=?, phone=?, 
                department=?, position=?, date_hired=?, 
                sss_number=?, pagibig_number=?, philhealth_number=?, tin_number=?, 
                basic_salary=?, daily_rate=?, updated_at=NOW() 
                WHERE id=?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssssddi", 
            $first_name, $middle_name, $last_name, $email, $phone, 
            $department, $position, $date_hired, 
            $sss, $pagibig, $philhealth, $tin, 
            $basic_salary, $daily_rate, $update_id
        );

        if ($stmt->execute()) {
            $success_msg = "Employee details updated successfully!";
            $is_edit = true; 
            
            // 2. Update User Account (Sync Email & Role & Password)
            $new_role = $_POST['account_role'] ?? 'employee';
            $reset_pass = $_POST['reset_password'] ?? '';
            $full_name = "$first_name $last_name";

            // If user typed a new password, hash it.
            if (!empty($reset_pass)) {
                $hashed_pw = password_hash($reset_pass, PASSWORD_DEFAULT);
                $u_sql = "UPDATE users SET name=?, email=?, role=?, password=?, updated_at=NOW() WHERE employee_id=?";
                $u_stmt = $conn->prepare($u_sql);
                $u_stmt->bind_param("ssssi", $full_name, $email, $new_role, $hashed_pw, $update_id);
                $u_stmt->execute();
                $success_msg .= " Account password reset!";
            } else {
                // Just update details, keep old password
                $u_sql = "UPDATE users SET name=?, email=?, role=?, updated_at=NOW() WHERE employee_id=?";
                $u_stmt = $conn->prepare($u_sql);
                $u_stmt->bind_param("sssi", $full_name, $email, $new_role, $update_id);
                $u_stmt->execute();
            }

        } else {
            $error_msg = "Error updating record: " . $conn->error;
        }
        $stmt->close();

    } else {
        // --- INSERT LOGIC (Add New) ---
        
        // Step 1: Check if email already exists in 'users' or 'employees' table to prevent crash
        $check_stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $error_msg = "Error: The email address '$email' is already registered. Please use a different email.";
            $check_stmt->close();
        } else {
            $check_stmt->close();
            
            // Proceed with Insert
            $shift_id = 1; 
            $employment_status = 'Probationary';
            
            $year = date('Y');
            $rand = rand(1000, 9999);
            $employee_id_str = "EMP-$year-$rand";

            $sql = "INSERT INTO employees 
                    (employee_id, first_name, middle_name, last_name, email, phone, 
                    department, position, shift_id, date_hired, employment_status,
                    sss_number, pagibig_number, philhealth_number, tin_number,
                    basic_salary, daily_rate, is_active, created_at)
                    VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssssssssssdd", 
                $employee_id_str, $first_name, $middle_name, $last_name, $email, $phone,
                $department, $position, $shift_id, $date_hired, $employment_status,
                $sss, $pagibig, $philhealth, $tin,
                $basic_salary, $daily_rate
            );

            if ($stmt->execute()) {
                $db_id = $stmt->insert_id;
                
                // Create Login Account
                $generated_password = "emp-$db_id-" . date('md'); 
                $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);
                $full_name = "$first_name $last_name";

                $insertAccount = "INSERT INTO users (employee_id, name, email, password, role, created_at, updated_at)
                                 VALUES ('$db_id', '$full_name', '$email', '$hashed_password', 'employee', NOW(), NOW())";

                if ($conn->query($insertAccount) === TRUE) {
                    // Send Email
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'dragon.edge.group.company@gmail.com'; 
                        $mail->Password = 'otqo fjoy iypf juqw'; 
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        $mail->setFrom('dragon.edge.group.company@gmail.com', 'Dragon Edge Group');
                        $mail->addAddress($email, $full_name); 

                        $mail->isHTML(true); 
                        $mail->Subject = 'Welcome to Dragon Edge Group';
                        $mail->Body     = "
                            <html>
                            <body>
                                <h2 style='color:#d9534f;'>Welcome to the Team!</h2>
                                <p>Dear $first_name,</p>
                                <p>Your employee account has been created successfully.</p>
                                <p><strong>Email:</strong> $email</p>
                                <p><strong>Temporary Password:</strong> $generated_password</p>
                            </body>
                            </html>
                        ";
                        $mail->send();
                        $success_msg = "Employee registered and credentials emailed successfully!";
                    } catch (Exception $e) {
                        $success_msg = "Employee registered, but email failed: " . $mail->ErrorInfo;
                    }
                } else {
                    $error_msg = "Error creating user account: " . $conn->error;
                }
            } else {
                $error_msg = "Error registering employee: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $form_title; ?></title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        .form-container { background: white; padding: 30px; border-radius: 8px; max-width: 900px; margin: 0 auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-header { text-align: center; margin-bottom: 30px; color: #d9534f; }
        .form-row { display: flex; gap: 20px; margin-bottom: 15px; }
        .form-group { flex: 1; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { width: 100%; padding: 12px; background: #d9534f; color: white; border: none; font-size: 16px; cursor: pointer; border-radius: 4px; }
        .btn-submit:hover { background: #c9302c; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; }
        .alert-error { background: #f2dede; color: #a94442; border: 1px solid #ebccd1; }
        
        .section-divider {
            border-top: 2px dashed #ddd;
            margin: 30px 0;
            position: relative;
        }
        .section-label {
            position: absolute;
            top: -12px;
            left: 20px;
            background: white;
            padding: 0 10px;
            font-weight: bold;
            color: #777;
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="form-container">
        <h2 class="form-header"><?php echo $form_title; ?></h2>

        <?php if($success_msg): ?> <div class="alert alert-success"><?php echo $success_msg; ?></div> <?php endif; ?>
        <?php if($error_msg): ?> <div class="alert alert-error"><?php echo $error_msg; ?></div> <?php endif; ?>

        <form method="POST" action="">
            <!-- Hidden ID for Updates -->
            <?php if($is_edit): ?>
                <input type="hidden" name="update_id" value="<?php echo $id; ?>">
            <?php endif; ?>

            <!-- Section 1: Personal Info -->
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" value="<?php echo htmlspecialchars($middle_name); ?>">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="form-group">
                    <label>Mobile Number</label>
                    <input type="text" name="mobile_number" value="<?php echo htmlspecialchars($phone); ?>" required maxlength="10">
                </div>
            </div>

            <hr>

            <!-- Section 2: Job Details -->
            <div class="form-row">
                <div class="form-group">
                    <label>Department</label>
                    <select name="department_name">
                        <?php if (empty($departments_list)): ?>
                            <option value="">-- No Departments Found --</option>
                            <!-- Fallback/Placeholder Options if DB fails -->
                            <option value="IT" <?php if($department == 'IT') echo 'selected'; ?>>IT (Fallback)</option>
                            <option value="HR" <?php if($department == 'HR') echo 'selected'; ?>>HR (Fallback)</option>
                            <option value="Accounting" <?php if($department == 'Accounting') echo 'selected'; ?>>Accounting (Fallback)</option>
                            <option value="Sales" <?php if($department == 'Sales') echo 'selected'; ?>>Sales (Fallback)</option>
                        <?php else: ?>
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments_list as $deptName): ?>
                                <option value="<?php echo $deptName; ?>" <?php if($department == $deptName) echo 'selected'; ?>>
                                    <?php echo $deptName; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Designation (Position)</label>
                    <input type="text" name="designation_name" value="<?php echo htmlspecialchars($position); ?>" required>
                </div>
                <div class="form-group">
                    <label>Date Hired</label>
                    <input type="date" name="joining_date" value="<?php echo htmlspecialchars($date_hired); ?>" required>
                </div>
            </div>

            <hr>

            <!-- Section 3: Government Numbers -->
            <div class="form-row">
                <div class="form-group">
                    <label>SSS Number</label>
                    <input type="text" name="sss_number" value="<?php echo htmlspecialchars($sss); ?>" maxlength="10">
                </div>
                <div class="form-group">
                    <label>Pag-IBIG Number</label>
                    <input type="text" name="pagibig_number" value="<?php echo htmlspecialchars($pagibig); ?>" maxlength="10">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>PhilHealth Number</label>
                    <input type="text" name="philhealth_number" value="<?php echo htmlspecialchars($philhealth); ?>" maxlength="10">
                </div>
                <div class="form-group">
                    <label>TIN Number</label>
                    <input type="text" name="tin_number" value="<?php echo htmlspecialchars($tin); ?>" maxlength="12">
                </div>
            </div>

            <hr>

            <!-- Section 4: Salary -->
            <div class="form-row">
                <div class="form-group">
                    <label>Basic Salary (Monthly)</label>
                    <input type="number" name="basic_salary" step="0.01" value="<?php echo htmlspecialchars($basic_salary); ?>" required>
                    <small>System will auto-calculate Daily Rate.</small>
                </div>
            </div>

            <!-- Section 5: Account Management (Only Visible on Edit) -->
            <?php if($is_edit): ?>
            <div class="section-divider">
                <span class="section-label">Login Credentials</span>
            </div>
            <div class="form-row" style="background: #f9f9f9; padding: 15px; border-radius: 5px;">
                <div class="form-group">
                    <label>System Role</label>
                    <select name="account_role">
                        <option value="employee" <?php if($user_role == 'employee') echo 'selected'; ?>>Employee (Standard)</option>
                        <option value="admin" <?php if($user_role == 'admin') echo 'selected'; ?>>Admin (Full Access)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reset Password</label>
                    <input type="text" name="reset_password" placeholder="Leave empty to keep current password">
                    <small style="color: #888;">Type a new value here only if you want to change it.</small>
                </div>
            </div>
            <?php endif; ?>

            <br>
            <button type="submit" class="btn-submit"><?php echo $btn_text; ?></button>
        </form>
    </div>

</body>
</html>