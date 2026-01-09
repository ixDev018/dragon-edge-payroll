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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
</head>
<body>

<div class="container mt-4">
    <div class="card">
        <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="title">
            <i class="fas fa-hourglass-half"></i>
            Shifts View
        </div>
        <button class="add-shift-btn">
            <i class="fas fa-plus"></i> Add Shift
        </button>
        <div class="table-container">
        <table class="table table-bordered table-striped table-hover" id="shiftTable">
            <thead>
                <tr>
                    <th>Shift ID</th>
                    <th>Shift Name</th>
                    <th>Shift In</th>
                    <th>Shift Out</th>
                    <th>View More</th>
                    <th>Update</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
</div>


<script>
$(document).ready(function() {
    $('#shiftTable').DataTable();
});
</script>

<!-- Add Shift Modal -->
<div id="addShiftModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Add Shift</h2>
        <form id="addShiftForm">
            <input type="text" id="shiftName" placeholder="Shift Name" required>
            <input type="time" id="shiftIn" required>
            <input type="time" id="shiftOut" required>
            <button type="submit">Add Shift</button>
        </form>
    </div>
</div>

<script>
$(document).ready(function () {
    $(".add-shift-btn").click(function () {
        $("#addShiftModal").fadeIn();
    });

    $(".close").click(function () {
        $("#addShiftModal").fadeOut();
    });

    $("#addShiftForm").submit(function (e) {
        e.preventDefault();

        let shiftName = $("#shiftName").val();
        let shiftIn = $("#shiftIn").val();
        let shiftOut = $("#shiftOut").val();

        $.ajax({
            url: "add_shift.php",
            type: "POST",
            data: {
                shift_name: shiftName,
                shift_in: shiftIn,
                shift_out: shiftOut
            },
            success: function (response) {
                Swal.fire("Success", "Shift added successfully!", "success");
                $("#addShiftModal").fadeOut();
                $("#shiftTable").DataTable().ajax.reload();
            }
        });
    });
});

</script>

<script>
    $(document).ready(function() {
        $('#addShiftModal').hide();
        shiftTable(); 
    });
    $(document).ready(function () {
    let table = $("#shiftTable").DataTable({
        ajax: "fetch_shifts.php",
        destroy: true,
        columns: [
            { data: "shift_id" },
            { data: "shift_name" },
            { data: "shift_in" },
            { data: "shift_out" },
            {
                data: "shift_id",
                render: function (data) {
                    return `<button class="action-btn view-btn" data-id="${data}"><i class="fas fa-eye"></i> View</button>`;
                }
            },
            {
                data: "shift_id",
                render: function (data) {
                    return `<button class="action-btn update-btn" data-id="${data}"><i class="fas fa-edit"></i> Update</button>`;
                }
            },
            {
                data: "shift_id",
                render: function (data) {
                    return `<button class="action-btn delete-btn" data-id="${data}"><i class="fas fa-trash"></i> Delete</button>`;
                }
            }
        ]
    });
});
    $("#addShiftForm").submit(function (e) {
        e.preventDefault();

        let shiftName = $("#shiftName").val();
        let shiftIn = $("#shiftIn").val();
        let shiftOut = $("#shiftOut").val();

        $.ajax({
            url: "add_shift.php",
            type: "POST",
            data: {
                shift_name: shiftName,
                shift_in: shiftIn,
                shift_out: shiftOut
            },
            success: function (response) {
                Swal.fire("Success", "Shift added successfully!", "success");
                $("#addShiftModal").fadeOut();
                $("#addShiftForm")[0].reset();

                table.destroy();
                table = $("#shiftTable").DataTable({
                    ajax: "fetch_shifts.php",
                    destroy: true
                });
            }
        });
    });

</script>


