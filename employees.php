<?php
    session_start();
    include 'sidebar.php';
    include 'db_connection.php';
    require __DIR__ . '/vendor/autoload.php';

    use PhpMqtt\Client\MqttClient;
    use PhpMqtt\Client\ConnectionSettings;

    // Delete Logic with Fingerprint Removal
    if (isset($_GET['delete_id'])) {
        $id = $_GET['delete_id'];
        
        // First, get the fingerprint_id before deleting
        $stmt = $conn->prepare("SELECT fingerprint_id, first_name, last_name FROM employees WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $employee = $result->fetch_assoc();
        
        if ($employee && $employee['fingerprint_id']) {
            $fingerprint_id = $employee['fingerprint_id'];
            $name = $employee['first_name'] . ' ' . $employee['last_name'];
            
            // Send delete command to ESP32 via MQTT
            try {
                $mqtt_server = "fad64f7d54c740f7b5b3679bdba0f4cf.s1.eu.hivemq.cloud";
                $mqtt_port = 8883;
                $mqtt_user = "dragonedge";
                $mqtt_pass = "DragonEdge2025!";
                
                $connectionSettings = (new ConnectionSettings)
                    ->setUsername($mqtt_user)
                    ->setPassword($mqtt_pass)
                    ->setUseTls(true)
                    ->setTlsSelfSignedAllowed(true)
                    ->setTlsVerifyPeer(false)
                    ->setTlsVerifyPeerName(false);
                
                $mqtt = new MqttClient($mqtt_server, $mqtt_port, "DragonEdge-Delete-" . uniqid());
                $mqtt->connect($connectionSettings, true);
                
                $message = json_encode([
                    'action' => 'delete',
                    'fingerprint_id' => $fingerprint_id,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                
                $mqtt->publish('dragonedge/fingerprint/delete', $message, 0);
                $mqtt->disconnect();
            } catch (Exception $e) {
                // Log error but continue with database deletion
                error_log("MQTT delete failed: " . $e->getMessage());
            }
        }
        
        // Delete from database
        $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo "<script>
                Swal.fire('Deleted!', 'Employee and fingerprint removed.', 'success')
                .then(() => { window.location.href = 'employees.php'; });
            </script>";
        } else {
            echo "<script>Swal.fire('Error', 'Failed to delete employee.', 'error');</script>";
        }
    }
    
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employees List</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .main-content {
            margin-left: 300px;
            padding: 40px;
        }
        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #f8f9fa;
            color: #333;
            font-weight: 600;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-regular { background: #d4edda; color: #155724; }
        .status-probationary { background: #fff3cd; color: #856404; }
        
        .btn-add {
            background: #d9534f;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
        }
        .btn-action {
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            font-size: 12px;
            margin-right: 5px;
        }
        .btn-view { background: #17a2b8; }
        .btn-delete { background: #dc3545; }
        .btn-clear-all {
            background: #ff6b6b;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            font-weight: bold;
            cursor: pointer;
            margin-left: 10px;
        }
        .btn-clear-all:hover {
            background: #ff5252;
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="header-flex">
        <h1>Employees</h1>
        <div>
            <a href="add_employee.php" class="btn-add">+ Add Employee</a>
            <button onclick="clearAllFingerprints()" class="btn-clear-all">
                <i class="fas fa-trash-alt"></i> Clear All Fingerprints
            </button>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Daily Rate</th>
                    <th>Fingerprint ID</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT id, employee_id, first_name, last_name, department, position, daily_rate, fingerprint_id, employment_status FROM employees ORDER BY id DESC";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $fullName = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
                        $dept = htmlspecialchars($row['department'] ?? 'N/A');
                        $pos = htmlspecialchars($row['position'] ?? 'N/A');
                        $rate = number_format($row['daily_rate'], 2);
                        $fpId = $row['fingerprint_id'] ? '#' . $row['fingerprint_id'] : '<span style="color:#999">Not enrolled</span>';
                        $status = htmlspecialchars($row['employment_status']);
                        
                        $statusClass = ($status == 'regular') ? 'status-regular' : 'status-probationary';

                        echo "<tr>";
                        echo "<td>" . $row['employee_id'] . "</td>";
                        echo "<td><strong>" . $fullName . "</strong></td>";
                        echo "<td>" . $dept . "</td>";
                        echo "<td>" . $pos . "</td>";
                        echo "<td>₱" . $rate . "</td>";
                        echo "<td>" . $fpId . "</td>";
                        echo "<td><span class='status-badge $statusClass'>" . ucfirst($status) . "</span></td>";
                        echo "<td>
                                <a href='add_employee.php?id=" . $row['id'] . "' class='btn-action btn-view'><i class='fas fa-edit'></i> Edit</a>
                                <a href='#' onclick='deleteEmployee(" . $row['id'] . ", \"" . addslashes($fullName) . "\")' class='btn-action btn-delete'><i class='fas fa-trash'></i></a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' style='text-align:center'>No employees found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function deleteEmployee(id, name) {
    Swal.fire({
        title: 'Delete Employee?',
        html: `This will remove <strong>${name}</strong> and their fingerprint from the sensor.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'employees.php?delete_id=' + id;
        }
    });
}

function clearAllFingerprints() {
    Swal.fire({
        title: 'Clear ALL Fingerprints?',
        html: '⚠️ This will delete <strong>ALL</strong> fingerprints from the ESP32 sensor.<br>Employees will need to re-enroll.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ff6b6b',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, clear all!',
        input: 'checkbox',
        inputPlaceholder: 'I understand this action cannot be undone'
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            fetch('delete_fingerprint_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'clear_all'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Cleared!', data.message, 'success')
                    .then(() => {
                        // Optionally reload the page to show updated fingerprint status
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Failed to send command: ' + error, 'error');
            });
        } else if (result.isConfirmed) {
            Swal.fire('Cancelled', 'You must confirm to proceed', 'info');
        }
    });
}
</script>

</body>
</html>