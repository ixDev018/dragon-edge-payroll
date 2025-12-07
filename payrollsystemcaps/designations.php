<?php include 'sidebar.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dragon Edge Group</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>

<div class="container mt-4">
    <div class="card">
        <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="title">
            <i class="fas fa-user-tie"></i> 
            Designations View
        </div>

            <button class="add-designation-btn">
                <i class="fas fa-plus"></i> Add Designation
            </button>
        </div>

        <div class="table-container">
            <table class="table table-bordered table-striped table-hover" id="designationTable">
                <thead>
                    <tr>
                        <th>Designation ID</th>
                        <th>Designation Name</th>
                        <th>Department Name</th>
                        <th>View More</th>
                        <th>Update</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Designation Modal -->
<div id="addDesignationModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="$('#addDesignationModal').fadeOut();">&times;</span>
        <h2>Add Designation</h2>
        <form id="addDesignationForm">
            <input type="text" id="designationName" placeholder="Designation Name" required>
            <select id="departmentID" required>
                <option value="">Select Department</option>
            </select>
            <button type="submit">Add Designation</button>
        </form>
    </div>
</div>

<!-- View Designation Modal -->
<div id="viewDesignationModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="$('#viewDesignationModal').fadeOut();">&times;</span>
        <h2>Designation Details</h2>
        <p><strong>Designation ID:</strong> <span id="viewDesignationID"></span></p>
        <div class="separator"></div>
        <p><strong>Designation Name:</strong> <span id="viewDesignationName"></span></p>
        <div class="separator"></div>
        <p><strong>Department:</strong> <span id="viewDepartmentName"></span></p>
        <div class="separator"></div>
        <p><strong>Created Date:</strong> <span id="viewCreatedDate"></span></p>
        <div class="separator"></div>
        <p><strong>Modified Date:</strong> <span id="viewModifiedDate"></span></p>
    </div>
</div>

<!-- Update Designation Modal -->
<div id="updateDesignationModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="$('#updateDesignationModal').fadeOut();">&times;</span>
        <h2>Update Designation</h2>
        <form id="updateDesignationForm">
            <input type="hidden" id="updateDesignationID">
            <label for="updateDesignationName">Designation Name</label>
            <input type="text" id="updateDesignationName" required>

            <label for="updateDepartmentID">Department</label>
            <select id="updateDepartmentID" required>
                <option value="">Select Department</option>
            </select>

            <button type="submit">Update Designation</button>
        </form>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function() {
        $('#addDesignationModal').hide();
        $('#viewDesignationModal').hide();
        $('#updateDesignationModal').hide();
        designationTable(); 
    });

    $(document).ready(function () {
    let table = $("#designationTable").DataTable({
        ajax: "fetch_designations.php",
        columns: [
            { data: "designation_id" },
            { data: "designation_name" },
            { data: "department_name" },
            {
                data: "designation_id",
                render: function (data) {
                    return `<button class="action-btn view-btn" data-id="${data}"><i class="fas fa-eye"></i> View</button>`;
                }
            },
            {
                data: "designation_id",
                render: function (data) {
                    return `<button class="action-btn update-btn" data-id="${data}"><i class="fas fa-edit"></i> Update</button>`;
                }
            },
            {
                data: "designation_id",
                render: function (data) {
                    return `<button class="action-btn delete-btn" data-id="${data}"><i class="fas fa-trash"></i> Delete</button>`;
                }
            }
        ]
    });

    $(".add-designation-btn").click(function () {
        $("#addDesignationModal").fadeIn();
        loadDepartments();
    });

    function loadDepartments() {
        $.ajax({
            url: "fetch_departments_dropdown.php",
            type: "GET",
            success: function (response) {
                $("#departmentID").html(response);
            }
        });
    }

    $(document).on("click", ".view-btn", function () {
        let designationID = $(this).data("id");

        $.ajax({
            url: "fetch_designation_details.php",
            type: "POST",
            data: { designation_id: designationID },
            success: function (response) {
                let data = JSON.parse(response);
                $("#viewDesignationID").text(data.designation_id);
                $("#viewDesignationName").text(data.designation_name);
                $("#viewDepartmentName").text(data.department_name);
                $("#viewCreatedDate").text(data.created_date);
                $("#viewModifiedDate").text(data.modified_date);

                $("#viewDesignationModal").fadeIn();
            }
        });
    });

    $(document).on("click", ".delete-btn", function () {
        let designationID = $(this).data("id");

        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to recover this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "delete_designation.php",
                    type: "POST",
                    data: { designation_id: designationID },
                    success: function (response) {
                        Swal.fire("Deleted!", "The designation has been deleted.", "success");
                        table.ajax.reload();
                    }
                });
            }
        });
    });
});
</script>

