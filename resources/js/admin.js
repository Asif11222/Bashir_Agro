// Admin Panel presentation JavaScript
document.addEventListener('DOMContentLoaded', () => {
    // Mobile menu toggle & backdrop
    const menuBtn = document.getElementById('menu');
    const sideBar = document.getElementById('side');
    const backdrop = document.getElementById('sidebar-backdrop');

    function closeSidebar() {
        if (sideBar) sideBar.classList.remove('open');
        if (backdrop) backdrop.classList.remove('show');
        document.body.classList.remove('sidebar-locked');
    }

    function openSidebar() {
        if (sideBar) sideBar.classList.add('open');
        if (backdrop) backdrop.classList.add('show');
        document.body.classList.add('sidebar-locked');
    }

    if (menuBtn && sideBar) {
        menuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (sideBar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeSidebar);
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sideBar && sideBar.classList.contains('open')) {
            closeSidebar();
        }
    });

    if (sideBar) {
        sideBar.querySelectorAll('nav a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 1024) {
                    closeSidebar();
                }
            });
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

    // Password visibility toggles
    document.querySelectorAll('[data-toggle-password]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = btn.dataset.togglePassword;
            const input = targetId ? document.getElementById(targetId) : btn.parentElement.querySelector('input');
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            const eyeIcon = btn.querySelector('.eye-icon, #eye_icon');
            const eyeSlashIcon = btn.querySelector('.eye-slash-icon, #eye_slash_icon');
            if (eyeIcon && eyeSlashIcon) {
                eyeIcon.style.display = isPassword ? 'none' : 'block';
                eyeSlashIcon.style.display = isPassword ? 'block' : 'none';
            }

            const label = isPassword ? 'পাসওয়ার্ড লুকান' : 'পাসওয়ার্ড দেখুন';
            btn.setAttribute('title', label);
            btn.setAttribute('aria-label', label);
            input.focus();
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
