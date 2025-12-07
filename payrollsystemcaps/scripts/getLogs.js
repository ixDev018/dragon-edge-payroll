document.getElementById("viewLogsBtn").addEventListener("click", async () => {
  const empId = document.getElementById("employeeId").value.trim();
  
  if (!empId) {
    alert("Please enter an Employee ID first.");
    return;
  }

  document.getElementById("attendanceTable").innerHTML = "<p class='text-center text-muted'>Loading attendance logs...</p>";

  try {
    const response = await fetch(`fetch_attendance.php?employee_id=${encodeURIComponent(empId)}`);
    const html = await response.text();
    document.getElementById("attendanceTable").innerHTML = html;
  } catch (err) {
    document.getElementById("attendanceTable").innerHTML = "<p class='text-danger text-center'>Error loading logs.</p>";
  }
});

// async function fetchAttendance() {
//   const empId = document.getElementById("employeeId").value.trim();
//   if (!empId) return;

//   const res = await fetch(`fetch_attendance.php?employee_id=${empId}`);
//   const html = await res.text();
//   document.getElementById("attendanceTable").innerHTML = html;
// }

// async function handleAttendanceAction(action) {
//   const empId = document.getElementById("employeeId").value.trim();
//   if (!empId) {
//     alert("Please enter your Employee ID");
//     return;
//   }

//   document.getElementById("result").textContent = "Processing...";
//   try {
//     const response = await doWebAuthnAction(empId, action);
//     document.getElementById("result").textContent = JSON.stringify(response, null, 2);
//     fetchAttendance();
//   } catch (err) {
//     document.getElementById("result").textContent = "Error: " + err.message;
//   }
// }

// document.getElementById("timeInBtn").onclick = () => handleAttendanceAction("time_in");
// document.getElementById("timeOutBtn").onclick = () => handleAttendanceAction("time_out");