<?php

session_start();

include 'db_connection.php';

if (isset($_SESSION['employee_id'])) {
    header('Location: APP_dashboard.php');
    exit;
}

$loginError = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $user_password = trim($_POST['password']);
    $remember = isset($_POST['remember']) ? true : false;

    $sql = "SELECT * FROM employee_accounts WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stored_password = $row['password'];
        $verified = false;
        $needs_rehash = false;

        if (is_string($stored_password) && (strpos($stored_password, '$2y$') === 0 || strpos($stored_password, '$argon2') === 0)) {
            if (password_verify($user_password, $stored_password)) {
                $verified = true;
                $needs_rehash = password_needs_rehash($stored_password, PASSWORD_DEFAULT);
            }
        } 

        else if ($user_password === $stored_password) {
            $verified = true;
            $needs_rehash = true;
        }

        if ($verified) {
            if ($needs_rehash) {
                $newHash = password_hash($user_password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE employee_accounts SET password = ? WHERE employee_id = ?");
                $update->bind_param("si", $newHash, $row['employee_id']);
                $update->execute();
                $update->close();
            }

            $_SESSION['employee_id'] = $row['employee_id'];
            $_SESSION['email'] = $row['email'];

            if ($remember) {
              $token = bin2hex(random_bytes(32));
              $hashedToken = hash('sha256', $token);

              $update = $conn->prepare("UPDATE employee_accounts SET remember_token = ? WHERE employee_id = ?");
              $update->bind_param("si", $hashedToken, $employee['employee_id']);
              $update->execute();

              setcookie("remember", $token, time() + 300, "/", "", false, true);
            }

            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    setTimeout(function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Login Successful',
                            text: 'Redirecting to dashboard...',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = 'APP_dashboard.php';
                        });
                    }, 100);
                </script>";
            exit;
        } else {
            $loginError = "Incorrect password. Please try again.";
        }
    } else {
        $loginError = "Email not found. Please register first.";
    }

    $stmt->close();
    $conn->close();

  if (isset($_COOKIE['remember'])) {
      echo "Cookie is still active.";
  } else {
      echo "Cookie has expired or does not exist.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="icon" type="image/png" href="images/logo.png">
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="#">
      <img src="images/logo.png" alt="Logo" width="50" height="50" class="me-2" />
      Dragon Edge Group
    </a>
  </div>
</nav>

<div class="container-wrapper">
  <div class="login-card">
    <h5 class="text-center mb-4 text-danger" style="font-weight: 600;">Employee Login</h5>
    <form method="POST">
      <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required/>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required/>
      </div>
      <div class="d-flex justify-content-between mb-3">
        <div>
          <input type="checkbox" id="remember" name="remember" value="1" /> 
          <label for="remember" class="ms-1">Remember me</label>
        </div>
        <a href="APP_forgot_password.php" class="text-decoration-none">Forgot password?</a>
      </div>
      <div class="d-flex justify-content-between mb-3">
        <a href="APP_register.php" class="text-decoration-none">Create an account</a>
      </div>
      <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
  </div>
</div>

<?php if (!empty($loginError)) : ?>
<script>
Swal.fire({
  icon: 'error',
  title: 'Login Failed',
  text: '<?= $loginError ?>'
});
</script>
<?php endif; ?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

body, html {
  font-family: 'Poppins', sans-serif;
  height: 100%;
  margin: 0;
  padding: 0;
  background-color: #fff2f2;
  background-image:
    linear-gradient(45deg, #ffd6d6 25%, transparent 25%),
    linear-gradient(-45deg, #ffd6d6 25%, transparent 25%),
    linear-gradient(45deg, transparent 75%, #ffd6d6 75%),
    linear-gradient(-45deg, transparent 75%, #ffd6d6 75%);
  background-size: 40px 40px;
  background-position: 0 0, 0 20px, 20px -20px, -20px 0px;
}

.navbar {
  background-color: #e83c3c;
}

.navbar-brand, .nav-link {
  color: #fff !important;
  font-weight: 500;
}

.container-wrapper {
  min-height: calc(100vh - 56px);
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2rem 1rem;
}

.login-card {
  width: 100%;
  max-width: 400px;
  padding: 2rem;
  border-radius: 1.5rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
  background-color: #fff;
}

.form-control:focus {
  border-color: #e83c3c;
  box-shadow: 0 0 0 0.2rem rgba(232, 60, 60, 0.25);
}

.btn-primary {
  background-color: #e83c3c;
  border: none;
}

.btn-primary:hover {
  background-color: #c83232;
}
</style>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
