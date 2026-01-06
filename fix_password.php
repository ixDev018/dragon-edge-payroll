<?php
require 'db_connection.php';

// 1. The password we want to use
$new_password = '12345';

// 2. Encrypt it using the server's own logic (Foolproof)
$new_hash = password_hash($new_password, PASSWORD_DEFAULT);

// 3. The user we want to fix
$email = 'admin@test.com';

echo "<h2>Password Reset Tool</h2>";
echo "Target Account: <b>$email</b><br>";
echo "Setting Password to: <b>$new_password</b><br>";
echo "Generated Hash: <small>$new_hash</small><br><br>";

// 4. Update the database
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $new_hash, $email);

if ($stmt->execute()) {
    echo "<h1 style='color:green'>SUCCESS! ✅</h1>";
    echo "The password has been reset in the database.<br>";
    echo "Please go back and login now.";
} else {
    echo "<h1 style='color:red'>ERROR ❌</h1>";
    echo "Database error: " . $conn->error;
}
?>