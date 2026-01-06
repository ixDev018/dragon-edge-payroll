<?php

include 'db_connection.php';
include 'APP_sidebar.php';

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Fingerprint Registration</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body class="p-5">
        <h3 class="mb-4">Register Fingerprint</h3>
        <form id="regForm" class="mb-3">
            <div class="mb-3">
                <label for="employee_id" class="form-label">Employee ID</label>
                <input type="text" id="employee_id" class="form-control" placeholder="Enter employee ID" required>
            </div>
            <button type="button" class="btn btn-primary" id="scanBtn">Scan Fingerprint</button>
        </form>

        <script>
            const scanButton = document.getElementById('scanBtn');
            scanButton.addEventListener('click', async () => {
                const id = document.getElementById('employee_id').value.trim();
                if (!id) {
                    Swal.fire('Error', 'Please enter Employee ID', 'error');
                    return;
                }

                Swal.fire({ title: 'Place finger on scanner...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                try {
                    const res = await fetch(`http://localhost:8080/register_fingerprint?employee_id=${id}`);
                    const data = await res.json();
                    if (data.ok) {
                    Swal.fire('Success', data.message, 'success');
                    } else {
                    Swal.fire('Error', data.error || 'Registration failed', 'error');
                    }
                } catch (err) {
                    Swal.fire('Bridge Error', 'Cannot connect to Fingerprint Bridge', 'error');
                }
            })
        </script>
    </body>
</html>