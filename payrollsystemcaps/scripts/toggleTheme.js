const themeToggleBtn = document.getElementById('themeToggle');
const html = document.documentElement;

if (localStorage.getItem('theme')) {
    const savedTheme = localStorage.getItem('theme');
    html.setAttribute('data-bs-theme', savedTheme);
    themeToggleBtn.textContent = savedTheme === 'dark' ? 'Light Mode' : 'Dark Mode';
}

themeToggleBtn.addEventListener('click', () => {
    const currentTheme = html.getAttribute('data-bs-theme');
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    html.setAttribute('data-bs-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    themeToggleBtn.textContent = newTheme === 'dark' ? 'Light Mode' : 'Dark Mode';
});