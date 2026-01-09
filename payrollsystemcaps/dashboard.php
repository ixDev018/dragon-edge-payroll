<?php
    include 'sidebar.php'; 
    include 'db_connection.php'; 

    // 1. Fetched only the existing tables (Removed Holidays)
    $departmentCount = mysqli_query($conn, "SELECT COUNT(*) FROM departments");
    $designationCount = mysqli_query($conn, "SELECT COUNT(*) FROM designations");
    $employeeCount = mysqli_query($conn, "SELECT COUNT(*) FROM employees");

    $departmentCount = mysqli_fetch_assoc($departmentCount)['COUNT(*)'];
    $designationCount = mysqli_fetch_assoc($designationCount)['COUNT(*)'];
    $employeeCount = mysqli_fetch_assoc($employeeCount)['COUNT(*)'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dragon Edge Group</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    padding: 0;
    background: linear-gradient(135deg, rgb(92, 92, 92), rgb(92, 92, 92));
    color: #333;
}

.container {
    margin-left: 300px;  
    padding: 40px;
    max-width: 1200px;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    justify-content: center;
}

.card {
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s;
    width: 220px;
    text-align: center;
    margin: 10px;
}

.card:hover {
    transform: scale(1.05);
}

.card-title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 10px;
}

.card-number {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 15px;
}

.icon {
    font-size: 36px;
    margin-bottom: 15px;
}

.card-department { background: linear-gradient(135deg, #F7B7A3, #F5A25D); }
.card-designation { background: linear-gradient(135deg,rgb(179, 221, 94),rgb(105, 128, 56)); }
.card-employee { background: linear-gradient(135deg, #FF914D, #FFB23C); }
.card-messages { background: linear-gradient(135deg, rgb(250, 190, 88), rgb(255, 160, 67)); }
.card-payroll { background: linear-gradient(135deg, rgb(78, 201, 176), rgb(42, 157, 143)); color: white; }

.graph-container {
    width: 100%;
    height: 120px;
    margin-top: 15px;
}

.welcome-message {
    width: calc(100% - 300px);
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
    font-size: 32px;
    font-weight: 700;
    background: #FF914D;
    color: white;
    padding: 20px 40px;
    border-radius: 10px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    margin-left: 300px; 
    margin-bottom: 40px;
}

.welcome-message .icon {
    margin-right: 15px;
    font-size: 36px;
}
</style>


<div class="welcome-message">
    <div class="icon"><i class="fas fa-gem"></i></div> 
    <p>Welcome to Dragon Edge Group Dashboard!</p>
</div>

<div class="container">
    <div class="card card-department">
        <div class="icon"><i class="fas fa-users"></i></div>
        <div class="card-title">Departments</div>
        <div class="card-number"><?php echo $departmentCount; ?></div>
        <div class="graph-container">
            <canvas id="departmentChart"></canvas>
        </div>
    </div>

    <div class="card card-designation">
        <div class="icon"><i class="fas fa-briefcase"></i></div>
        <div class="card-title">Designations</div>
        <div class="card-number"><?php echo $designationCount; ?></div>
        <div class="graph-container">
            <canvas id="designationChart"></canvas>
        </div>
    </div>

    <div class="card card-employee">
        <div class="icon"><i class="fas fa-user-tie"></i></div>
        <div class="card-title">Employees</div>
        <div class="card-number"><?php echo $employeeCount; ?></div>
        <div class="graph-container">
            <canvas id="employeeChart"></canvas>
        </div>
    </div>

    <div class="card card-messages">
        <div class="icon"><i class="fas fa-envelope"></i></div>
        <div class="card-title">Chats</div>
        <div class="card-number">No Messages</div>
    </div>

    <?php
    $today = date('j'); 
    $currentMonth = date('F'); 
    $nextMonth = date('F', strtotime('+1 month'));

    if ($today < 10) {
        $payrollDate = "10th of $currentMonth";
    } elseif ($today < 25) {
        $payrollDate = "25th of $currentMonth";
    } else {
        $payrollDate = "10th of $nextMonth";
    }
    ?>

    <div class="card card-payroll">
        <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
        <div class="card-title">Next Payroll</div>
        <div class="card-number"><?php echo "The next Payroll will be on $payrollDate"; ?></div>
    </div>
</div>

<script>
    // Common settings for all charts to keep them clean
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } }, // Hide legend to save space
        scales: {
            y: { display: false }, // Hide axes for cleaner look in small card
            x: { display: false }
        }
    };

    // 1. Department Chart
    new Chart(document.getElementById('departmentChart'), {
        type: 'bar',
        data: {
            labels: ['Total'],
            datasets: [{
                label: 'Departments',
                data: [<?php echo $departmentCount; ?>], // Fixed: Uses actual department count
                backgroundColor: '#F5A25D',
                borderColor: '#F5A25D',
                borderWidth: 1
            }]
        },
        options: commonOptions
    });

    // 2. Designation Chart
    new Chart(document.getElementById('designationChart'), {
        type: 'bar',
        data: {
            labels: ['Total'],
            datasets: [{
                label: 'Designations',
                data: [<?php echo $designationCount; ?>], // Fixed: Uses actual designation count
                backgroundColor: '#b3dd5e',
                borderColor: '#b3dd5e',
                borderWidth: 1
            }]
        },
        options: commonOptions
    });

    // 3. Employee Chart
    new Chart(document.getElementById('employeeChart'), {
        type: 'bar',
        data: {
            labels: ['Total'],
            datasets: [{
                label: 'Employees',
                data: [<?php echo $employeeCount; ?>], // Fixed: Uses actual employee count
                backgroundColor: '#FFB23C',
                borderColor: '#FFB23C',
                borderWidth: 1
            }]
        },
        options: commonOptions
    });

    // REMOVED: Branch, Shift, and Holiday charts initialization to avoid errors.
</script>

</body>
</html>