// Menu <details data-dropdown>: tutup saat klik di luar, saat menekan Esc,
// dan tutup dropdown lain ketika satu dibuka.
const dropdowns = () => document.querySelectorAll('details[data-dropdown][open]');

document.addEventListener('click', (event) => {
    dropdowns().forEach((details) => {
        if (!details.contains(event.target)) {
            details.open = false;
        }
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }
    dropdowns().forEach((details) => {
        details.open = false;
        details.querySelector('summary')?.focus();
    });
});
