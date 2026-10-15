// Admin Panel presentation JavaScript
document.addEventListener('DOMContentLoaded', () => {
    // Mobile menu toggle
    const menuBtn = document.getElementById('menu');
    const sideBar = document.getElementById('side');
    if (menuBtn && sideBar) {
        menuBtn.addEventListener('click', () => {
            sideBar.classList.toggle('open');
        });
    }

    // Modal triggers & close
    const modal = document.getElementById('modal');
    document.querySelectorAll('[data-action="close"]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (modal) modal.classList.remove('show');
        });
    });

    // Switches
    document.querySelectorAll('[data-switch]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            btn.classList.toggle('on');
            const targetInput = btn.dataset.input ? document.getElementById(btn.dataset.input) : null;
            if (targetInput) {
                targetInput.value = btn.classList.contains('on') ? '1' : '0';
            }
        });
    });

    // Choice buttons
    document.querySelectorAll('.choice').forEach(choice => {
        choice.addEventListener('click', (e) => {
            e.preventDefault();
            const parent = choice.parentElement;
            if (parent) {
                parent.querySelectorAll('.choice').forEach(c => c.classList.remove('active'));
                choice.classList.add('active');
                const targetInput = parent.dataset.input ? document.getElementById(parent.dataset.input) : null;
                if (targetInput) {
                    targetInput.value = choice.dataset.value || choice.textContent.trim();
                }
            }
        });
    });

    // Toast helper
    window.showToast = function (message) {
        const toast = document.getElementById('toast');
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('show');
        clearTimeout(window.showToastTimeout);
        window.showToastTimeout = setTimeout(() => {
            toast.classList.remove('show');
        }, 2500);
    };

    // Auto-display flash toast if present
    const flashToast = document.getElementById('flash-toast-message');
    if (flashToast && flashToast.value) {
        window.showToast(flashToast.value);
    }
});
