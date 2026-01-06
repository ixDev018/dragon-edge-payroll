<?php
// accounts.php
session_start();
include 'db_connection.php'; 

// --- 2. BACKEND LOGIC (Handle Archive/Restore) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $empPk  = $_POST['id'] ?? ''; 

    if ($action && $empPk) {
        try {
            // Determine target status
            $newStatus = ($action === 'archive') ? 'Archived' : 'Regular';
            
            // 1. First, check what the current status is
            $checkStmt = $conn->prepare("SELECT employment_status FROM employees WHERE id = ?");
            $checkStmt->bind_param("i", $empPk);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $currentData = $checkResult->fetch_assoc();
            $checkStmt->close();

            if (!$currentData) {
                throw new Exception("Employee not found.");
            }

            // If already in the target status, just return success
            if (strtolower($currentData['employment_status']) === strtolower($newStatus)) {
                 echo json_encode(["status" => "success", "message" => "Account is already $newStatus."]);
                 exit;
            }

            // 2. Perform the Update
            $stmt = $conn->prepare("UPDATE employees SET employment_status = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $newStatus, $empPk);
                $stmt->execute();
                
                // 3. CRITICAL CHECK: Did the row actually change?
                if ($stmt->affected_rows > 0) {
                    echo json_encode(["status" => "success", "message" => "Account status updated to $newStatus."]);
                } else {
                    // If affected_rows is 0, it means the DB rejected the value (ENUM issue) or ID was wrong
                    throw new Exception("Database refused the status '$newStatus'. Your 'employment_status' column might be an ENUM that doesn't allow this value.");
                }
                $stmt->close();
            } else {
                throw new Exception("Prepare failed: " . $conn->error);
            }

        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        exit;
    }
}

// --- 3. DATA FETCHING ---
$sql = "
    SELECT 
        e.id AS emp_pk,
        e.employee_id AS emp_code,
        CONCAT(e.first_name, ' ', e.last_name) AS full_name,
        u.email,
        u.password,
        e.employment_status
    FROM employees e
    JOIN users u ON e.id = u.employee_id
    ORDER BY e.id ASC
";

$result = $conn->query($sql);
$accounts = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $accounts[] = $row;
    }
}

