<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <h1>Register an Admin</h1>

            <form action="register.php" method="post" id="signup-form">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Enter your email" class="input-field" required>

                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" class="input-field" required>

                <button type="submit" class="btn">Sign Up</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('signup-form').addEventListener('submit', (event) => {
            event.preventDefault();

            let formData = new FormData(event.target);

            fetch('register.php', {
                method: 'POST',
                body: formData
            }).then((response) => response.json()).then((data) => {
                if (data.status === 'success') {
                    Swal.fire({
                        title: "Welcome Back! 🎉",
                        text: data.message,
                        icon: "success",
                        timer: 2000,
                        showConfirmButton: false,
                        backdrop: `
                            rgba(0,0,123,0.4)
                            url("images/confetti.gif")
                            left top
                            no-repeat
                        `,
                        customClass: {
                            popup: 'animated fadeInDown faster'
                        }
                    }).then(() => { 
                        window.location.href = "dashboard.php";
                    });
                } else {
                    Swal.fire({
                        title: "Oops! Login Failed 😞",
                        text: data.message,
                        icon: "error",
                        confirmButtonText: "Try Again",
                        confirmButtonColor: "#d33",
                        showClass: {
                            popup: "animate__animated animate__shakeX"
                        },
                        backdrop: `
                            rgba(0,0,0,0.4)
                        `
                    });
                }
            }).catch((error) => console.error('Error:', error));
        })
    </script>
</body>
</html>