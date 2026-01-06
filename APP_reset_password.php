<?php

include 'db_connection.php';

session_start();

if (!isset($_SESSION['reset_email'])) {
    header("Location: APP_forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE employee_accounts SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed, $email);
        $stmt->execute();

        unset($_SESSION['reset_email']);
        $message = "<div class='alert alert-success'>Password reset successfully. <a href='APP_index.php'>Login now</a></div>";
    } else {
        $message = "<div class='alert alert-danger'>Passwords do not match.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">

<div class="card shadow p-4" style="width: 350px;">
  <h4 class="text-center mb-3">Reset Password</h4>
  <form method="POST">
    <div class="mb-3">
      <label for="new_password" class="form-label">New Password</label>
      <input type="password" name="new_password" class="form-control" required minlength="6">
    </div>
    <div class="mb-3">
      <label for="confirm_password" class="form-label">Confirm Password</label>
      <input type="password" name="confirm_password" class="form-control" required minlength="6">
    </div>
    <button type="submit" class="btn btn-success w-100">Reset Password</button>
  </form>
  <div class="mt-3"><?= $message ?></div>
</div>

</body>
</html>