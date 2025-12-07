<?php

include 'db_connection.php';

session_start();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT employee_id FROM employees WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['reset_email'] = $email;
        header("Location: APP_reset_password.php");
        exit();
    } else {
        $message = "<div class='alert alert-danger'>Email not found.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">

<div class="card shadow p-4" style="width: 350px;">
  <h4 class="text-center mb-3">Forgot Password</h4>
  <form method="POST">
    <div class="mb-3">
      <label for="email" class="form-label">Enter your registered email</label>
      <input type="email" class="form-control" name="email" required placeholder="example@email.com">
    </div>
    <button type="submit" class="btn btn-primary w-100">Continue</button>
  </form>
  <div class="mt-3 text-center"><?= $message ?></div>
  <div class="text-center mt-3">
    <a href="APP_index.php" class="text-decoration-none">Back to Login</a>
  </div>
</div>

</body>
</html>