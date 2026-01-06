<?php
// payroll_master.php - AUTOMATED VERSION
require 'db_connection.php';
include 'sidebar.php';

$empList = $conn->query("SELECT id, employee_id, first_name, last_name, daily_rate FROM employees WHERE employment_status != 'Archived'");
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Payroll Master - Automated</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="styles.css">
    
    <style>
        .main-content { margin-left: 280px; padding: 40px; }
        .card { border-radius: 15px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .bg-gradient-primary { background: linear-gradient(45deg, #0d6efd, #0dcaf0); color: white; }
        .calc-preview { background: #f8f9fa; border-radius: 10px; padding: 20px; border: 2px solid #dee2e6; }
        .calc-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.95rem; }
        .calc-section { margin-bottom: 15px; }
        .calc-section-title { font-weight: 600; color: #6c757d; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 8px; }
        .calc-total { font-weight: bold; font-size: 1.2rem; border-top: 2px solid #0d6efd; padding-top: 10px; margin-top: 10px; color: #0d6efd; }
        .auto-badge { background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .attendance-details { max-height: 300px; overflow-y: auto; background: white; border-radius: 8px; padding: 10px; }
        .attendance-day { padding: 8px; border-bottom: 1px solid #e9ecef; font-size: 0.85rem; }
        .attendance-day:last-child { border-bottom: none; }
    </style>
</head>
<body>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark">
                <i class="fas fa-file-invoice-dollar text-primary"></i> Automated Payroll System
                <span class="auto-badge ms-2"><i class="fas fa-robot"></i> AUTO</span>
            </h2>
            <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#createPayrollModal">
                <i class="fas fa-calculator"></i> Generate Payroll
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
                            <th>Days</th>
                            <th>Gross Pay</th>
                            <th>Deductions</th>
                            <th>Net Pay</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="payrollBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- AUTOMATED PAYROLL MODAL -->
    <div class="modal fade" id="createPayrollModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary">
                    <h5 class="modal-title">
                        <i class="fas fa-robot"></i> Auto-Calculate Payroll from Attendance
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="save_payroll.php" method="POST" id="payrollForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Employee Selection -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Select Employee</label>
                                <select class="form-select" name="employee_id" id="empSelect" required>
                                    <option value="">-- Choose Employee --</option>
                                    <?php while($e = $empList->fetch_assoc()): ?>
                                        <option value="<?= $e['id'] ?>">
                                            <?= $e['last_name'] . ", " . $e['first_name'] ?> (<?= $e['employee_id'] ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Date Range -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Period Start</label>
                                <input type="date" name="start_date" id="startDateInput" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Period End</label>
                                <input type="date" name="end_date" id="endDateInput" class="form-control" required>
                            </div>

                            <div class="col-12">
                                <button type="button" class="btn btn-primary w-100" onclick="calculateFromAttendance()">
                                    <i class="fas fa-calculator"></i> Calculate from Attendance
                                </button>
                            </div>

                            <hr>

                            <!-- CALCULATION PREVIEW -->
                            <div class="col-12">
                                <div id="calculationPreview" style="display:none;">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="calc-preview">
                                                <h6 class="text-muted fw-bold mb-3">
                                                    <i class="fas fa-chart-line"></i> Earnings
                                                </h6>
                                                
                                                <div class="calc-section">
                                                    <div class="calc-section-title">Work Summary</div>
                                                    <div class="calc-row">
                                                        <span>Days Worked:</span>
                                                        <span id="daysWorked" class="fw-bold">0</span>
                                                    </div>
                                                    <div class="calc-row">
                                                        <span>Total Hours:</span>
                                                        <span id="totalHours" class="fw-bold">0</span>
                                                    </div>
                                                    <div class="calc-row">
                                                        <span>Overtime Hours:</span>
                                                        <span id="overtimeHours" class="text-success fw-bold">0</span>
                                                    </div>
                                                </div>

                                                <div class="calc-section">
                                                    <div class="calc-section-title">Pay Breakdown</div>
                                                    <div class="calc-row">
                                                        <span>Basic Pay:</span>
                                                        <span id="basicPay">₱0.00</span>
                                                    </div>
                                                    <div class="calc-row">
                                                        <span>Overtime Pay:</span>
                                                        <span id="overtimePay" class="text-success">₱0.00</span>
                                                    </div>
                                                    <div class="calc-row fw-bold text-success">
                                                        <span>GROSS PAY:</span>
                                                        <span id="grossPay">₱0.00</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="calc-preview">
                                                <h6 class="text-muted fw-bold mb-3">
                                                    <i class="fas fa-minus-circle"></i> Deductions
                                                </h6>
                                                
                                                <div class="calc-section">
                                                    <div class="calc-section-title">Attendance Issues</div>
                                                    <div class="calc-row">
                                                        <span>Late (hrs):</span>
                                                        <span id="lateHours" class="text-danger fw-bold">0</span>
                                                    </div>
                                                    <div class="calc-row">
                                                        <span>Late Deduction:</span>
                                                        <span id="lateDeduction" class="text-danger">₱0.00</span>
                                                    </div>
                                                </div>

                                                <div class="calc-section">
                                                    <div class="calc-section-title">Government Contributions</div>
                                                    <div class="calc-row">
                                                        <span>SSS:</span>
                                                        <span id="sss">₱0.00</span>
                                                    </div>
                                                    <div class="calc-row">
                                                        <span>PhilHealth:</span>
                                                        <span id="philhealth">₱0.00</span>
                                                    </div>
                                                    <div class="calc-row">
                                                        <span>Pag-IBIG:</span>
                                                        <span id="pagibig">₱0.00</span>
                                                    </div>
                                                    <div class="calc-row fw-bold text-danger">
                                                        <span>TOTAL DEDUCTIONS:</span>
                                                        <span id="totalDeductions">₱0.00</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="calc-preview mt-3">
                                        <div class="calc-total">
                                            <span>NET PAY (Take Home):</span>
                                            <span id="netPay">₱0.00</span>
                                        </div>
                                    </div>

                                    <!-- Attendance Details -->
                                    <div class="mt-3">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#attendanceDetails">
                                            <i class="fas fa-list"></i> View Daily Attendance Breakdown
                                        </button>
                                        <div class="collapse mt-2" id="attendanceDetails">
                                            <div class="attendance-details" id="attendanceList"></div>
                                        </div>
                                    </div>

                                    <!-- Hidden Fields for Form Submission -->
                                    <input type="hidden" name="days_worked" id="hiddenDays">
                                    <input type="hidden" name="total_hours" id="hiddenHours">
                                    <input type="hidden" name="ot_hours" id="hiddenOT">
                                    <input type="hidden" name="late_hours" id="hiddenLate">
                                    <input type="hidden" name="gross_pay" id="hiddenGross">
                                    <input type="hidden" name="deductions" id="hiddenDeductions">
                                    <input type="hidden" name="net_pay" id="hiddenNet">
                                </div>

                                <div id="noDataMessage" style="display:none;" class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    No attendance records found for this employee in the selected period.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success fw-bold" id="saveBtn" style="display:none;">
                            <i class="fas fa-save"></i> Save Payroll
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Payslip Modal -->
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
    // Success modal trigger
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('msg') === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Payroll Saved!',
                text: 'The payroll has been automatically calculated and saved.',
                confirmButtonColor: '#0d6efd',
                timer: 3000
            }).then(() => {
                window.history.replaceState(null, null, window.location.pathname);
                loadData();
            });
        }
        loadData();
    });

    // Auto-calculate from attendance
    async function calculateFromAttendance() {
        const empId = document.getElementById('empSelect').value;
        const startDate = document.getElementById('startDateInput').value;
        const endDate = document.getElementById('endDateInput').value;

        if (!empId || !startDate || !endDate) {
            Swal.fire('Error', 'Please select employee and date range', 'error');
            return;
        }

        // Show loading
        Swal.fire({
            title: 'Calculating...',
            text: 'Fetching attendance data',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const url = `calculate_payroll_from_attendance.php?employee_id=${empId}&start_date=${startDate}&end_date=${endDate}`;
            console.log('Fetching URL:', url);
            
            const response = await fetch(url);
            
            // Get the raw text first
            const text = await response.text();
            console.log('Raw response:', text);
            
            // Try to parse as JSON
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                Swal.close();
                Swal.fire('Error', 'Server returned invalid response. Check browser console (F12).', 'error');
                throw new Error('Invalid JSON from server');
            }
            
            console.log('Parsed data:', data);

            Swal.close();

            if (data.error) {
                Swal.fire('Error', data.error, 'error');
                return;
            }

            if (data.attendance.days_worked === 0) {
                document.getElementById('noDataMessage').style.display = 'block';
                document.getElementById('calculationPreview').style.display = 'none';
                document.getElementById('saveBtn').style.display = 'none';
                return;
            }

            // Hide no data message
            document.getElementById('noDataMessage').style.display = 'none';
            document.getElementById('calculationPreview').style.display = 'block';
            document.getElementById('saveBtn').style.display = 'block';

            // Update UI
            document.getElementById('daysWorked').textContent = data.attendance.days_worked;
            document.getElementById('totalHours').textContent = data.attendance.total_hours.toFixed(2);
            document.getElementById('overtimeHours').textContent = data.attendance.overtime_hours.toFixed(2);
            document.getElementById('lateHours').textContent = data.attendance.late_hours.toFixed(2);

            document.getElementById('basicPay').textContent = '₱' + data.calculation.basic_pay.toLocaleString();
            document.getElementById('overtimePay').textContent = '₱' + data.calculation.overtime_pay.toLocaleString();
            document.getElementById('grossPay').textContent = '₱' + data.calculation.gross_pay.toLocaleString();

            document.getElementById('lateDeduction').textContent = '₱' + data.calculation.late_deduction.toLocaleString();
            document.getElementById('sss').textContent = '₱' + data.calculation.sss.toLocaleString();
            document.getElementById('philhealth').textContent = '₱' + data.calculation.philhealth.toLocaleString();
            document.getElementById('pagibig').textContent = '₱' + data.calculation.pagibig.toLocaleString();
            document.getElementById('totalDeductions').textContent = '₱' + data.calculation.total_deductions.toLocaleString();

            document.getElementById('netPay').textContent = '₱' + data.calculation.net_pay.toLocaleString();

            // Update hidden fields
            document.getElementById('hiddenDays').value = data.attendance.days_worked;
            document.getElementById('hiddenHours').value = data.attendance.total_hours;
            document.getElementById('hiddenOT').value = data.attendance.overtime_hours;
            document.getElementById('hiddenLate').value = data.attendance.late_hours;
            document.getElementById('hiddenGross').value = data.calculation.gross_pay;
            document.getElementById('hiddenDeductions').value = data.calculation.total_deductions;
            document.getElementById('hiddenNet').value = data.calculation.net_pay;

            // Show attendance details
            let attendanceHTML = '';
            data.attendance.details.forEach(day => {
                attendanceHTML += `
                    <div class="attendance-day">
                        <strong>${day.date}</strong><br>
                        ${day.clock_in} - ${day.clock_out} 
                        (${day.hours} hrs)
                        ${day.overtime > 0 ? `<span class="text-success ms-2">+${day.overtime} OT</span>` : ''}
                        ${day.late_minutes > 0 ? `<span class="text-danger ms-2">-${day.late_minutes} min late</span>` : ''}
                    </div>
                `;
            });
            document.getElementById('attendanceList').innerHTML = attendanceHTML;

        } catch (error) {
            Swal.close();
            console.error('Calculation error:', error);
            Swal.fire('Error', 'Failed to calculate payroll: ' + error.message, 'error');
        }
    }

    // Filter form
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        loadData();
    });

    // Load payroll data
    async function loadData() {
        const tbody = document.getElementById('payrollBody');
        const start = document.getElementById('start_date').value;
        const end = document.getElementById('end_date').value;
        const search = document.getElementById('search_name').value;

        tbody.innerHTML = '<tr><td colspan="7" class="text-center p-3 text-muted">Loading...</td></tr>';

        try {
            const params = new URLSearchParams({ start, end, search });
            const res = await fetch(`payroll_data.php?${params.toString()}`);
            const data = await res.json();

            tbody.innerHTML = '';
            if (data.length > 0) {
                data.forEach(row => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${row.period_display}</td>
                            <td class="fw-bold">${row.employee_name}</td>
                            <td>${row.days_worked || 'N/A'}</td>
                            <td>₱${row.gross_display}</td>
                            <td class="text-danger">₱${row.deductions_display}</td>
                            <td class="fw-bold text-success">₱${row.net_display}</td>
                            <td>
                                <button onclick="viewPayslip(${row.id})" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center p-3 text-muted">No records found.</td></tr>';
            }
        } catch (error) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center p-3 text-danger">Error loading data.</td></tr>';
        }
    }

    function viewPayslip(id) {
        document.getElementById('payslipFrame').src = 'payslip.php?id=' + id;
        new bootstrap.Modal(document.getElementById('payslipModal')).show();
    }
</script>
</body>
</html>