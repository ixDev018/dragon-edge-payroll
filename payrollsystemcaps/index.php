<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dragon Edge Group</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

<div class="container">
    <div class="login-box">
        <img src="images/logo.png" alt="Logo" class="logo">
        <h1>Dragon Edge Group</h1>
        <p class="welcome-text">Welcome Back! Please enter your details</p>

        <form id="loginForm" action="login.php" method="POST">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter your email" class="input-field" required>

            <label>Password</label>
            <input type="password" name="password" placeholder="Enter your password" class="input-field" required>

            <button type="submit" class="btn">Sign In</button>
            
            <div style="width: 100%; display: flex; justify-content: space-between; margin-top: 15px; font-size: 14px;">
                <a href="forgot_password.php" style="color: #555; text-decoration: none;">Forgot Password?</a>
                <a href="register.php" style="color: rgb(212, 65, 65); text-decoration: none; font-weight: bold;">Create Account</a>
            </div>
        </form>
    </div>


    <script>
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            event.preventDefault();

            let formData = new FormData(this);

            fetch('login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    document.documentElement.style.overflow = "hidden"; 

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
                        document.documentElement.style.overflow = "auto"; 
                        // window.location.href = "dashboard.php";
                        window.location.href = data.redirect_url; // Use the URL sent from the server
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
            })
            .catch(error => console.error('Error:', error));
        });
    </script>
</div>


<div class="video-background-container">
    <video class="video-background" autoplay loop muted>
        <source src="vids/walk.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    <div class="overlay"></div>
</div>


<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
    }

    html, body {
        height: 100%;
        width: 100%;
        overflow: hidden; 
    }

    body {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh; 
        position: relative;
    }

    .video-background {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: -1;
        background-color: rgba(224, 47, 47, 0.64);
    }

    .overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(224, 47, 47, 0.64); 
        z-index: 1;
    }
    .container {
        display: flex;
        width: 90%;
        max-width: 1300px;
        min-height: 700px;
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.2);
        transition: transform 0.3s;
        position: relative;
        z-index: 10;
        justify-content: center;
    }

    @keyframes gradientBG {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@700&display=swap');

    .header {
        width: 100%;
        padding: 20px;
        text-align: center;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(5px);
        color: white;
        font-size: 50px;
        font-weight: bold;
        font-family: 'Cinzel', serif;
        text-shadow: 3px 3px 10px rgba(0, 0, 0, 0.3);
        animation: fadeInScale 1.5s ease-in-out;
        transition: color 0.5s ease-in-out;
    }

    .header:hover {
        color: #ffcc00;
    }

    @keyframes fadeInScale {
        0% {
            opacity: 0;
            transform: scale(0.8);
        }
        100% {
            opacity: 1;
            transform: scale(1);
        }
    }

    .container:hover {
        transform: scale(1.02);
    }

    .login-box {
        width: 50%;
        padding: 40px;
        color: black;
        background: white;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    @keyframes logoSpin {
        0% { transform: rotate(0deg) scale(1); }
        50% { transform: rotate(10deg) scale(1.05); } 
        100% { transform: rotate(0deg) scale(1); }
    }

    .logo {
        width: 250px;
        height: 250px;
        margin-bottom: 20px;
        display: block;
        animation: logoSpin 3s ease-in-out infinite;
    }

    h2 {
        font-size: 26px;
        font-weight: bold;
        margin-bottom: 10px;
        animation: fadeIn 1s ease-in-out;
    }

    p {
        color: #555;
        margin-bottom: 20px;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    form {
        display: flex;
        flex-direction: column;
        width: 100%;
        margin-top: 20px;
    }

    label {
        font-size: 14px;
        margin-bottom: 5px;
        text-align: left;
    }

    input {
        width: 100%;
        padding: 12px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 14px;
        transition: all 0.3s;
        background: white;
        color: black;
        outline: none;
    }

    input::placeholder {
        color: #999;
    }

    input:focus {
        border-color: #6a11cb;
    }

    .btn {
        background: #ff6a00;
        color: white;
        padding: 12px;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s ease-in-out;
    }

    .btn:hover {
        background: #e65c00;
    }

    .video-section {
        width: 50%;
        overflow: hidden;
    }

    .video-section video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .input-field {
        width: 100%;
        padding: 12px 15px;
        margin: 8px 0 16px;
        border-radius: 25px;
        border: 1px solid #bdc3c7;
        outline: none;
        font-size: 16px;
        transition: all 0.3s ease;
    }

    .input-field:focus {
        border-color: rgb(212, 65, 65);
        box-shadow: 0 0 5px rgba(212, 65, 65, 0.41);
    }

    .btn {
        width: 100%;
        padding: 12px 0;
        background:rgb(212, 65, 65);
        color: white;
        font-size: 18px;
        border: none;
        border-radius: 25px;
        cursor: pointer;
        margin-top: 10px;
        transition: background 0.3s ease;
    }

    .btn:hover {
        background:rgb(185, 41, 41);
    }

    label {
        font-weight: bold;
        font-size: 16px;
        margin-bottom: 5px;
        color: #34495e;
        text-align: left;
        display: block;
    }

    form {
        margin-top: 20px;
    }

</style>

</body>
</html>