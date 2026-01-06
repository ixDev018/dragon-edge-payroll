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
</head>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>

<div class="container">
    <div class="card">
        <div class="title">
            <i class="fas fa-store"></i>
            Location View
        </div>
        <button class="add-branch-btn" onclick="openModal()">
            <i class="fas fa-plus"></i> Add Location
        </button>

        <div class="table-container">
            <table id="branchTable">
                <thead>
                    <tr>
                        <th>Location ID</th>
                        <th>Location Name</th>
                        <th>Phone Number</th>
                        <th>View More</th>
                        <th>Update</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody id="branchTableBody">

                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="branchModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Add Branch</h2>
            <input type="text" id="branch_name" placeholder="Location Name">
            <input type="text" id="phone_number" placeholder="Phone Number">
            <input type="text" id="address" placeholder="Location Address">
            <input type="text" id="manager_name" placeholder="Manager Name">
            <input type="email" id="email" placeholder="Email Address">
            <input type="text" id="operating_hours" placeholder="Operating Hours">
            <button onclick="submitBranch()">Add Location</button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#branchTable').DataTable();
    });

    function openModal() {
        document.getElementById("branchModal").style.display = "flex";
    }

    function closeModal() {
        document.getElementById("branchModal").style.display = "none";
    }
</script>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('#branchModal').hide();
        loadBranches(); 
    });

    function loadBranches() {
        $.ajax({
            url: "fetch_branches.php",
            type: "GET",
            success: function(data) {
                $("#branchTableBody").html(data);
            },
            error: function() {
                $("#branchTableBody").html("<tr><td colspan='6'>Failed to load data</td></tr>");
            }
        });
    }

    function submitBranch() {
    var branchName = document.getElementById("branch_name").value;
    var phoneNumber = document.getElementById("phone_number").value;
    var address = document.getElementById("address").value;
    var managerName = document.getElementById("manager_name").value;
    var email = document.getElementById("email").value;
    var operatingHours = document.getElementById("operating_hours").value;

    if (branchName === "" || phoneNumber === "" || address === "" || managerName === "" || email === "" || operatingHours === "") {
        Swal.fire("Error", "All fields are required!", "error");
        return;
    }

    $.ajax({
        url: "save_branch.php",
        type: "POST",
        data: { 
            branch_name: branchName, 
            phone_number: phoneNumber,
            address: address,
            manager_name: managerName,
            email: email,
            operating_hours: operatingHours
        },
        dataType: "json",
        success: function(response) {
            if (response.status === "success") {
                Swal.fire("Success", response.message, "success").then(() => {
                    closeModal();
                    loadBranches();
                });
            } else {
                Swal.fire("Error", response.message, "error");
            }
        },
        error: function() {
            Swal.fire("Error", "Something went wrong!", "error");
        }
    });
}

</script>

<!-- View Branch Modal -->
<div id="viewBranchModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeViewModal()">&times;</span>
        <h2 class="modal-title">Location Details</h2>
        <div class="modal-body">
            <div class="branch-info">
                <p><span class="label">Location ID:</span> <span id="view_branch_id"></span></p>
                <div class="separator"></div>
                <p><span class="label">Location Name:</span> <span id="view_branch_name"></span></p>
                <div class="separator"></div>
                <p><span class="label">Phone Number:</span> <span id="view_phone_number"></span></p>
                <div class="separator"></div>
                <p><span class="label">Location Address:</span> <span id="view_address"></span></p>
                <div class="separator"></div>
                <p><span class="label">Manager Name:</span> <span id="view_manager_name"></span></p>
                <div class="separator"></div>
                <p><span class="label">Email Address:</span> <span id="view_email"></span></p>
                <div class="separator"></div>
                <p><span class="label">Operating Hours:</span> <span id="view_operating_hours"></span></p>
            </div>
        </div>
    </div>
</div>