<!-- View Shift Modal -->
<div id="viewShiftModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeViewModal()">&times;</span>
        <h2>Shift Details</h2>
        <p><strong>Shift ID:</strong> <span id="viewShiftID"></span></p>
        <div class="separator"></div>
        <p><strong>Shift Name:</strong> <span id="viewShiftName"></span></p>
        <div class="separator"></div>
        <p><strong>Shift In:</strong> <span id="viewShiftIn"></span></p>
        <div class="separator"></div>
        <p><strong>Shift Out:</strong> <span id="viewShiftOut"></span></p>
        <div class="separator"></div>
        <p><strong>Created Date:</strong> <span id="viewCreatedDate"></span></p>
        <div class="separator"></div>
        <p><strong>Modified Date:</strong> <span id="viewModifiedDate"></span></p>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#viewShiftModal').hide();
        shiftTable(); 
    });

        $(document).ready(function () {
            if (!$.fn.DataTable.isDataTable("#shiftTable")) {
                var table = $("#shiftTable").DataTable({
                    destroy: true, 
                    ajax: "fetch_shifts.php",
                    columns: [
                        { data: "shift_id" },
                        { data: "shift_name" },
                        { data: "shift_in" },
                        { data: "shift_out" },
                        {
                            data: "shift_id",
                            render: function (data) {
                                return `<button class="action-btn view-btn" data-id="${data}"><i class="fas fa-eye"></i> View</button>`;
                            }
                        },
                        {
                            data: "shift_id",
                            render: function (data) {
                                return `<button class="action-btn update-btn" data-id="${data}"><i class="fas fa-edit"></i> Update</button>`;
                            }
                        },
                        {
                            data: "shift_id",
                            render: function (data) {
                                return `<button class="action-btn delete-btn" data-id="${data}"><i class="fas fa-trash"></i> Delete</button>`;
                            }
                        }
                    ]
                });
            }

            $(document).on("click", ".view-btn", function () {
                let shiftID = $(this).data("id");

                $.ajax({
                    url: "fetch_shift_details.php",
                    type: "POST",
                    data: { shift_id: shiftID },
                    success: function (response) {
                        let shift = JSON.parse(response);
                        $("#viewShiftID").text(shift.shift_id);
                        $("#viewShiftName").text(shift.shift_name);
                        $("#viewShiftIn").text(shift.shift_in);
                        $("#viewShiftOut").text(shift.shift_out);
                        $("#viewCreatedDate").text(shift.created_date);
                        $("#viewModifiedDate").text(shift.modified_date);
                        $("#viewShiftModal").fadeIn();
                    }
                });
            });

            function closeViewModal() {
                $("#viewShiftModal").fadeOut();
            }
            $(".close").click(closeViewModal);

            $(document).on("click", ".delete-btn", function () {
                let shiftID = $(this).data("id");

                Swal.fire({
                    title: "Are you sure?",
                    text: "This shift will be permanently deleted!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Yes, delete it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "delete_shift.php",
                            type: "POST",
                            data: { shift_id: shiftID },
                            success: function (response) {
                                Swal.fire("Deleted!", "Shift has been removed.", "success");
                                $("#shiftTable").DataTable().ajax.reload(null, false); // Reload DataTable without reinitializing
                            }
                        });
                    }
                });
            });
        });
    </script>

<!-- Update Shift Modal -->
<div id="updateShiftModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeUpdateModal()">&times;</span>
        <h2>Update Shift</h2>
        <form id="updateShiftForm">
            <input type="hidden" id="updateShiftID">
            <label>Shift Name</label>
            <input type="text" id="updateShiftName" required>
            <label>Shift In</label>
            <input type="time" id="updateShiftIn" required>
            <label>Shift Out</label>
            <input type="time" id="updateShiftOut" required>
            <button type="submit">Update Shift</button>
        </form>
    </div>
</div>


<script>
    $(document).ready(function() {
        $('#updateShiftModal').hide();
        shiftTable(); 
    });
$(document).on("click", ".update-btn", function () {
    let shiftID = $(this).data("id");

    $.ajax({
        url: "fetch_shift_details.php",
        type: "POST",
        data: { shift_id: shiftID },
        success: function (response) {
            let shift = JSON.parse(response);

            $("#updateShiftID").val(shift.shift_id);
            $("#updateShiftName").val(shift.shift_name);
            $("#updateShiftIn").val(shift.shift_in);
            $("#updateShiftOut").val(shift.shift_out);

            $("#updateShiftModal").fadeIn();
        }
    });
});

$("#updateShiftForm").submit(function (e) {
    e.preventDefault();

    let shiftID = $("#updateShiftID").val();
    let shiftName = $("#updateShiftName").val();
    let shiftIn = $("#updateShiftIn").val();
    let shiftOut = $("#updateShiftOut").val();

    $.ajax({
        url: "update_shift.php",
        type: "POST",
        data: {
            shift_id: shiftID,
            shift_name: shiftName,
            shift_in: shiftIn,
            shift_out: shiftOut
        },
        success: function (response) {
            Swal.fire("Updated!", "Shift details have been updated.", "success");
            $("#updateShiftModal").fadeOut();
            $("#shiftTable").DataTable().ajax.reload(null, false); // Reload without reinitializing
        }
    });
});

function closeUpdateModal() {
    $("#updateShiftModal").fadeOut();
}
$(".close").click(closeUpdateModal);


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

        .add-shift-btn {
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

        .add-shift-btn i {
            font-size: 18px;
        }

        .add-shift-btn:hover {
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


        body.dark-theme .add-shift-btn:hover, 
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
