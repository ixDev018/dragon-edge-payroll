const searchInput = document.getElementById('searchName');
const resultBody = document.querySelector('#resultTable tbody');
let debounceTimer = null;

searchInput.addEventListener('input', () => {
  clearTimeout(debounceTimer);
  const name = searchInput.value.trim();

  debounceTimer = setTimeout(() => {
    if (name.length < 2) {
      resultBody.innerHTML = `<tr><td colspan="2" class="text-center text-muted">Type at least 2 letters to search</td></tr>`;
      return;
    }

    fetch(`./find_employee_id.php?name=${encodeURIComponent(name)}`)
      .then(res => res.json())
      .then(data => {
        if (data.length === 0) {
          resultBody.innerHTML = `<tr><td colspan="2" class="text-center text-muted">No results found</td></tr>`;
          return;
        }

        let rows = '';
        data.forEach(emp => {
          rows += `
            <tr>
              <td>
                <span class="copy-id text-primary" style="cursor:pointer;" title="Click to copy">${emp.employee_id}</span>
              </td>
              <td>${emp.employee_name}</td>
            </tr>`;
        });
        resultBody.innerHTML = rows;

        document.querySelectorAll('.copy-id').forEach(span => {
          span.addEventListener('click', async () => {
            try {
              await navigator.clipboard.writeText(span.textContent);
              const originalText = span.textContent;
              span.textContent = "Copied!";
              span.classList.add("text-success");
              setTimeout(() => {
                span.textContent = originalText;
                span.classList.remove("text-success");
              }, 1000);
            } catch (err) {
              alert('Failed to copy ID');
            }
          });
        });
      })
      .catch(err => {
        console.error(err);
        resultBody.innerHTML = `<tr><td colspan="2" class="text-center text-danger">Error fetching data</td></tr>`;
      });
  }, 400);
});
