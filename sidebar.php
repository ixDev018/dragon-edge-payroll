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
<div class="l-navbar" id="nav-bar">
    <nav class="nav">
        <div class="nav_header">
            <img src="images/logo.png" alt="Payroll Logo" class="custom-logo">
            <h4 class="nav_title">Integrated Payroll System</h4>
            <div class="chat-button">
            </div>
        </div> 

     

        <!-- <div class="nav_list">
            <a href="dashboard.php" class="nav_link">
                <div class="icon-container">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M520-600v-240h320v240H520ZM120-440v-400h320v400H120Zm400 320v-400h320v400H520Zm-400 0v-240h320v240H120Z"/></svg>
                </div>
                <span class="nav_name">Dashboard</span>
            </a>
            <hr class="inner-separator"> -->

        <div class="nav_list"> 
            <a href="employees.php" class="nav_link"> <div class="icon-container">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h168q14-36 44-58t68-22q38 0 68 22t44 58h168q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm280-670q13 0 21.5-8.5T510-820q0-13-8.5-21.5T480-850q-13 0-21.5 8.5T450-820q0 13 8.5 21.5T480-790Zm0 350q58 0 99-41t41-99q0-58-41-99t-99-41q-58 0-99 41t-41 99q0 58 41 99t99 41ZM200-200h560v-46q-54-53-125.5-83.5T480-360q-83 0-154.5 30.5T200-246v46Z"/></svg>
            </div>
                <span class="nav_name">Employees</span>
            </a>

            <hr class="inner-separator">

            <a href="accounts.php" class="nav_link">
            <div class="icon-container">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M480-440q58 0 99-41t41-99q0-58-41-99t-99-41q-58 0-99 41t-41 99q0 58 41 99t99 41ZM200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-46q-54-53-125.5-83.5T480-360q-83 0-154.5 30.5T200-246v46Z"/></svg>
            </div>
                <span class="nav_name">e-Accounts</span>
            </a>
            
            <hr class="inner-separator">
            <a href="departments.php" class="nav_link">
                <div class="icon-container">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M160-120q-33 0-56.5-23.5T80-200v-160h280v80h240v-80h280v160q0 33-23.5 56.5T800-120H160Zm280-240v-80h80v80h-80ZM80-440v-200q0-33 23.5-56.5T160-720h160v-80q0-33 23.5-56.5T400-880h160q33 0 56.5 23.5T640-800v80h160q33 0 56.5 23.5T880-640v200H600v-80H360v80H80Zm320-280h160v-80H400v80Z"/></svg>
                </div>
                <span class="nav_name">Departments</span>
            </a>

            <hr class="inner-separator">


            
            
            <a href="leave.php" class="nav_link">
                <div class="icon-container">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="m696-440-56-56 83-84-83-83 56-57 84 84 83-84 57 57-84 83 84 84-57 56-83-83-84 83Zm-336-40q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM40-160v-112q0-34 17.5-62.5T104-378q62-31 126-46.5T360-440q66 0 130 15.5T616-378q29 15 46.5 43.5T680-272v112H40Z"/></svg>
                </div>
                <span class="nav_name">Leaves</span>
            </a>
            <hr class="inner-separator">
            
            <a href="attendance.php" class="nav_link">
                <div class="icon-container">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M481-781q106 0 200 45.5T838-604q7 9 4.5 16t-8.5 12q-6 5-14 4.5t-14-8.5q-55-78-141.5-119.5T481-741q-97 0-182 41.5T158-580q-6 9-14 10t-14-4q-7-5-8.5-12.5T126-602q62-85 155.5-132T481-781Zm0 94q135 0 232 90t97 223q0 50-35.5 83.5T688-257q-51 0-87.5-33.5T564-374q0-33-24.5-55.5T481-452q-34 0-58.5 22.5T398-374q0 97 57.5 162T604-121q9 3 12 10t1 15q-2 7-8 12t-15 3q-104-26-170-103.5T358-374q0-50 36-84t87-34q51 0 87 34t36 84q0 33 25 55.5t59 22.5q34 0 58-22.5t24-55.5q0-116-85-195t-203-79q-118 0-203 79t-85 194q0 24 4.5 60t21.5 84q3 9-.5 16T208-205q-8 3-15.5-.5T182-217q-15-39-21.5-77.5T154-374q0-133 96.5-223T481-687Zm0-192q64 0 125 15.5T724-819q9 5 10.5 12t-1.5 14q-3 7-10 11t-17-1q-53-27-109.5-41.5T481-839q-58 0-114 13.5T260-783q-8 5-16 2.5T232-791q-4-8-2-14.5t10-11.5q56-30 117-46t124-16Zm0 289q93 0 160 62.5T708-374q0 9-5.5 14.5T688-354q-8 0-14-5.5t-6-14.5q0-75-55.5-125.5T481-550q-76 0-130.5 50.5T296-374q0 81 28 137.5T406-123q6 6 6 14t-6 14q-6 6-14 6t-14-6q-59-62-90.5-126.5T256-374q0-91 66-153.5T481-590Zm-1 196q9 0 14.5 6t5.5 14q0 75 54 123t126 48q6 0 17-1t23-3q9-2 15.5 2.5T744-191q2 8-3 14t-13 8q-18 5-31.5 5.5t-16.5.5q-89 0-154.5-60T460-374q0-8 5.5-14t14.5-6Z"/></svg>
                </div>
                <span class="nav_name">Biometrics</span>
            </a>
            <hr class="inner-separator">
            
            <a href="payroll_master.php" class="nav_link">
                <div class="icon-container">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="m691-150 139-138-42-42-97 95-39-39-42 43 81 81ZM240-600h480v-80H240v80ZM720-40q-83 0-141.5-58.5T520-240q0-83 58.5-141.5T720-440q83 0 141.5 58.5T920-240q0 83-58.5 141.5T720-40ZM120-80v-680q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v267q-28-14-58.5-20.5T720-520H240v80h284q-17 17-31.5 37T467-360H240v80h203q-2 10-2.5 19.5T440-240q0 42 11.5 80.5T486-86l-6 6-60-60-60 60-60-60-60 60-60-60-60 60Z"/></svg>
                </div>
                <span class="nav_name">Payroll</span>
            </a>
            <hr class="inner-separator">
            <a href="attendance_logs.php" class="nav_link">
                <div class="icon-container">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-table" viewBox="0 0 16 16">
                        <path style="filter: invert(100%);" d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm15 2h-4v3h4zm0 4h-4v3h4zm0 4h-4v3h3a1 1 0 0 0 1-1zm-5 3v-3H6v3zm-5 0v-3H1v2a1 1 0 0 0 1 1zm-4-4h4V8H1zm0-4h4V4H1zm5-3v3h4V4zm4 4H6v3h4z"/>
                    </svg>
                </div>
                <span class="nav_name">Reports</span>
            </a>
        </div> 

       

       

         <!-- <button id="sidebar-theme-toggle" class="theme-toggle-btn">
            🌙 Dark Mode
        </button> -->

        <a href="#" class="nav_link logout" id="logout">
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M200-120q-33 0-56.5-23.5T120-200v-160h80v160h560v-560H200v160h-80v-160q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm220-160-56-58 102-102H120v-80h346L364-622l56-58 200 200-200 200Z"/></svg>
            <span class="nav_name">Sign Out</span>
        </a>
        </nav>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const savedTheme = localStorage.getItem("theme");
    const body = document.body;
    const themeToggle = document.getElementById("theme-toggle");
    const sidebarThemeToggle = document.getElementById("sidebar-theme-toggle");

    if (savedTheme === "light") {
        body.classList.add("light-theme");
        themeToggle.innerHTML = "☀️ Light Mode";
        sidebarThemeToggle.innerHTML = "☀️ Light Mode";
    } else {
        body.classList.add("dark-theme");
        themeToggle.innerHTML = "🌙 Dark Mode";
        sidebarThemeToggle.innerHTML = "🌙 Dark Mode";
    }

    function toggleTheme() {
        body.classList.toggle("light-theme");
        body.classList.toggle("dark-theme");
        const isLight = body.classList.contains("light-theme");
        localStorage.setItem("theme", isLight ? "light" : "dark");
        themeToggle.innerHTML = isLight ? "☀️ Light Mode" : "🌙 Dark Mode";
        sidebarThemeToggle.innerHTML = isLight ? "☀️ Light Mode" : "🌙 Dark Mode";
    }

    themeToggle.addEventListener("click", toggleTheme);
    sidebarThemeToggle.addEventListener("click", toggleTheme);
});

