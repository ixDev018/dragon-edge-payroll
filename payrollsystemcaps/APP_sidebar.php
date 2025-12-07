<div class="d-flex">
  <nav id="sidebar" class="sidebar p-3">
    <center><img src="images/logo.png" alt="Logo" class="mb-4"></center>
    <h4 class="mb-4">Dragon Edge Group</h4>
    <button id="closeSidebar" class="btn btn-danger mb-3"><i class="bi bi-x-circle-fill"></i> Close</button>
    <ul class="nav flex-column">
      <li class="nav-item">
        <a href="APP_dashboard.php" class="nav-link d-flex align-items-center">
          <i class="bi bi-house-door-fill me-2"></i><span>Home</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="APP_attendance.php" class="nav-link d-flex align-items-center">
          <i class="bi bi-card-checklist me-2"></i><span>Attendance</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="APP_payslip.php" class="nav-link d-flex align-items-center">
          <i class="bi bi-receipt me-2"></i><span>Payslip</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="APP_request-leave.php" class="nav-link d-flex align-items-center">
          <i class="bi bi-file-earmark-arrow-up-fill me-2"></i><span>Request Leave</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="#" id="logoutBtn" class="nav-link d-flex align-items-center">
          <i class="bi bi-box-arrow-right me-2"></i><span>Log out</span>
        </a>
      </li>
    </ul>
  </nav>

<style>
  body { overflow-x: hidden; }
  .sidebar {
    min-height: 100vh;
    background-color: #e83c3c;
    color: white;
    transition: all 0.3s ease;
    width: 270px;
  }
  .sidebar .nav-item { margin-bottom: 15px; }
  .sidebar .nav-link {
    color: white;
    font-size: 18px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: color 0.3s ease, transform 0.3s ease;
  }
  .sidebar .nav-link:hover {
    color: #f1f1f1;
    transform: scale(1.1);
  }
  .sidebar h4 {
    color: white;
    font-size: 24px;
    font-weight: bold;
    text-align: center;
  }
  .sidebar img { max-width: 50%; margin-bottom: 20px; }
  .sidebar .nav-link i { font-size: 20px; }
  @media (max-width: 768px) {
    .sidebar { width: 80px; padding: 10px 0; text-align: center; }
    .sidebar h4, .sidebar img, .sidebar .nav-link span, #closeSidebar { display: none !important; }
    .sidebar .nav-link { justify-content: center; }
    .sidebar .nav-link i { margin-right: 0; font-size: 24px; }
  }
  @media (min-width: 769px) { #closeSidebar { display: none; } }
</style>

<script>
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('sidebarToggle');
  const closeBtn = document.getElementById('closeSidebar');
  if(toggleBtn) { toggleBtn.addEventListener('click', () => { sidebar.classList.toggle('collapsed'); }); }
  if(closeBtn) { closeBtn.addEventListener('click', () => { sidebar.classList.add('collapsed'); }); }
</script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  const logoutBtn = document.getElementById('logoutBtn');
  if(logoutBtn){
      logoutBtn.addEventListener('click', function(e) {
        e.preventDefault(); 
        Swal.fire({
          title: 'Are you sure?',
          text: "You will be logged out.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Yes, log out'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = 'APP_logout.php'; 
          }
        });
      });
  }
</script>
</div>