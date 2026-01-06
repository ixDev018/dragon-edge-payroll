document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("genForm");
  const payslipContainer = document.getElementById("payslipContainer");
  const payslipDetails = document.getElementById("payslipDetails");
  const payPeriod = document.getElementById("payPeriod");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    if (!form.checkValidity()) {
      e.stopPropagation();
      form.classList.add("was-validated");
      return;
    }

    const data = {
      employee_id: document.getElementById("employee_id").value,
      from: document.getElementById("from").value,
      to: document.getElementById("to").value
    };

    try {
      const res = await fetch("payroll_api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
      });

      const json = await res.json();
      if (!json.ok) throw new Error(json.error || "Payroll computation failed.");

      renderPayslip(json.data, data.from, data.to);
    } catch (err) {
      alert("Error: " + err.message);
    }
  });

  function renderPayslip(data, from, to) {
    payPeriod.textContent = `Payroll Period: ${from} to ${to}`;

    const gross = parseFloat(data.employee.basic_salary).toFixed(2);
    const deductions = parseFloat(data.late_deduction).toFixed(2);
    const net = parseFloat(data.net_pay).toFixed(2);

    payslipDetails.innerHTML = `
      <table class="table table-bordered">
        <tbody>
          <tr><th>Employee Name</th><td>${data.employee.employee_name}</td></tr>
          <tr><th>Role</th><td>${data.employee.role}</td></tr>
          <tr><th>Department</th><td>${data.employee.department_name}</td></tr>
          <tr><th>Basic Salary</th><td>₱${gross}</td></tr>
          <tr><th>Deductions</th><td>-₱${deductions}</td></tr>
          <tr class="table-primary fw-bold"><th>Net Salary</th><td>₱${net}</td></tr>
        </tbody>
      </table>
      <div class="text-center mt-4">
        <p><strong>_________________________</strong><br>Authorized Signature</p>
      </div>
    `;

    payslipContainer.classList.remove("d-none");
  }

  document.getElementById("btnPrint").addEventListener("click", () => {
    const printContent = document.getElementById("payslipCard").outerHTML;
    const printWindow = window.open("", "_blank");
    printWindow.document.write(`
      <html><head>
      <title>Payslip</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
      <style>@media print { body { margin: 40px; } }</style>
      </head><body>${printContent}</body></html>
    `);
    printWindow.document.close();
    printWindow.print();
  });

  document.getElementById("btnPDF").addEventListener("click", async () => {
    const payslip = document.getElementById("payslipCard");
    const { jsPDF } = window.jspdf;

    const canvas = await html2canvas(payslip, { scale: 2 });
    const imgData = canvas.toDataURL("image/png");

    const pdf = new jsPDF("p", "mm", "a4");
    const imgProps = pdf.getImageProperties(imgData);
    const pdfWidth = pdf.internal.pageSize.getWidth();
    const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

    pdf.addImage(imgData, "PNG", 0, 0, pdfWidth, pdfHeight);
    pdf.save(`Payslip_${Date.now()}.pdf`);
  });

  document.getElementById("btnExcel").addEventListener("click", () => {
    const table = document.getElementById("payslipDetails");
    if (!table) {
      alert("No payslip data found!");
      return;
    }

    const workbook = XLSX.utils.table_to_book(table, { sheet: "Payslip" });
    XLSX.writeFile(workbook, `Payslip_${Date.now()}.xlsx`);
  });
});
