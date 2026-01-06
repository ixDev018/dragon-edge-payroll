<?php
include 'db_connection.php'; 

$sql = "SELECT * FROM branches";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['branch_id']}</td>
                <td>{$row['branch_name']}</td>
                <td>{$row['phone_number']}</td>
                <td><button class='action-btn view-btn' onclick='viewBranch({$row['branch_id']})'><b><i class='fas fa-eye'></i> VIEW</b></button></td>
                <td><button class='action-btn update-btn' onclick='openUpdateModal({$row['branch_id']})'><b><i class='fas fa-edit'></i> UPDATE</b></button></td>
                <td><button class='action-btn delete-btn' onclick='deleteBranch({$row['branch_id']})'><b><i class='fas fa-trash'></i> DELETE</b></button></td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='6' class='no-data'>No branches found</td></tr>";
}

$conn->close();
?>



<style>

.action-btn {
    padding: 8px 14px;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease-in-out;
    color: white;
}

.view-btn {
    background: #3498db;
}

.view-btn:hover {
    background: #2980b9;
}

.update-btn {
    background: #f39c12;
}

.update-btn:hover {
    background: #e67e22;
}

.delete-btn {
    background: #e74c3c;
}

.delete-btn:hover {
    background: #c0392b;
}

.no-data {
    text-align: center;
    font-style: italic;
    color: #777;
    font-weight: bold;
}

    </style>