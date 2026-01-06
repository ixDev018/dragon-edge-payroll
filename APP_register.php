<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Employee Registration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f8f9fa;
    }
    .card {
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">
  <div class="card p-4" style="max-width: 400px; width: 100%;">
    <h3 class="text-center mb-3">Employee Registration</h3>
    <form id="registerForm">
      <div class="mb-3">
        <label for="employee_id" class="form-label">Employee ID</label>
        <input type="text" id="employee_id" name="employee_id" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" id="email" name="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="employee_name" class="form-label">Full Name</label>
        <input type="text" id="employee_name" name="name" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" id="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Register</button>
    </form>
    <p class="mt-3 text-center">
      Already have an account? <a href="APP_index.php">Login here</a>
    </p>
    <div id="result" class="text-center mt-3 text-danger small"></div>
  </div>

  <script>
    document.getElementById('registerForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const data = Object.fromEntries(new FormData(e.target));
      const res = await fetch('employee_register_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
      });
      const json = await res.json();
      document.getElementById('result').textContent = json.message;
      if (json.ok) {
        e.target.reset();
        document.getElementById('result').classList.replace('text-danger','text-success');
      }
    });
  </script>
</body>
</html>