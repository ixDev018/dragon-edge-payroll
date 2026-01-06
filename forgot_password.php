<?php
// forgot_password.php
require 'db_connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$success_msg = "";
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $user_id = $row['id'];
        $name = $row['name'];
        
        // Generate Temporary Password
        $temp_pass = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 8);
        $hashed_pw = password_hash($temp_pass, PASSWORD_DEFAULT);

        // Update Database
        $conn->query("UPDATE users SET password = '$hashed_pw' WHERE id = $user_id");

        // Send Email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'dragon.edge.group.company@gmail.com'; 
            
            // ---------------------------------------------------
            // PASTE YOUR 16-DIGIT GOOGLE APP PASSWORD HERE
            // ---------------------------------------------------
            $mail->Password = 'YOUR_APP_PASSWORD_HERE'; 
            
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('dragon.edge.group.company@gmail.com', 'Dragon Edge Security');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "
                <h3>Hello $name,</h3>
                <p>Your password has been successfully reset.</p>
                <p><b>Temporary Password:</b> <span style='background:#eee; padding:5px; font-weight:bold;'>$temp_pass</span></p>
                <p>Please login and change it immediately.</p>
            ";

            $mail->send();
            $success_msg = "A temporary password has been sent to your email.";
        } catch (Exception $e) {
            $error_msg = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $error_msg = "Email not found in our records.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | Dragon Edge Group</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f4f6f9; }
        .login-box { width: 100%; max-width: 450px; padding: 40px; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; }
        .input-field { width: 100%; padding: 12px 15px; margin: 8px 0 16px; border-radius: 25px; border: 1px solid #bdc3c7; font-size: 16px; outline: none; transition: 0.3s; }
        .input-field:focus { border-color: rgb(212, 65, 65); box-shadow: 0 0 5px rgba(212, 65, 65, 0.41); }
        .btn { width: 100%; padding: 12px; background: rgb(212, 65, 65); color: white; border: none; border-radius: 25px; cursor: pointer; font-size: 18px; margin-top: 10px;}
        .btn:hover { background: rgb(185, 41, 41); }
        h1 { margin-bottom: 10px; color: #333; }
        p { margin-bottom: 20px; color: #666; }
        label { display: block; text-align: left; font-weight: bold; margin-bottom: 5px; color: #555; }
        a { color: rgb(212, 65, 65); text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="login-box">
    <h1>Forgot Password?</h1>
    <p>Enter your email to receive a temporary password.</p>
    <form method="POST">
        <label>Email Address</label>
        <input type="email" name="email" class="input-field" required>

        <button type="submit" class="btn">Reset Password</button>
        <p style="margin-top: 15px;"><a href="index.php">Back to Login</a></p>
    </form>
</div>

<?php if($success_msg): ?>
<script>
    Swal.fire({ icon: 'success', title: 'Check your Email', text: '<?= $success_msg ?>' });
</script>
<?php endif; ?>

<?php if($error_msg): ?>
<script>
    Swal.fire({ icon: 'error', title: 'Error', text: '<?= $error_msg ?>' });
</script>
<?php endif; ?>

</body>
</html>