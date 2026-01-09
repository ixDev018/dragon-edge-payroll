<?php
session_start();
include 'db_connection.php';
include 'sidebar.php';

// Get today's attendance
$today_attendance = [];
$sql = "SELECT * FROM v_biometric_attendance_today ORDER BY clock_in DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $today_attendance[] = $row;
    }
}

// Get device status
$devices = [];
$sql = "SELECT * FROM biometric_devices ORDER BY last_seen DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $devices[] = $row;
    }
}

// Statistics
$stats = [
    'total_employees' => 0,
    'present_today' => 0,
    'late_today' => 0,
    'absent_today' => 0
];

$stmt = $conn->query("
    SELECT 
        COUNT(*) as total_employees,
        SUM(CASE WHEN al.clock_in IS NOT NULL THEN 1 ELSE 0 END) as present_today,
        SUM(CASE WHEN al.status = 'late' THEN 1 ELSE 0 END) as late_today,
        SUM(CASE WHEN al.clock_in IS NULL THEN 1 ELSE 0 END) as absent_today
    FROM employees e
    LEFT JOIN attendance_logs al ON e.id = al.employee_id AND al.attendance_date = CURDATE()
    WHERE e.is_active = 1
");
$stats = $stmt->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biometric Dashboard | Dragon Edge</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .main-content {
            margin-left: 280px;
            padding: 30px;
        }
        
        .dashboard-header {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .dashboard-header h1 {
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 28px;
        }
        
        .dashboard-header h1 i {
            color: #667eea;
            font-size: 32px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .stat-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-icon.orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.red { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        
        .stat-info h3 {
            color: #7f8c8d;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 700;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .card-header h2 {
            color: #2c3e50;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .refresh-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .refresh-btn:hover {
            transform: scale(1.05);
        }
        
        .attendance-list {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .attendance-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }
        
        .attendance-item:hover {
            background: #f8f9fa;
        }
        
        .attendance-item:last-child {
            border-bottom: none;
        }
        
        .employee-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .employee-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
        }
        
        .employee-details h4 {
            color: #2c3e50;
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        .employee-details p {
            color: #7f8c8d;
            font-size: 13px;
        }
        
        .time-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .time-badge.in {
            background: #d4edda;
            color: #155724;
        }
        
        .time-badge.out {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 5px;
        }
        
        .status-badge.present { background: #d4edda; color: #155724; }
        .status-badge.late { background: #fff3cd; color: #856404; }
        .status-badge.absent { background: #f8d7da; color: #721c24; }
        
        .device-item {
            padding: 15px;
            border: 2px solid #f0f0f0;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .device-item:hover {
            border-color: #667eea;
            transform: translateX(5px);
        }
        
        .device-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .device-name {
            color: #2c3e50;
            font-weight: 600;
            font-size: 15px;
        }
        
        .device-status {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .device-status.online { background: #d4edda; color: #155724; }
        .device-status.offline { background: #f8d7da; color: #721c24; }
        
        .device-info {
            font-size: 13px;
            color: #7f8c8d;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .pulse {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #28a745;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.3;
        }
        
        /* Auto-refresh indicator */
        .auto-refresh {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: white;
            padding: 15px 20px;
            border-radius: 50px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #2c3e50;
            z-index: 1000;
        }
        
        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="dashboard-header">
        <h1>
            <i class="fas fa-fingerprint"></i>
            Real-time Biometric Attendance
        </h1>
    </div>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3>Total Employees</h3>
                <p><?php echo $stats['total_employees']; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3>Present Today</h3>
                <p><?php echo $stats['present_today']; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3>Late Today</h3>
                <p><?php echo $stats['late_today']; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-info">
                <h3>Absent Today</h3>
                <p><?php echo $stats['absent_today']; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="content-grid">
        <!-- Today's Attendance -->
        <div class="card">
            <div class="card-header">
                <h2>
                    <i class="fas fa-list"></i>
                    Today's Attendance
                </h2>
                <button class="refresh-btn" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
            </div>
            
            <div class="attendance-list">
                <?php if (empty($today_attendance)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No attendance records yet today</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($today_attendance as $att): ?>
                        <div class="attendance-item">
                            <div class="employee-info">
                                <div class="employee-avatar">
                                    <?php echo strtoupper(substr($att['first_name'], 0, 1)); ?>
                                </div>
                                <div class="employee-details">
                                    <h4><?php echo htmlspecialchars($att['first_name'] . ' ' . $att['last_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($att['department'] ?? 'N/A'); ?> • <?php echo htmlspecialchars($att['position'] ?? 'N/A'); ?></p>
                                    <?php if ($att['status']): ?>
                                        <span class="status-badge <?php echo $att['status']; ?>">
                                            <?php echo strtoupper($att['status']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <?php if ($att['clock_in']): ?>
                                    <div class="time-badge in">
                                        <i class="fas fa-sign-in-alt"></i>
                                        <?php echo date('h:i A', strtotime($att['clock_in'])); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($att['clock_out']): ?>
                                    <div class="time-badge out">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <?php echo date('h:i A', strtotime($att['clock_out'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Device Status -->
        <div class="card">
            <div class="card-header">
                <h2>
                    <i class="fas fa-microchip"></i>
                    Device Status
                </h2>
            </div>
            
            <?php if (empty($devices)): ?>
                <div class="empty-state">
                    <i class="fas fa-robot"></i>
                    <p>No devices registered</p>
                </div>
            <?php else: ?>
                <?php foreach ($devices as $device): ?>
                    <div class="device-item">
                        <div class="device-header">
                            <span class="device-name"><?php echo htmlspecialchars($device['device_name']); ?></span>
                            <span class="device-status <?php echo $device['status']; ?>">
                                <?php if ($device['status'] === 'online'): ?>
                                    <span class="pulse"></span>
                                <?php endif; ?>
                                <?php echo strtoupper($device['status']); ?>
                            </span>
                        </div>
                        <div class="device-info">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($device['location'] ?? 'Unknown'); ?>
                        </div>
                        <?php if ($device['ip_address']): ?>
                            <div class="device-info">
                                <i class="fas fa-network-wired"></i>
                                <?php echo htmlspecialchars($device['ip_address']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($device['last_seen']): ?>
                            <div class="device-info">
                                <i class="fas fa-clock"></i>
                                Last seen: <?php echo date('M d, h:i A', strtotime($device['last_seen'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Auto-refresh indicator -->
<div class="auto-refresh">
    <i class="fas fa-sync-alt"></i>
    Auto-refreshing every 30 seconds
</div>

<script>
// Auto-refresh every 30 seconds
setTimeout(function() {
    location.reload();
}, 30000);
</script>

</body>
</html>