document.getElementById("logout").addEventListener("click", function (event) {
    event.preventDefault();
    Swal.fire({
        title: "Are you sure?",
        text: "You will be logged out!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, log me out!"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "logout.php";
        }
    });
});
</script>


<style>
body {
    transition: background 0.3s ease-in-out;
    font-family: 'Poppins', sans-serif;
}
:root {
    --icon-bg: rgba(255, 255, 255, 0.1);
}

body.light-theme {
    --icon-bg: rgba(0, 0, 0, 0.1);
}
body.light-theme {
    background: #f4f4f4;
}

:root {
    --sidebar-bg:rgb(34, 34, 34); 
    --text-color: #ffffff;
    --hover-bg: #222222; 
    --border-color: #333333; 
}

.custom-icon {
    width: 35px;
    height: 35px;
}

.l-navbar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 280px;
    background: var(--sidebar-bg);
    padding: 20px 15px;
    transition: 0.4s ease-in-out;
 
    border-right: 3px solid var(--border-color);
    text-align: center;
    overflow:hidden ; 
    scrollbar-width: thin; 
   
}

.l-navbar::-webkit-scrollbar {
    width: 8px;
}

.l-navbar::-webkit-scrollbar-track {
    background: transparent;
}

.l-navbar::-webkit-scrollbar-thumb {
    background: var(--hover-bg);
    border-radius: 10px;
}

