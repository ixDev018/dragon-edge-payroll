<?php
    session_start();
    include 'sidebar.php';
    include 'db_connection.php';

    // Simple Delete Logic
    if (isset($_GET['delete_id'])) {
        $id = $_GET['delete_id'];
        $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo "<script>
                Swal.fire('Deleted!', 'Employee has been removed.', 'success')
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
    <!-- Use the same CSS/JS as your dashboard for consistency -->
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
    </style>
</head>
<body>

<div class="main-content">
    <div class="header-flex">
        <h1>Employees</h1>
        <!-- IMPORTANT: This links to the fixed add_employee.php -->
        <a href="add_employee.php" class="btn-add">+ Add Employee</a>
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
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Select columns that ACTUALLY exist in your database
                $sql = "SELECT id, employee_id, first_name, last_name, department, position, daily_rate, employment_status FROM employees ORDER BY id DESC";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        // Safe check for missing data
                        $fullName = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
                        $dept = htmlspecialchars($row['department'] ?? 'N/A');
                        $pos = htmlspecialchars($row['position'] ?? 'N/A');
                        $rate = number_format($row['daily_rate'], 2);
                        $status = htmlspecialchars($row['employment_status']);
                        
                        // Determine badge color
                        $statusClass = ($status == 'regular') ? 'status-regular' : 'status-probationary';

                        echo "<tr>";
                        echo "<td>" . $row['employee_id'] . "</td>";
                        echo "<td><strong>" . $fullName . "</strong></td>";
                        echo "<td>" . $dept . "</td>";
                        echo "<td>" . $pos . "</td>";
                        echo "<td>₱" . $rate . "</td>";
                        echo "<td><span class='status-badge $statusClass'>" . ucfirst($status) . "</span></td>";
                        echo "<td>
                                <a href='add_employee.php?id=" . $row['id'] . "' class='btn-action btn-view'><i class='fas fa-edit'></i> Edit</a>
                                <a href='employees.php?delete_id=" . $row['id'] . "' class='btn-action btn-delete' onclick='return confirm(\"Are you sure?\")'><i class='fas fa-trash'></i></a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' style='text-align:center'>No employees found. Click 'Add Employee' to create one.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>