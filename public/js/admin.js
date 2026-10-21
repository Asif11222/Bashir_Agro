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

    const closeBtn = document.getElementById('sidebar-close-btn');

    if (backdrop) {
        backdrop.addEventListener('click', closeSidebar);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            closeSidebar();
        });
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

    // Global Spotlight Search
    const searchInput = document.getElementById('global-search-input');
    const searchDropdown = document.getElementById('global-search-dropdown');
    const searchWrap = document.getElementById('global-search-wrap');
    const searchClearBtn = document.getElementById('global-search-clear');
    const searchKbd = searchWrap ? searchWrap.querySelector('.search-kbd') : null;

    if (searchInput && searchDropdown) {
        let searchTimer = null;
        let searchAbortController = null;
        let highlightedIndex = -1;

        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function closeSearchDropdown() {
            searchDropdown.style.display = 'none';
            searchDropdown.innerHTML = '';
            highlightedIndex = -1;
        }

        function updateHighlightedItem(items) {
            items.forEach((item, idx) => {
                if (idx === highlightedIndex) {
                    item.classList.add('highlighted');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('highlighted');
                }
            });
        }

        async function performSearch(query) {
            const searchUrl = searchInput.dataset.searchUrl || '/admin/global-search';

            if (searchAbortController) {
                searchAbortController.abort();
            }
            searchAbortController = new AbortController();

            searchDropdown.style.display = 'block';
            searchDropdown.innerHTML = `
                <div class="search-loading">
                    <div class="search-spinner"></div>
                    <div>ফলাফল খোঁজা হচ্ছে...</div>
                </div>
            `;

            try {
                const res = await fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: searchAbortController.signal
                });

                if (!res.ok) throw new Error('Search request failed');
                const data = await res.json();

                if (!data.groups || data.groups.length === 0 || data.total === 0) {
                    searchDropdown.innerHTML = `
                        <div class="search-empty">
                            <span style="font-size:22px;">🔍</span>
                            <b>"${escapeHtml(query)}" এর জন্য কোনো ফলাফল পাওয়া যায়নি</b>
                            <small>নাম, ফোন নম্বর, কোড বা ঠিকানা দিয়ে আবার চেষ্টা করুন</small>
                        </div>
                    `;
                    return;
                }

                let html = '';
                data.groups.forEach(group => {
                    html += `
                        <div class="search-group">
                            <div class="search-group-header">
                                <span>${escapeHtml(group.icon)}</span>
                                <span>${escapeHtml(group.name)}</span>
                            </div>
                    `;
                    group.items.forEach(item => {
                        html += `
                            <a href="${item.url}" class="search-item">
                                <div class="search-item-icon">${escapeHtml(group.icon)}</div>
                                <div class="search-item-info">
                                    <div class="search-item-title">${escapeHtml(item.title)}</div>
                                    <div class="search-item-sub">${escapeHtml(item.subtitle)}</div>
                                </div>
                                <div class="search-item-badge">${escapeHtml(item.badge)}</div>
                            </a>
                        `;
                    });
                    html += `</div>`;
                });

                html += `
                    <div class="search-hint">
                        <span>নেভিগেট করতে <b>↑</b> <b>↓</b></span>
                        <span>খুলতে <b>Enter</b></span>
                        <span>বন্ধ করতে <b>Esc</b></span>
                    </div>
                `;

                searchDropdown.innerHTML = html;
                highlightedIndex = -1;
            } catch (err) {
                if (err.name === 'AbortError') return;
                searchDropdown.innerHTML = `
                    <div class="search-empty">
                        <span style="font-size:20px;color:var(--red)">⚠️</span>
                        <b>অনুসন্ধানে সমস্যা হয়েছে</b>
                        <small>অনুগ্রহ করে আবার চেষ্টা করুন</small>
                    </div>
                `;
            }
        }

        searchInput.addEventListener('input', () => {
            const query = searchInput.value.trim();

            if (searchClearBtn) {
                searchClearBtn.style.display = searchInput.value ? 'flex' : 'none';
            }
            if (searchKbd) {
                searchKbd.style.display = searchInput.value ? 'none' : 'block';
            }

            clearTimeout(searchTimer);

            if (query.length < 2) {
                closeSearchDropdown();
                return;
            }

            searchTimer = setTimeout(() => {
                performSearch(query);
            }, 220);
        });

        searchInput.addEventListener('keydown', (e) => {
            const items = searchDropdown.querySelectorAll('.search-item');

            if (e.key === 'ArrowDown') {
                if (searchDropdown.style.display === 'none' || items.length === 0) return;
                e.preventDefault();
                highlightedIndex = (highlightedIndex + 1) % items.length;
                updateHighlightedItem(items);
            } else if (e.key === 'ArrowUp') {
                if (searchDropdown.style.display === 'none' || items.length === 0) return;
                e.preventDefault();
                highlightedIndex = (highlightedIndex - 1 + items.length) % items.length;
                updateHighlightedItem(items);
            } else if (e.key === 'Enter') {
                if (searchDropdown.style.display !== 'none' && items.length > 0) {
                    e.preventDefault();
                    const target = highlightedIndex >= 0 ? items[highlightedIndex] : items[0];
                    if (target) {
                        window.location.href = target.getAttribute('href');
                    }
                }
            } else if (e.key === 'Escape') {
                closeSearchDropdown();
                searchInput.blur();
            }
        });

        if (searchClearBtn) {
            searchClearBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                searchInput.value = '';
                searchClearBtn.style.display = 'none';
                if (searchKbd) searchKbd.style.display = 'block';
                closeSearchDropdown();
                searchInput.focus();
            });
        }

        document.addEventListener('click', (e) => {
            if (searchWrap && !searchWrap.contains(e.target)) {
                closeSearchDropdown();
            }
        });

        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });
    }
});