.nav_link:active {
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.7);
    transition: box-shadow 0.2s ease-in-out;
}


body.light-theme .l-navbar {
    --sidebar-bg: #ffffff;
    --text-color: #2c3e50;
    --hover-bg: #f1f1f1;
    border-right: 3px solid #ddd;
}

.nav_header {
    text-align: center;
    padding-bottom: 20px;
}

.custom-logo {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid white;
}

.nav_title {
    color: var(--text-color);
    font-size: 18px;
    margin-top: 10px;
}

body.light-theme .nav_title {
    color: #2c3e50;
}

.separator {
    border: 1px solid var(--border-color);
    margin: 10px 0;
}

.inner-separator {
    border: 0.5px solid var(--border-color);
    margin: 8px 0;
}

.nav_list {
    flex-grow: 1;
}

.nav_link {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 10px;
    padding: 5px 10px;
    text-decoration: none;
    color: var(--text-color);
    font-size: 18px;
    border-radius: 8px;
    transition: all 0.3s ease-in-out;
}

.nav_link:hover, .nav_link.active {
    background: var(--hover-bg);
    border: 1px solid var(--border-color);
    transform: scale(1.05);
}

.custom-icon {
    width: 35px;
    height: 35px;
}

.theme-toggle-btn {
    background: var(--sidebar-bg);
    color: var(--text-color);
    border: none;
    padding: 10px 15px;
    border-radius: 6px;
    cursor: pointer;
    transition: 0.3s ease-in-out;
    font-size: 14px;
    width: 100%;
}

body.light-theme .theme-toggle-btn {
    background: #f8f9fa;
    color: #2c3e50;
    border: 1px solid #ddd;
}

.logout {
    background: #e74c3c; 
    color: white !important; 
    transition: 0.3s ease-in-out;
    border: 2px solid #c0392b;
    font-weight: bold;
    text-align: center;
    width: 200px;
   margin-top: 50px;
}

.logout:hover {
    background: #c0392b; 
}

body.light-theme .logout {
    background: #e74c3c; 
    color: white !important; 
    border: 2px solid #c0392b;
}

.icon-container {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    border-radius: 12px;
    background: #2b2b2bff;
    transition: background 0.3s ease-in-out;
}

.nav_link.active {
    background: #ffcccc;
    border-left: 5px solid #d9534f; 
    font-weight: bold;
    transform: scale(1.05);
}

body.dark-theme .nav_link.active {
    background: #555;
    border-left: 5px solid #888; 
}

.chat-button {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

.chat-btn {
    background-color:rgba(240, 126, 126, 0.41);
    border: none;
    padding: 8px;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(240, 135, 135, 0.2);
    transition: transform 0.3s ease;
}

.chat-btn:hover {
    transform: scale(1.1);
}

.chat-icon {
    width: 40px;
    height: 40px;
    object-fit: cover;
}


</style>

<script>
    document.addEventListener("DOMContentLoaded", function () {
    const navLinks = document.querySelectorAll(".nav_link");

    function setActiveLink() {
        navLinks.forEach(link => link.classList.remove("active"));
        this.classList.add("active");
        localStorage.setItem("activeNav", this.getAttribute("href"));
    }

    navLinks.forEach(link => link.addEventListener("click", setActiveLink));

    const activeNav = localStorage.getItem("activeNav");
    if (activeNav) {
        const activeLink = document.querySelector(`.nav_link[href="${activeNav}"]`);
        if (activeLink) {
            activeLink.classList.add("active");
        }
    }
});

</script>