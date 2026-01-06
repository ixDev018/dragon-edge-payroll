<?php
// register.php
require 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $ideal_salary = $_POST['ideal_salary']; // Replacing basic_salary

    // Check if email exists
    $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $error_msg = "Email already registered!";
    } else {
        // 1. Generate Employee ID
        $prefix = date('Y-m-');
        $res = $conn->query("SELECT employee_id FROM employees WHERE employee_id LIKE '$prefix%' ORDER BY employee_id DESC LIMIT 1");
        $last_num = ($res->num_rows > 0) ? intval(substr($res->fetch_assoc()['employee_id'], -4)) + 1 : 1;
        $emp_id_str = $prefix . str_pad($last_num, 4, '0', STR_PAD_LEFT);

        // 2. Insert into Employees (Status: Probationary by default)
        // We map 'Ideal Salary' to 'basic_salary' column, but you might want to review this later in Admin
        $stmt = $conn->prepare("INSERT INTO employees (employee_id, first_name, last_name, email, basic_salary, employment_status, created_at) VALUES (?, ?, ?, ?, ?, 'Probationary', NOW())");
        $stmt->bind_param("ssssd", $emp_id_str, $first_name, $last_name, $email, $ideal_salary);
        
        if ($stmt->execute()) {
            $db_id = $stmt->insert_id;
            
            // 3. Insert into Users
            $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
            $full_name = "$first_name $last_name";
            
            $u_stmt = $conn->prepare("INSERT INTO users (employee_id, name, email, password, role, created_at) VALUES (?, ?, ?, ?, 'employee', NOW())");
            $u_stmt->bind_param("isss", $db_id, $full_name, $email, $hashed_pw);
            $u_stmt->execute();

            $success_msg = "Registration successful! You can now login.";
        } else {
            $error_msg = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register | Dragon Edge Group</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Reusing your Login CSS for consistency */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f4f6f9; }
        .login-box { width: 100%; max-width: 500px; padding: 40px; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; }
        .input-field { width: 100%; padding: 12px 15px; margin: 8px 0 16px; border-radius: 25px; border: 1px solid #bdc3c7; font-size: 16px; }
        .btn { width: 100%; padding: 12px; background: rgb(212, 65, 65); color: white; border: none; border-radius: 25px; cursor: pointer; font-size: 18px; }
        .btn:hover { background: rgb(185, 41, 41); }
        h1 { margin-bottom: 20px; color: #333; }
        label { display: block; text-align: left; font-weight: bold; margin-bottom: 5px; color: #555; }
    </style>
</head>
<body>

<div class="login-box">
    <h1>Create Account</h1>
    <form method="POST">
        <label>First Name</label>
        <input type="text" name="first_name" class="input-field" required>

        <label>Last Name</label>
        <input type="text" name="last_name" class="input-field" required>

        <label>Email Address</label>
        <input type="email" name="email" class="input-field" required>
        
        <label>Ideal Salary (Monthly)</label>
        <input type="number" name="ideal_salary" class="input-field" placeholder="e.g. 25000" required>

        <label>Password</label>
        <input type="password" name="password" class="input-field" required>

        <button type="submit" class="btn">Register</button>
        <p style="margin-top: 15px;">Already have an account? <a href="index.php" style="color: rgb(212, 65, 65);">Sign In</a></p>
    </form>
</div>

<?php if(isset($success_msg)): ?>
<script>
    Swal.fire({ icon: 'success', title: 'Success', text: '<?= $success_msg ?>' }).then(() => { window.location.href = 'index.php'; });
</script>
<?php endif; ?>

<?php if(isset($error_msg)): ?>
<script>
    Swal.fire({ icon: 'error', title: 'Error', text: '<?= $error_msg ?>' });
</script>
<?php endif; ?>

</body>
</html>