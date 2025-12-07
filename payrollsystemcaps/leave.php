<?php
// leave.php
session_start();
include 'db_connection.php';

// --- 1. BACKEND LOGIC (Handle Approve/Reject) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Return JSON response
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $leaveId = $_POST['leave_id'] ?? '';
    
    if ($action && $leaveId) {
        $status = ($action === 'approve') ? 'Approved' : 'Rejected';
        
        try {
            // Note: Ensure your table name is correct (leave_requests vs leaves)
            $stmt = $conn->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("si", $status, $leaveId);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => "Leave request marked as $status."]);
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// --- 2. FETCH DATA ---
$leaves = [];
$dbError = null;
$missingTable = false;

try {
    // --- UPDATED QUERY ---
    // We select specific columns from employees table to display nice details
    $query = "
        SELECT 
            lr.id,
            lr.reason,
            lr.start_date,
            lr.end_date,
            lr.status,
            lr.is_paid,
            lr.created_at,
            e.employee_id AS emp_code,       /* The formatted ID (e.g. 2024-12-001) */
            e.first_name,
            e.last_name,
            e.position,
            e.department
        FROM leave_requests lr
        LEFT JOIN employees e ON lr.employee_id = e.id 
        ORDER BY lr.created_at DESC
    ";
    
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $leaves[] = $row;
        }
    }
} catch (mysqli_sql_exception $e) {
    $dbError = $e->getMessage();
    if (strpos($dbError, "doesn't exist") !== false) {
        $missingTable = true;
    }
} catch (Exception $e) {
    $dbError = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Requests | Dragon Edge Group</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    
    <link rel="stylesheet" href="styles.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        /* --- Layout: Centralized Content (Matching Departments) --- */
        .main-content {
            margin-left: 300px; /* Sidebar space */
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center; 
            min-height: calc(100vh - 80px);
        }

        .header-container, .card {
            width: 100%;
            max-width: 1200px;
        }

        /* --- Header --- */
        .title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 26px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .title i {
            font-size: 30px;
            color: #FF6B6B;
        }

        /* --- Card & Table --- */
        .card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100% !important;
            border-collapse: collapse;
        }
        
        th {
            background-color: #f8f9fa;
            color: #333;
            font-weight: 600;
            padding: 15px;
            text-align: center;
            border-bottom: 2px solid #eee;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
            text-align: center;
        }

        /* --- Status Badges --- */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-approved { background: #dcfce7; color: #166534; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-pending { background: #fef9c3; color: #854d0e; }

        /* --- Payment Badges --- */
        .pay-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .pay-paid { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .pay-unpaid { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

        /* --- Buttons --- */
        .action-btn {
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            margin: 2px;
            width: 90px;
            transition: 0.2s;
        }

        .approve-btn { background: #2ecc71; }
        .approve-btn:hover { background: #27ae60; }

        .reject-btn { background: #e74c3c; }
        .reject-btn:hover { background: #c0392b; }

        .view-reason-btn { background: #3498db; width: auto; padding: 6px 12px; }
        .view-reason-btn:hover { background: #2980b9; }

        /* Disabled state for buttons */
        .action-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.7;
        }

        /* --- Helper for DB Error --- */
        .db-error-box {
            background: #fff5f5;
            border: 1px solid #fc8181;
            color: #c53030;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        /* Small text for position */
        .emp-details {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
        }
        .emp-name { font-weight: 600; color: #2c3e50; }
        .emp-role { font-size: 12px; color: #7f8c8d; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    
    <div class="header-container">
        <div class="title">
            <i class="fas fa-calendar-check"></i>
            Leave Requests
        </div>
    </div>

    <div class="card">
        
        <?php if ($missingTable): ?>
            <div class="db-error-box">
                <h3 style="margin-top:0; font-weight:600;"><i class="fas fa-exclamation-triangle"></i> Table Missing</h3>
                <p>The system could not find the <strong>leave_requests</strong> table.</p>
            </div>
        <?php elseif ($dbError): ?>
            <div class="db-error-box">
                <strong>Database Error:</strong> <?= htmlspecialchars($dbError) ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table class="table table-bordered table-striped table-hover" id="leaveTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee Details</th>
                        <th>Leave Reason</th>
                        <th>Payment</th>
                        <th>From Date</th>
                        <th>To Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($leaves)): ?>
                        <?php foreach ($leaves as $row): ?>
                        <tr>
                            <td style="font-weight: bold; color: #555;">
                                <?= htmlspecialchars($row['emp_code'] ?? 'N/A') ?>
                            </td>

                            <td>
                                <div class="emp-details">
                                    <span class="emp-name">
                                        <?= htmlspecialchars(($row['first_name'] ?? 'Unknown') . ' ' . ($row['last_name'] ?? '')) ?>
                                    </span>
                                    <span class="emp-role">
                                        <?= htmlspecialchars(($row['position'] ?? 'No Position') . ' - ' . ($row['department'] ?? 'No Dept')) ?>
                                    </span>
                                </div>
                            </td>
                            
                            <td>
                                <button class="action-btn view-reason-btn" 
                                        onclick="viewReason(this)" 
                                        data-reason="<?= htmlspecialchars($row['reason']) ?>"
                                        data-emp-name="<?= htmlspecialchars(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?>">
                                    <i class="fas fa-eye"></i> View Reason
                                </button>
                            </td>

                            <td>
                                <?php if (!empty($row['is_paid']) && $row['is_paid'] == 1): ?>
                                    <span class="pay-badge pay-paid">Paid</span>
                                <?php else: ?>
                                    <span class="pay-badge pay-unpaid">Unpaid</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['start_date']) ?></td>
                            <td><?= htmlspecialchars($row['end_date']) ?></td>
                            <td>
                                <?php 
                                    $s = strtolower($row['status']);
                                    $badge = 'status-pending';
                                    if ($s === 'approved') $badge = 'status-approved';
                                    if ($s === 'rejected') $badge = 'status-rejected';
                                ?>
                                <span class="status-badge <?= $badge ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (strtolower($row['status']) === 'pending'): ?>
                                    <button class="action-btn approve-btn" onclick="updateStatus(<?= $row['id'] ?>, 'approve')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="action-btn reject-btn" onclick="updateStatus(<?= $row['id'] ?>, 'reject')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: #aaa;">Completed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#leaveTable').DataTable({
            "paging": true,
            "ordering": true,
            "info": true,
            "order": [[ 6, "asc" ], [ 4, "desc" ]], // Order by Status (Pending first) then Date
            "language": {
                "emptyTable": "No leave requests found."
            }
        });
    });

    function viewReason(btn) {
        const reasonText = btn.getAttribute('data-reason');
        const empName = btn.getAttribute('data-emp-name');
        
        Swal.fire({
            title: 'Leave Request Details',
            html: `
                <div style="text-align: left; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                    <span style="color: #6c757d; font-size: 14px;">Submitted by:</span><br>
                    <strong style="color: #2c3e50; font-size: 18px;">${empName}</strong>
                </div>
                <div style="text-align: left;">
                    <span style="color: #6c757d; font-size: 14px;">Reason for Leave:</span>
                    <div style="
                        background: #f8f9fa; 
                        padding: 15px; 
                        border-radius: 8px; 
                        border: 1px solid #e9ecef; 
                        margin-top: 5px;
                        max-height: 250px; 
                        overflow-y: auto; 
                        font-family: inherit;
                        color: #333;
                        line-height: 1.6;
                        white-space: pre-wrap;
                    ">${reasonText}</div>
                </div>
            `,
            icon: 'info',
            width: '600px',
            confirmButtonText: 'Close',
            confirmButtonColor: '#3498db'
        });
    }

    function updateStatus(id, action) {
        const actionText = action === 'approve' ? 'Approve' : 'Reject';
        const confirmColor = action === 'approve' ? '#2ecc71' : '#e74c3c';

        Swal.fire({
            title: `${actionText} Request?`,
            text: `Are you sure you want to ${action} this leave request?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: confirmColor,
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Yes, ${actionText} it!`
        }).then((result) => {
            if (result.isConfirmed) {
                // IMPORTANT: Ensure this points to the correct filename (leave.php)
                $.post('leave.php', {
                    leave_id: id,
                    action: action
                }, function(response) {
                    let res = response;
                    if (typeof response === 'string') {
                        try { res = JSON.parse(response); } catch(e) {}
                    }

                    if (res.status === 'success') {
                        Swal.fire('Success', res.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', res.message || 'Unknown error occurred', 'error');
                    }
                }).fail(function() {
                    Swal.fire('Error', 'Server connection failed', 'error');
                });
            }
        });
    }
</script>

</body>
</html>