<script>
    $(document).ready(function() {
        $('#viewBranchModal').hide();
        viewBranch(); 
    });
    function viewBranch(branchId) {
        $.ajax({
            url: "view_branch.php",
            type: "POST",
            data: { branch_id: branchId },
            dataType: "json",
            success: function(response) {
                if (response.status === "success") {
                    const data = response.data;
                    $("#view_branch_id").text(data.branch_id);
                    $("#view_branch_name").text(data.branch_name);
                    $("#view_phone_number").text(data.phone_number);
                    $("#view_address").text(data.address);
                    $("#view_manager_name").text(data.manager_name);
                    $("#view_email").text(data.email);
                    $("#view_operating_hours").text(data.operating_hours);

                    $("#viewBranchModal").show();
                } else {
                    Swal.fire("Success", response.message, "success");
                }
            },

        });
    }

    function closeViewModal() {
        $("#viewBranchModal").hide();
    }
</script>

<script>
    function deleteBranch(branchId) {
        Swal.fire({
            title: "Are you sure?",
            text: "This action cannot be undone!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#e74c3c",
            cancelButtonColor: "#3498db",
            confirmButtonText: "Yes, delete it!",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "delete_branch.php",
                    type: "POST",
                    data: { branch_id: branchId },
                    dataType: "json",
                    success: function(response) {
                        if (response.status === "success") {
                            Swal.fire("Deleted!", response.message, "success").then(() => {
                                loadBranches();
                            });
                        } else {
                            Swal.fire("Error", response.message, "error");
                        }
                    },
                    error: function() {
                        Swal.fire("Error", "Something went wrong!", "error");
                    }
                });
            }
        });
    }
</script>

<!-- Update Branch Modal -->
<div id="updateBranchModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeUpdateModal()">&times;</span>
        <h2>Update Location</h2>
        <input type="hidden" id="update_branch_id">
        <label>Location Name:</label>
        <input type="text" id="update_branch_name">
        <label>Phone Number:</label>
        <input type="text" id="update_phone_number">
        <label>Address:</label>
        <input type="text" id="update_address">
        <label>Manager Name:</label>
        <input type="text" id="update_manager_name">
        <label>Email Address:</label>
        <input type="email" id="update_email">
        <label>Operating Hours:</label>
        <input type="text" id="update_operating_hours">
        <button onclick="submitUpdateBranch()">Save Changes</button>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#updateBranchModal').hide();
        openUpdateModal(); 
    });
    function openUpdateModal(branchId) {
        $.ajax({
            url: "view_branch.php",
            type: "POST",
            data: { branch_id: branchId },
            dataType: "json",
            success: function(response) {
                if (response.status === "success") {
                    const data = response.data;
                    $("#update_branch_id").val(data.branch_id);
                    $("#update_branch_name").val(data.branch_name);
                    $("#update_phone_number").val(data.phone_number);
                    $("#update_address").val(data.address);
                    $("#update_manager_name").val(data.manager_name);
                    $("#update_email").val(data.email);
                    $("#update_operating_hours").val(data.operating_hours);
                    $("#updateBranchModal").show();
                } else {
                    Swal.fire("Error", response.message, "error");
                }
            },

        });
    }

    function closeUpdateModal() {
        $("#updateBranchModal").hide();
    }

    function submitUpdateBranch() {
        var branchId = $("#update_branch_id").val();
        var branchName = $("#update_branch_name").val();
        var phoneNumber = $("#update_phone_number").val();
        var address = $("#update_address").val();
        var managerName = $("#update_manager_name").val();
        var email = $("#update_email").val();
        var operatingHours = $("#update_operating_hours").val();

        if (branchName === "" || phoneNumber === "" || address === "" || managerName === "" || email === "" || operatingHours === "") {
            Swal.fire("Error", "All fields are required!", "error");
            return;
        }

        $.ajax({
            url: "update_branch.php",
            type: "POST",
            data: {
                branch_id: branchId,
                branch_name: branchName,
                phone_number: phoneNumber,
                address: address,
                manager_name: managerName,
                email: email,
                operating_hours: operatingHours
            },
            dataType: "json",
            success: function(response) {
                if (response.status === "success") {
                    Swal.fire("Success", response.message, "success").then(() => {
                        closeUpdateModal();
                        loadBranches();
                    });
                } else {
                    Swal.fire("Error", response.message, "error");
                }
            },
            error: function() {
                Swal.fire("Error", "Something went wrong!", "error");
            }
        });
    }
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

        .add-branch-btn {
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

        .add-branch-btn i {
            font-size: 18px;
        }

        .add-branch-btn:hover {
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


        body.dark-theme .add-branch-btn:hover, 
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



    </style>
</body>
</html>
