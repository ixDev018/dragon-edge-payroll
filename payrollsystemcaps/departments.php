<?php
// departments.php
session_start();
include 'db_connection.php';

// --- 1. BACKEND LOGIC ---

// Handle JSON responses for AJAX actions
if (isset($_GET['action']) || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['status' => 'error', 'message' => 'Invalid request'];
    
    // ACTION: Add Department
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $deptName = trim($_POST['dept_name']);
        if ($deptName) {
            // FIX: 'departments' table requires a 'code' and uses 'name' column (not dept_name)
            // We generate a simple code automatically: First 3 letters uppercase + random number
            $code = strtoupper(substr($deptName, 0, 3)) . rand(100, 999);
            
            $stmt = $conn->prepare("INSERT INTO departments (name, code, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("ss", $deptName, $code);
            
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Department added successfully!'];
            } else {
                $response = ['status' => 'error', 'message' => 'Database error: ' . $stmt->error];
            }
        }
        echo json_encode($response);
        exit;
    }

    // ACTION: Update Department
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $id = $_POST['dept_id'];
        $name = trim($_POST['dept_name']);
        
        if ($id && $name) {
            // FIX: Column is 'name', not 'dept_name'
            $stmt = $conn->prepare("UPDATE departments SET name = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $name, $id);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Department updated successfully!'];
            } else {
                $response = ['status' => 'error', 'message' => 'Update failed: ' . $stmt->error];
            }
        }
        echo json_encode($response);
        exit;
    }

    // ACTION: Delete Department
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['dept_id'];
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Department deleted.'];
            } else {
                $response = ['status' => 'error', 'message' => 'Delete failed: ' . $stmt->error];
            }
        }
        echo json_encode($response);
        exit;
    }

    // ACTION: Get Single Department (for View/Update Modal)
    if (isset($_GET['action']) && $_GET['action'] === 'get_dept') {
        $id = $_GET['id'];
        $stmt = $conn->prepare("SELECT * FROM departments WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        if ($data) {
            // Normalize keys for JS (Map DB columns to JS expected names)
            echo json_encode([
                'status' => 'success',
                'dept_id' => $data['id'],
                'dept_name' => $data['name'], // Map DB 'name' to JS 'dept_name'
                'created_date' => $data['created_at'],
                'modified_date' => $data['updated_at']
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Not found']);
        }
        exit;
    }
}

// --- 2. FETCH DATA FOR VIEW ---
$departments = [];
try {
    // Select all departments
    $result = $conn->query("SELECT * FROM departments ORDER BY id ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $departments[] = $row;
        }
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
    <title>Departments | Dragon Edge Group</title>
    
    <!-- Dependencies -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- DataTables -->
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

        /* --- Layout: Centralized Content --- */
        .main-content {
            margin-left: 300px; /* Sidebar space */
            padding: 40px;
            /* Flexbox for centering */
            display: flex;
            flex-direction: column;
            align-items: center; /* Centers children horizontally */
            min-height: calc(100vh - 80px); /* Ensures full height visual */
        }

        /* --- Header & Card Width Control --- */
        .header-actions, .card {
            width: 100%;
            max-width: 1200px; /* Restricts width on large screens */
        }

        /* --- Header --- */
        .title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 26px;
            font-weight: 600;
            color: #2c3e50;
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

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        table {
            width: 100% !important;
            border-collapse: collapse;
        }
        /* UPDATED: Center Align Headers */
        th {
            background-color: #f8f9fa;
            color: #333;
            font-weight: 600;
            padding: 15px;
            text-align: center; 
            border-bottom: 2px solid #eee;
        }
        /* UPDATED: Center Align Cells */
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
            text-align: center; 
        }

        /* --- Buttons --- */
        .add-dept-btn {
            background: #FF6B6B;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }
        .add-dept-btn:hover {
            background: #E63946;
            transform: translateY(-2px);
        }

        .action-btn {
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-right: 5px;
            transition: 0.2s;
            width: 100px; /* Fixed width for uniformity */
            justify-content: center;
        }
        .view-btn { background: #3498db; }
        .view-btn:hover { background: #2980b9; }

        .update-btn { background: #f39c12; }
        .update-btn:hover { background: #d35400; }

        .delete-btn { background: #e74c3c; }
        .delete-btn:hover { background: #c0392b; }

        /* --- Modal --- */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 400px;
            text-align: left;
            position: relative;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .modal-content h2 {
            margin-top: 0;
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #aaa;
        }
        .close:hover { color: #333; }
        
        .modal input {
            width: 100%;
            padding: 12px;
            margin: 10px 0 20px 0;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
        }
        .modal button[type="submit"] {
            width: 100%;
            background: #FF6B6B;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
        }
        .modal button[type="submit"]:hover { background: #E63946; }

        /* View Modal Specifics */
        .detail-row {
            margin-bottom: 15px;
            font-size: 14px;
        }
        .detail-label {
            font-weight: 600;
            color: #666;
            display: block;
            margin-bottom: 4px;
        }
        .detail-value {
            color: #333;
            font-size: 16px;
        }
        .separator {
            height: 1px;
            background: #eee;
            margin: 10px 0;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    
    <div class="header-actions">
        <div class="title">
            <i class="fas fa-building"></i>
            Departments View
        </div>
        <button class="add-dept-btn" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add Department
        </button>
    </div>

    <div class="card">
        <?php if (isset($dbError)): ?>
            <div style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong>Database Error:</strong> <?= htmlspecialchars($dbError) ?><br>
                <small>Ensure the 'departments' table exists and has columns 'id', 'name', 'code'.</small>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table class="table table-bordered table-striped table-hover" id="deptTable">
                <thead>
                    <tr>
                        <th>Department ID</th>
                        <th>Department Name</th>
                        <th>View More</th>
                        <th>Update</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($departments)): ?>
                        <?php foreach ($departments as $dept): ?>
                        <tr>
                            <td><?= htmlspecialchars($dept['id']) ?></td>
                            <td><strong><?= htmlspecialchars($dept['name']) ?></strong></td>
                            
                            <td>
                                <button class="action-btn view-btn" onclick="openViewModal(<?= $dept['id'] ?>)">
                                    <i class="fas fa-eye"></i> VIEW
                                </button>
                            </td>
                            <td>
                                <button class="action-btn update-btn" onclick="openUpdateModal(<?= $dept['id'] ?>)">
                                    <i class="fas fa-edit"></i> UPDATE
                                </button>
                            </td>
                            <td>
                                <button class="action-btn delete-btn" onclick="deleteDept(<?= $dept['id'] ?>)">
                                    <i class="fas fa-trash"></i> DELETE
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Department Modal -->
<div id="addDeptModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('addDeptModal')">&times;</span>
        <h2>Add Department</h2>
        <form id="addDeptForm">
            <input type="hidden" name="action" value="add">
            <label class="detail-label">Department Name</label>
            <input type="text" name="dept_name" placeholder="e.g. Human Resources" required>
            <button type="submit">Add Department</button>
        </form>
    </div>
</div>

<!-- Update Department Modal -->
<div id="updateDeptModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('updateDeptModal')">&times;</span>
        <h2>Update Department</h2>
        <form id="updateDeptForm">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="updateDeptID" name="dept_id">
            
            <label class="detail-label">Department Name</label>
            <input type="text" id="updateDeptName" name="dept_name" required>
            <button type="submit">Save Changes</button>
        </form>
    </div>
</div>

<!-- View Department Modal -->
<div id="viewDeptModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('viewDeptModal')">&times;</span>
        <h2>Department Details</h2>
        
        <div class="detail-row">
            <span class="detail-label">Department Name:</span>
            <span class="detail-value" id="viewDeptName">Loading...</span>
        </div>
        <div class="separator"></div>
        <div class="detail-row">
            <span class="detail-label">Created Date:</span>
            <span class="detail-value" id="viewCreatedDate">-</span>
        </div>
        <div class="separator"></div>
        <div class="detail-row">
            <span class="detail-label">Last Modified:</span>
            <span class="detail-value" id="viewModifiedDate">-</span>
        </div>
    </div>
</div>

<script>
    // Initialize DataTable
    $(document).ready(function() {
        $('#deptTable').DataTable({
            "paging": true,
            "ordering": true,
            "info": true,
            "language": {
                "emptyTable": "No departments found. Click 'Add Department' to create one."
            }
        });
    });

    // --- Modal Functions ---
    function openAddModal() {
        $('#addDeptModal').fadeIn().css('display', 'flex');
    }

    function closeModal(modalId) {
        $('#' + modalId).fadeOut();
    }

    // --- AJAX: Add Department ---
    $('#addDeptForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.post('departments.php', formData, function(response) {
            try {
                const res = JSON.parse(response);
                if (res.status === 'success') {
                    Swal.fire('Success', res.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            } catch(e) {
                console.error("Parse Error: ", response);
                Swal.fire('Error', 'Unexpected response from server.', 'error');
            }
        });
    });

    // --- AJAX: Open Update Modal (Fetch Data first) ---
    function openUpdateModal(id) {
        $.get('departments.php', { action: 'get_dept', id: id }, function(response) {
            const res = JSON.parse(response);
            if (res.status === 'success') {
                $('#updateDeptID').val(res.dept_id);
                $('#updateDeptName').val(res.dept_name); // Populates from the mapped 'name'
                $('#updateDeptModal').fadeIn().css('display', 'flex');
            } else {
                Swal.fire('Error', 'Could not fetch department details.', 'error');
            }
        });
    }

    // --- AJAX: Submit Update ---
    $('#updateDeptForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.post('departments.php', formData, function(response) {
            const res = JSON.parse(response);
            if (res.status === 'success') {
                Swal.fire('Success', res.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        });
    });

    // --- AJAX: View Modal ---
    function openViewModal(id) {
        $('#viewDeptModal').fadeIn().css('display', 'flex');
        // Clear previous data
        $('#viewDeptName').text('Loading...');
        
        $.get('departments.php', { action: 'get_dept', id: id }, function(response) {
            const res = JSON.parse(response);
            if (res.status === 'success') {
                $('#viewDeptName').text(res.dept_name);
                $('#viewCreatedDate').text(res.created_date);
                $('#viewModifiedDate').text(res.modified_date);
            }
        });
    }

    // --- AJAX: Delete ---
    function deleteDept(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This department will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('departments.php', { action: 'delete', dept_id: id }, function(response) {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        Swal.fire('Deleted!', res.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                });
            }
        });
    }

    // Close modals when clicking outside
    $(window).click(function(event) {
        if ($(event.target).hasClass('modal')) {
            $('.modal').fadeOut();
        }
    });
</script>

</body>
</html>