$activeAccounts = array_filter($accounts, fn($a) => strtolower($a['employment_status']) !== 'archived');
$archivedAccounts = array_filter($accounts, fn($a) => strtolower($a['employment_status']) === 'archived');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Accounts | Dragon Edge</title>
    
    <!-- Dependencies -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="styles.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');
        
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: #f4f6f9; /* Light gray background for the whole page */
            margin: 0;
        }

        /* --- YOUR ORIGINAL STYLES --- */
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
        .header-flex h1 {
            font-size: 24px;
            color: #333;
            margin: 0;
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
            vertical-align: middle;
        }
        th {
            background-color: #f8f9fa;
            color: #333;
            font-weight: 600;
        }
        
        /* Badges */
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            margin-top: 4px;
        }
        .status-regular { background: #d4edda; color: #155724; }
        .status-probationary { background: #fff3cd; color: #856404; }
        /* Added for completeness */
        .status-archived { background: #e2e3e5; color: #383d41; }
        
        /* Buttons */
        .btn-add {
            background: #d9534f;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-add:hover {
            background-color: #c9302c;
        }
        
        .btn-action {
            padding: 6px 12px;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            font-size: 12px;
            margin-right: 5px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-view { background: #17a2b8; }
        .btn-view:hover { background: #138496; }
        
        .btn-delete { background: #dc3545; }
        .btn-delete:hover { background: #c82333; }
        
        /* Helper for the toggle button to look blue instead of red if desired, 
           or we can use btn-view. I'll use a custom class for the toggle 
           based on btn-add but blue */
        .btn-toggle {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-toggle:hover {
            background: #0056b3;
        }

        /* Password Field Styling to match table look */
        .password-container {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 4px;
            width: fit-content;
        }
        .password-input {
            border: none;
            background: transparent;
            outline: none;
            width: 80px;
            font-size: 12px;
            color: #666;
            letter-spacing: 2px;
        }
        .password-toggle {
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            margin-left: 5px;
        }
        .password-toggle:hover {
            color: #333;
        }

        .hidden { display: none; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <!-- Header -->
        <div class="header-flex">
            <div>
                <h1>Employee Accounts</h1>
            </div>
            
            <button id="toggleViewBtn" onclick="toggleView()" class="btn-toggle">
                <i class="fas fa-eye"></i> <span id="toggleText">Show Archived Accounts</span>
            </button>
        </div>

        <!-- Main Card -->
        <div class="table-container">
            
            <!-- ACTIVE ACCOUNTS TABLE -->
            <div id="activeSection">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee Name</th>
                            <th>Email</th>
                            <th>Password</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($activeAccounts) > 0): ?>
                            <?php foreach ($activeAccounts as $row): ?>
                            <tr>
                                <!-- ID -->
                                <td><?= htmlspecialchars($row['emp_code']) ?></td>
                                
                                <!-- Name & Status -->
                                <td>
                                    <div>
                                        <div style="font-weight: 600; font-size: 14px; margin-bottom: 2px;">
                                            <?= htmlspecialchars($row['full_name']) ?>
                                        </div>
                                        <?php 
                                            $status = strtolower($row['employment_status']);
                                            $badgeClass = ($status == 'regular') ? 'status-regular' : 'status-probationary';
                                        ?>
                                        <span class="status-badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars($row['employment_status']) ?>
                                        </span>
                                    </div>
                                </td>
                                
                                <!-- Email -->
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                
                                <!-- Password -->
                                <td>
                                    <div class="password-container">
                                        <input type="password" value="********" readonly class="password-input">
                                        <button onclick="showPasswordInfo()" class="password-toggle">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                                
                                <!-- Action -->
                                <td style="text-align: right;">
                                    <button onclick="confirmAction(<?= $row['emp_pk'] ?>, 'archive')" class="btn-action btn-delete">
                                        <i class="fas fa-box-archive"></i> Archive
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center; padding: 30px;">No active accounts found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ARCHIVED ACCOUNTS TABLE -->
            <div id="archivedSection" class="hidden">
                <div style="padding: 15px; background: #fff3cd; color: #856404; margin-bottom: 15px; border-radius: 5px; font-size: 14px;">
                    <i class="fas fa-archive"></i> Viewing Archived Accounts
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee Name</th>
                            <th>Email</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($archivedAccounts) > 0): ?>
                            <?php foreach ($archivedAccounts as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['emp_code']) ?></td>
                                <td>
                                    <div>
                                        <div style="font-weight: 600; font-size: 14px;">
                                            <?= htmlspecialchars($row['full_name']) ?>
                                        </div>
                                        <span class="status-badge status-archived">Archived</span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td style="text-align: right;">
                                    <button onclick="confirmAction(<?= $row['emp_pk'] ?>, 'restore')" class="btn-action btn-view">
                                        <i class="fas fa-undo"></i> Restore
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; padding: 30px;">No archived accounts.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <script>
        // --- VIEW TOGGLE LOGIC ---
        let showingArchived = false;

        function toggleView() {
            showingArchived = !showingArchived;
            const activeSec = document.getElementById('activeSection');
            const archiveSec = document.getElementById('archivedSection');
            const btnText = document.getElementById('toggleText');
            const btnIcon = document.querySelector('#toggleViewBtn i');

            if (showingArchived) {
                activeSec.classList.add('hidden');
                archiveSec.classList.remove('hidden');
                btnText.innerText = "Show Active Accounts";
                btnIcon.classList.remove('fa-eye');
                btnIcon.classList.add('fa-eye-slash');
            } else {
                activeSec.classList.remove('hidden');
                archiveSec.classList.add('hidden');
                btnText.innerText = "Show Archived Accounts";
                btnIcon.classList.remove('fa-eye-slash');
                btnIcon.classList.add('fa-eye');
            }
        }

        function showPasswordInfo() {
            Swal.fire({
                icon: 'info',
                title: 'Security Notice',
                text: 'Passwords are encrypted and cannot be shown.',
                confirmButtonColor: '#3b82f6'
            });
        }

        // --- ARCHIVE / RESTORE LOGIC ---
        function confirmAction(id, action) {
            const isArchive = action === 'archive';
            Swal.fire({
                title: isArchive ? 'Archive Account?' : 'Restore Account?',
                text: isArchive ? "This will mark the employee status as Archived." : "This will set the employee status back to Regular.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: isArchive ? '#dc3545' : '#17a2b8',
                cancelButtonColor: '#6c757d',
                confirmButtonText: isArchive ? 'Yes, Archive it!' : 'Yes, Restore it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    performAction(id, action);
                }
            });
        }

        async function performAction(id, action) {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('action', action);

            try {
                const response = await fetch('accounts.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.status === 'success') {
                    Swal.fire(
                        'Success!',
                        result.message,
                        'success'
                    ).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'System error occurred.', 'error');
            }
        }
    </script>
</body>
</html>