<?php
// payroll_master.php
require 'db_connection.php';
include 'sidebar.php';

// FIX: Select 'id' (Primary Key) instead of just 'employee_id'
$empList = $conn->query("SELECT id, employee_id, first_name, last_name, daily_rate FROM employees WHERE employment_status != 'Archived'");
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Payroll Master</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link rel="stylesheet" href="styles.css"> 
    
    <style>
        .main-content { margin-left: 280px; padding: 40px; }
        .card { border-radius: 15px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .bg-gradient-primary { background: linear-gradient(45deg, #0d6efd, #0dcaf0); color: white; }
        .calc-preview { background: #f8f9fa; border-radius: 10px; padding: 15px; border: 1px dashed #ced4da; }
        .calc-row { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 0.9rem; }
        .calc-total { font-weight: bold; font-size: 1.1rem; border-top: 1px solid #ccc; padding-top: 5px; margin-top: 5px; }
    </style>
</head>
<body>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark"><i class="fas fa-file-invoice-dollar text-primary"></i> Payroll Master</h2>
            <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#createPayrollModal">
                <i class="fas fa-plus-circle"></i> Create New Payroll
            </button>
        </div>

        <div class="card p-4">
            <form id="filterForm" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold">From</label>
                    <input type="date" id="start_date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold">To</label>
                    <input type="date" id="end_date" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted fw-bold">Search</label>
                    <input type="text" id="search_name" class="form-control" placeholder="Employee Name...">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-dark w-100"><i class="fas fa-filter"></i> Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Period</th>
                            <th>Employee</th>
                            <th>Gross Pay</th>
                            <th>Deductions</th>
                            <th>Net Pay</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="payrollBody">
                        </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createPayrollModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary">
                    <h5 class="modal-title"><i class="fas fa-calculator"></i> Calculate Payroll</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="save_payroll.php" method="POST">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Select Employee</label>
                                <select class="form-select" name="employee_id" id="empSelect" required onchange="updateRate()">
                                    <option value="" data-rate="0">-- Choose Employee --</option>
                                    <?php while($e = $empList->fetch_assoc()): ?>
                                        <option value="<?= $e['id'] ?>" data-rate="<?= $e['daily_rate'] ?>">
                                            <?= $e['last_name'] . ", " . $e['first_name'] ?> (<?= $e['employee_id'] ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Period Start</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Period End</label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>

                            <hr>

                            <div class="col-md-4">
                                <label class="form-label text-success fw-bold">Days Worked</label>
                                <input type="number" name="days_worked" id="daysInput" class="form-control" step="0.5" value="0" oninput="calculatePreview()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-success fw-bold">Overtime (Hrs)</label>
                                <input type="number" name="ot_hours" id="otInput" class="form-control" step="1" value="0" oninput="calculatePreview()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-danger fw-bold">Late (Hrs)</label>
                                <input type="number" name="late_hours" id="lateInput" class="form-control" step="1" value="0" oninput="calculatePreview()">
                            </div>
                             <div class="col-md-12">
                                <label class="form-label text-danger fw-bold">Other Deductions (Loans/Cash Advance)</label>
                                <input type="number" name="other_deductions" id="otherInput" class="form-control" step="0.01" value="0" oninput="calculatePreview()">
                            </div>

                            <div class="col-12 mt-4">
                                <div class="calc-preview">
                                    <h6 class="text-muted text-uppercase small fw-bold">Estimated Computation</h6>
                                    <div class="calc-row"><span>Basic Pay:</span> <span id="prevBasic">0.00</span></div>
                                    <div class="calc-row"><span>Overtime Pay:</span> <span id="prevOT">0.00</span></div>
                                    <div class="calc-row text-danger"><span>Deductions (Late + Gov):</span> <span id="prevDed">0.00</span></div>
                                    <div class="calc-row calc-total text-primary"><span>NET PAY:</span> <span id="prevNet">0.00</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success fw-bold"><i class="fas fa-save"></i> Save Payroll</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="payslipModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <iframe id="payslipFrame" style="width:100%; height:600px; border:none;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // --- SUCCESS MODAL TRIGGER ---
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('msg') === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Payroll Saved!',
                    text: 'The payroll record has been successfully calculated and saved.',
                    confirmButtonColor: '#0d6efd',
                    timer: 3000
                }).then(() => {
                    window.history.replaceState(null, null, window.location.pathname);
                });
            }
        });

        // --- CALCULATOR LOGIC ---
        let currentDailyRate = 0;

        function updateRate() {
            const select = document.getElementById('empSelect');
            const option = select.options[select.selectedIndex];
            currentDailyRate = parseFloat(option.getAttribute('data-rate')) || 0;
            calculatePreview();
        }

        function calculatePreview() {
            const days = parseFloat(document.getElementById('daysInput').value) || 0;
            const ot = parseFloat(document.getElementById('otInput').value) || 0;
            const late = parseFloat(document.getElementById('lateInput').value) || 0;
            const other = parseFloat(document.getElementById('otherInput').value) || 0;

            const hourly = currentDailyRate / 8;
            
            const basicPay = days * currentDailyRate;
            const otPay = ot * (hourly * 1.25);
            const gross = basicPay + otPay;

            const lateDed = late * hourly;
            const govDed = (gross * 0.045) + 100 + 200; 
            const totalDed = lateDed + govDed + other;

            const net = gross - totalDed;

            document.getElementById('prevBasic').innerText = basicPay.toFixed(2);
            document.getElementById('prevOT').innerText = otPay.toFixed(2);
            document.getElementById('prevDed').innerText = totalDed.toFixed(2);
            document.getElementById('prevNet').innerText = "₱" + net.toLocaleString(undefined, {minimumFractionDigits: 2});
        }

        // --- FILTER & LOAD DATA LOGIC (UPDATED) ---
        
        // 1. Listen for the Filter Form Submit
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Stop page refresh
            loadData(); // Reload table with new filters
        });

        // 2. Load Data on Page Load (Default: Show All)
        document.addEventListener('DOMContentLoaded', () => loadData());
        
        async function loadData() {
            const tbody = document.getElementById('payrollBody');
            
            // Get values from inputs
            const start = document.getElementById('start_date').value;
            const end = document.getElementById('end_date').value;
            const search = document.getElementById('search_name').value;

            // Show Loading State
            tbody.innerHTML = '<tr><td colspan="6" class="text-center p-3 text-muted">Loading records...</td></tr>';

            try {
                // Build URL with Query Parameters
                const params = new URLSearchParams({
                    start: start,
                    end: end,
                    search: search
                });

                const res = await fetch(`payroll_data.php?${params.toString()}`); 
                const data = await res.json();
                
                tbody.innerHTML = '';
                if(data.length > 0) {
                    data.forEach(row => {
                        tbody.innerHTML += `
                            <tr>
                                <td>${row.period_display}</td>
                                <td class="fw-bold">${row.employee_name}</td>
                                <td>${row.gross_display}</td>
                                <td class="text-danger">${row.deductions_display}</td>
                                <td class="fw-bold text-success">₱${row.net_display}</td>
                                <td>
                                    <button onclick="viewPayslip(${row.id})" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> Payslip</button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center p-3 text-muted">No payroll records found for this criteria.</td></tr>';
                }
            } catch (error) {
                console.error(error);
                tbody.innerHTML = '<tr><td colspan="6" class="text-center p-3 text-danger">Error loading data.</td></tr>';
            }
        }

        function viewPayslip(id) {
            document.getElementById('payslipFrame').src = 'payslip.php?id=' + id;
            new bootstrap.Modal(document.getElementById('payslipModal')).show();
        }
    </script>
</body>
</html>