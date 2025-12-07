<?php
session_start();
include 'db_connection.php';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 1. Prepare query to fetch user by EMAIL
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // 3. Check if user exists
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc(); // *** $user IS NOW DEFINED HERE! ***
        
        // 4. Verify the password
        if (password_verify($password, $user['password'])) {
            
            // --- START: SUCCESS AND SESSION LOGIC ---
            
            // Core Session Variables
            $_SESSION['admin_email'] = $user['email']; 
            $_SESSION['user_id'] = $user['id']; 
            $_SESSION['role'] = $user['role']; 
            
            // APP_dashboard.php required variables
            // FIX: Use the 'employee_id' column (which links to the employee), NOT the account 'id'
            $_SESSION['employee_id'] = $user['employee_id'];
            // The null coalescing operator (??) ensures a value even if $user['name'] is null
            $_SESSION['employee_name'] = $user['name'] ?? $user['email']; 
            
            // Role-based Redirection Logic
            $target_url = 'APP_dashboard.php'; // Default to employee dashboard
            if (isset($user['role']) && $user['role'] === 'admin') {
                $target_url = 'employees.php'; // Change if role is admin
            }
            
            // Send the correct URL back in the JSON response
            echo json_encode([
                "status" => "success", 
                "message" => "Welcome! Redirecting...", 
                "redirect_url" => $target_url
            ]);
            
            // --- END: SUCCESS AND SESSION LOGIC ---
            
        } else {
            // Password incorrect
            echo json_encode(["status" => "error", "message" => "Invalid password!"]);
        }
    } else {
        // User not found
        echo json_encode(["status" => "error", "message" => "User not found!"]);
    }

    $stmt->close();
    $conn->close();
}
?>