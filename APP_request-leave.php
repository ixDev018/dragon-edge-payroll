<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['employee_id'])) {
    header('Location: APP_index.php');
    exit;
}

// --- 1. SMART ID DETECTION ---
$session_val = $_SESSION['employee_id']; 
$real_db_id = 0; 

if (is_numeric($session_val)) {
    $stmt = $conn->prepare("SELECT id FROM employees WHERE id = ?");
    $stmt->bind_param("i", $session_val);
} else {
    $stmt = $conn->prepare("SELECT id FROM employees WHERE employee_id = ?");
    $stmt->bind_param("s", $session_val);
}
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $real_db_id = $res->fetch_assoc()['id'];
} else {
    $real_db_id = intval($session_val); // Fallback
}
$stmt->close();

// --- 2. CALCULATE USED LEAVE CREDITS (For 15-Day Limit) ---
// We count all APPROVED days for the current year, excluding Maternity
$current_year = date('Y');
$used_days = 0;
$annual_limit = 15;

$credit_sql = "
    SELECT start_date, end_date 
    FROM leave_requests 
    WHERE employee_id = ? 
    AND status = 'Approved' 
    AND leave_type != 'Maternity' 
    AND YEAR(start_date) = ?
";
$stmt = $conn->prepare($credit_sql);
$stmt->bind_param("ii", $real_db_id, $current_year);
$stmt->execute();
$c_result = $stmt->get_result();

while ($row = $c_result->fetch_assoc()) {
    $start = new DateTime($row['start_date']);
    $end = new DateTime($row['end_date']);
    // +1 because if start=end, it is 1 day
    $days = $end->diff($start)->days + 1; 
    $used_days += $days;
}
$stmt->close();

$remaining_credits = max(0, $annual_limit - $used_days);


// --- 3. HANDLE FORM SUBMISSION ---
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type = $_POST['leave_type'] ?? 'Vacation';
    $start_date = $_POST['start_date'] ?? null;
    $end_date   = $_POST['end_date'] ?? null;
    $reason     = trim($_POST['reason'] ?? '');

    // Calculate requested duration
    $s_date = new DateTime($start_date);
    $e_date = new DateTime($end_date);
    $requested_days = $e_date->diff($s_date)->days + 1;

    // VALIDATION
    if (!$start_date || !$end_date) {
        $error_message = "Please select both start and end dates.";
    } elseif ($e_date < $s_date) {
        $error_message = "End date cannot be before start date.";
    } else {
        
        // --- LOGIC: CHECK LIMITS & PAY STATUS ---
        $can_proceed = true;
        $is_paid = 0; // Default Unpaid

        if ($leave_type === 'Maternity') {
            // Maternity Exception: Always allowed, Always Paid
            $can_proceed = true;
            $is_paid = 1; 
        } else {
            // Standard Leave: Check 15-day limit
            if (($used_days + $requested_days) > $annual_limit) {
                $can_proceed = false;
                $error_message = "Request exceeds your annual limit! You have $remaining_credits days left, but you requested $requested_days.";
            } else {
                // If within limit, it is Paid
                $is_paid = 1;
            }
        }

        if ($can_proceed) {
            $stmt = $conn->prepare("INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason, is_paid, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
            $stmt->bind_param("issssi", $real_db_id, $leave_type, $start_date, $end_date, $reason, $is_paid);

            if ($stmt->execute()) {
                $_SESSION['show_success_alert'] = true;
                header("Location: APP_request-leave.php");
                exit(); 
            } else {
                $error_message = "Database Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// --- 4. FETCH HISTORY ---
$requests = [];
$stmt = $conn->prepare("SELECT leave_type, start_date, end_date, reason, status, is_paid, created_at FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $real_db_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $requests = $result->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Request Leave</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .card { border-radius: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .credit-box { background: #e0f7fa; color: #006064; padding: 10px; border-radius: 8px; font-weight: 600; text-align: center; margin-bottom: 20px;}
  </style>
</head>
<body>
  <div class="container py-4">
    <a href="APP_dashboard.php" class="btn btn-outline-secondary mb-3">← Back to Dashboard</a>
    
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card p-4 mb-4">
              <h3 class="text-center mb-3">Request a Leave</h3>
              
              <div class="credit-box">
                  Annual Leave Credits: <?= $remaining_credits ?> / <?= $annual_limit ?> Days
              </div>

              <?php if (isset($_SESSION['show_success_alert'])): ?>
                <script>Swal.fire({icon: 'success', title: 'Request Submitted!', text: 'Your request is pending approval.', timer: 2500, showConfirmButton: false});</script>
                <?php unset($_SESSION['show_success_alert']); ?>
              <?php endif; ?>

              <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
              <?php endif; ?>

              <form method="POST">
                
                <div class="mb-3">
                    <label class="form-label">Leave Type</label>
                    <select name="leave_type" class="form-select" required>
                        <option value="Vacation">Vacation Leave</option>
                        <option value="Sick">Sick Leave</option>
                        <option value="Emergency">Emergency Leave</option>
                        <option value="Maternity">Maternity Leave (Paid Exception)</option>
                    </select>
                </div>

                <div class="row">
                  <div class="col-6 mb-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" required>
                  </div>
                  <div class="col-6 mb-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" required>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Reason</label>
                  <textarea name="reason" rows="3" class="form-control" placeholder="Detailed reason..." required></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100">Submit Request</button>
              </form>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card p-4">
              <h5 class="mb-3">My Leave History</h5>
              <?php if (!empty($requests)): ?>
                <div class="table-responsive">
                  <table class="table table-hover align-middle" style="font-size: 0.9rem;">
                    <thead class="table-light">
                      <tr>
                        <th>Type</th>
                        <th>Dates</th>
                        <th>Status</th>
                        <th>Payment</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($requests as $req): ?>
                        <tr>
                          <td>
                              <strong><?= htmlspecialchars($req['leave_type'] ?? 'General') ?></strong><br>
                              <small class="text-muted"><?= htmlspecialchars(substr($req['reason'], 0, 20)) ?>...</small>
                          </td>
                          <td>
                              <?= date("M d", strtotime($req['start_date'])) ?> - <?= date("M d", strtotime($req['end_date'])) ?>
                          </td>
                          <td>
                            <?php
                              $badge = ['Pending'=>'warning', 'Approved'=>'success', 'Rejected'=>'danger'][$req['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $badge ?>"><?= $req['status'] ?></span>
                          </td>
                          <td>
                              <?php if($req['is_paid']): ?>
                                  <span class="badge bg-info text-dark">Paid</span>
                              <?php else: ?>
                                  <span class="badge bg-secondary">Unpaid</span>
                              <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <p class="text-muted text-center mt-4">No leave history found.</p>
              <?php endif; ?>
            </div>
        </div>
    </div>
  </div>
</body>
</html>