<script>
$(document).on("click", ".update-btn", function () {
    let designationID = $(this).data("id");

    $.ajax({
        url: "fetch_designation_details.php",
        type: "POST",
        data: { designation_id: designationID },
        success: function (response) {
            let data = JSON.parse(response);
            $("#updateDesignationID").val(data.designation_id);
            $("#updateDesignationName").val(data.designation_name);

            loadUpdateDepartments(data.department_id);

            $("#updateDesignationModal").fadeIn();
        }
    });
});

function loadUpdateDepartments(selectedDepartment) {
    $.ajax({
        url: "fetch_departments_dropdown.php",
        type: "GET",
        success: function (response) {
            $("#updateDepartmentID").html(response);
            $("#updateDepartmentID").val(selectedDepartment);
        }
    });
}

$("#updateDesignationForm").submit(function (e) {
    e.preventDefault();

    let designationID = $("#updateDesignationID").val();
    let designationName = $("#updateDesignationName").val();
    let departmentID = $("#updateDepartmentID").val();

    $.ajax({
        url: "update_designation.php",
        type: "POST",
        data: {
            designation_id: designationID,
            designation_name: designationName,
            department_id: departmentID
        },
        success: function (response) {
            Swal.fire("Updated!", "The designation has been updated.", "success");
            $("#updateDesignationModal").fadeOut();
            $("#designationTable").DataTable().ajax.reload();
        }
    });
});

</script>

<style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg,rgb(92, 92, 92), rgb(92, 92, 92));
            color: #333;
        }

        .container {
            margin-left: 300px;
            padding: 40px;
            max-width: 1200px;
        }

        .card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s;
        }

        .card:hover {
            transform: scale(1.03);
        }

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

        .add-designation-btn {
            float: right;
            background: #FF6B6B;
            color: white;
            padding: 14px 22px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s, transform 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .add-designation-btn i {
            font-size: 18px;
        }

        .add-designation-btn:hover {
            background: #E63946;
            transform: scale(1.05);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            justify-content: center;
            align-items: center;
            
        }

        .modal-content {
            background:#2980b9;
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 400px;
            color: white;
        }

        .close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #fff;
        }

        .modal h2 {
            color: white;
            font-weight: 600;
        }

        .modal input {
            width: 100%;
            color: gray;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 8px;
        }

        .modal button {
            background:rgb(255, 255, 255);
            color:rgb(0, 0, 0);
            padding: 12px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }
        body.dark-theme .title {
            color: white;
        }
        .modal button:hover {
            background: #E63946;
        }
        body.dark-theme .card, 
        body.dark-theme .table-container table, 
        body.dark-theme .table-container , 
        body.dark-theme .table-container table td {
            background: #333333;
            color: white;
        }

        body.dark-theme .table-container,
        body.dark-theme .table-container table td {
            border-color: #444444;
        }


        body.dark-theme .add-designation-btn:hover, 
        body.dark-theme .view-btn:hover, 
        body.dark-theme .update-btn:hover, 
        body.dark-theme .delete-btn:hover {
            background: #444444;
        }

        body.dark-theme .dataTables_length label,
        body.dark-theme .dataTables_filter label,
        body.dark-theme .dataTables_info {
            color: white;
        }

        .action-btn {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
            color: white;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
            justify-content: center;
        }

        .view-btn {
            background: #3498db;
        }

        .view-btn:hover {
            background: #2980b9;
            transform: scale(1.05);
        }

        .update-btn {
            background: #f39c12;
        }

        .update-btn:hover {
            background: #e67e22;
            transform: scale(1.05);
        }

        .delete-btn {
            background: #e74c3c;
        }

        .delete-btn:hover {
            background: #c0392b;
            transform: scale(1.05);
        }

        .action-btn + .action-btn {
            margin-left: 5px;
        }

        .action-btn i {
            font-size: 14px;
        }

    </style>
</body>
</html>
