@extends('layouts.admin')

@section('title', 'কার্যক্রম পর্যবেক্ষণ — আমার ফার্ম')

@section('content')
<div class="page">
    <div class="head">
        <div>
            <h1>কার্যক্রম পর্যবেক্ষণ</h1>
            <p>মাঠ পর্যায়ের অপারেশন, সময়সূচি, জিপিএস জিও-ফেন্স ও প্রমাণ ছবি যাচাইকরণ এক নজরে</p>
        </div>
        <div class="actions" style="display:flex;align-items:center;gap:10px;">
            <span class="pill" style="font-size:11px;color:var(--g700);background:#e8f5e9;" title="স্বয়ংক্রিয় লাইভ সিঙ্ক সক্রিয়">
                <span class="dot" style="background:#2e7d32"></span>লাইভ সিঙ্ক
            </span>
            <a href="{{ route('admin.assignments.create') }}" class="btn soft">+ নতুন কাজ বরাদ্দ</a>
            <a href="{{ route('admin.monitoring', array_filter(['date_filter' => $dateFilter, 'status' => $status, 'priority' => $priority, 'search' => $search])) }}" id="btn-refresh-monitoring" class="btn primary">
                <span class="spin-icon">🔄</span> রিফ্রেশ মনিটরিং
            </a>
        </div>
    </div>

    {{-- Live Operation KPI Row --}}
    <div id="kpi-cards-container" class="grid cols-4">
        @include('monitoring.partials.kpis')
    </div>

    {{-- Main Monitoring Content Grid --}}
    <div class="grid monitoring-grid section-gap">
        <div class="monitoring-main-col">
            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">◉</span>
                        <div>
                            <h2>সরাসরি কার্যক্রম নিরীক্ষণ</h2>
                            <div class="sub">কৃষক, কাজ ও মাঠের অবস্থান অনুযায়ী ফিল্টার ও ট্র্যাক করুন</div>
                        </div>
                    </div>
                </div>

                {{-- Search & Filter Row --}}
                <div style="display:flex;gap:10px;align-items:center;margin-bottom:14px;flex-wrap:wrap;">
                    <form id="monitoring-filter-form" method="GET" action="{{ route('admin.monitoring') }}" style="display:flex;gap:8px;flex:1;flex-wrap:wrap;align-items:center;">
                        <input type="hidden" name="status" id="filter-status-input" value="{{ $status }}">

                        <input class="input" type="text" name="search" id="filter-search-input" value="{{ $search }}" placeholder="কৃষকের নাম, কাজ বা লোকেশন খুঁজুন..." style="padding:7px 12px;font-size:12px;flex:1;min-width:180px;">

                        <select name="priority" id="filter-priority-select" class="input" style="padding:7px 10px;font-size:12px;width:auto;">
                            <option value="">সকল অগ্রাধিকার</option>
                            <option value="normal" {{ $priority === 'normal' ? 'selected' : '' }}>সাধারণ</option>
                            <option value="important" {{ $priority === 'important' ? 'selected' : '' }}>গুরুত্বপূর্ণ</option>
                            <option value="urgent" {{ $priority === 'urgent' ? 'selected' : '' }}>জরুরি</option>
                        </select>

                        <select name="date_filter" id="filter-date-filter-select" class="input" style="padding:7px 10px;font-size:12px;width:auto;">
                            <option value="today" {{ $dateFilter === 'today' ? 'selected' : '' }}>আজকের কাজ</option>
                            <option value="this_week" {{ $dateFilter === 'this_week' ? 'selected' : '' }}>এই সপ্তাহের কাজ</option>
                            <option value="all" {{ $dateFilter === 'all' ? 'selected' : '' }}>সকল তারিখ</option>
                            <option value="custom" {{ $dateFilter === 'custom' ? 'selected' : '' }}>নির্দিষ্ট তারিখ</option>
                        </select>

                        <input type="date" name="date" id="filter-custom-date-input" class="input" value="{{ $customDate }}" style="padding:6px 10px;font-size:12px;width:auto;{{ $dateFilter === 'custom' ? '' : 'display:none;' }}">

                        <button type="submit" id="btn-submit-search" class="btn soft" style="padding:7px 12px">খুঁজুন</button>
                        
                        <a href="{{ route('admin.monitoring') }}" id="btn-reset-filters" class="btn" style="padding:7px 10px;{{ ($search || $priority || ($dateFilter && $dateFilter !== 'today')) ? '' : 'display:none;' }}" title="রিসেট">✕</a>
                    </form>
                </div>

                {{-- Status Tabs --}}
                <div id="monitoring-tabs-container">
                    @include('monitoring.partials.tabs')
                </div>

                {{-- Table & Pagination --}}
                <div id="monitoring-table-container" class="monitoring-table-container">
                    <div class="monitoring-loader-bar"></div>
                    @include('monitoring.partials.table')
                </div>
            </section>
        </div>

        {{-- Sidebar: Focused Active Operation, Rules & Timeline --}}
        <div id="monitoring-side-col" class="monitoring-side-col">
            @include('monitoring.partials.sidebar_focus')
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('monitoring-filter-form');
    const searchInput = document.getElementById('filter-search-input');
    const prioritySelect = document.getElementById('filter-priority-select');
    const dateFilterSelect = document.getElementById('filter-date-filter-select');
    const customDateInput = document.getElementById('filter-custom-date-input');
    const statusInput = document.getElementById('filter-status-input');
    const resetBtn = document.getElementById('btn-reset-filters');
    const refreshBtn = document.getElementById('btn-refresh-monitoring');

    const tableContainer = document.getElementById('monitoring-table-container');
    const tabsContainer = document.getElementById('monitoring-tabs-container');
    const kpiContainer = document.getElementById('kpi-cards-container');
    const sidebarCol = document.getElementById('monitoring-side-col');

    let abortController = null;
    let searchDebounceTimer = null;
    let autoSyncTimer = null;

    // Helper: Build query params from form
    function getFormQueryParams() {
        const params = new URLSearchParams();
        if (searchInput && searchInput.value.trim() !== '') {
            params.set('search', searchInput.value.trim());
        }
        if (prioritySelect && prioritySelect.value) {
            params.set('priority', prioritySelect.value);
        }
        if (dateFilterSelect && dateFilterSelect.value && dateFilterSelect.value !== 'today') {
            params.set('date_filter', dateFilterSelect.value);
        }
        if (dateFilterSelect && dateFilterSelect.value === 'custom' && customDateInput && customDateInput.value) {
            params.set('date', customDateInput.value);
        }
        if (statusInput && statusInput.value) {
            params.set('status', statusInput.value);
        }
        return params;
    }

    // Core AJAX Fetch function
    async function fetchMonitoringData(targetUrl = null, options = {}) {
        const isBackground = options.isBackground || false;

        if (abortController) {
            abortController.abort();
        }
        abortController = new AbortController();

        let requestUrl;
        if (targetUrl) {
            requestUrl = targetUrl;
        } else {
            const params = getFormQueryParams();
            const queryString = params.toString();
            requestUrl = filterForm.action + (queryString ? '?' + queryString : '');
        }

        if (!isBackground) {
            if (tableContainer) tableContainer.classList.add('loading');
            if (refreshBtn) {
                const icon = refreshBtn.querySelector('.spin-icon');
                if (icon) icon.classList.add('spinning');
            }
        }

        try {
            const res = await fetch(requestUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                signal: abortController.signal
            });

            if (!res.ok) throw new Error('HTTP ' + res.status);

            const data = await res.json();

            // Update DOM with rendered partials
            if (data.table_html && tableContainer) {
                tableContainer.innerHTML = '<div class="monitoring-loader-bar"></div>' + data.table_html;
            }
            if (data.tabs_html && tabsContainer) {
                tabsContainer.innerHTML = data.tabs_html;
            }
            if (data.kpis_html && kpiContainer) {
                kpiContainer.innerHTML = data.kpis_html;
            }
            if (data.sidebar_html && sidebarCol) {
                sidebarCol.innerHTML = data.sidebar_html;
            }

            // Update Reset button visibility
            if (resetBtn) {
                resetBtn.style.display = data.has_filters ? 'inline-flex' : 'none';
            }

            // Update browser URL without reloading
            if (!isBackground) {
                window.history.pushState({ path: requestUrl }, '', requestUrl);
            }

        } catch (err) {
            if (err.name !== 'AbortError') {
                console.error('AJAX monitoring fetch failed:', err);
            }
        } finally {
            if (tableContainer) tableContainer.classList.remove('loading');
            if (refreshBtn) {
                const icon = refreshBtn.querySelector('.spin-icon');
                if (icon) icon.classList.remove('spinning');
            }
        }
    }

    // Toggle custom date visibility
    function handleDateFilterChange() {
        if (!dateFilterSelect || !customDateInput) return;
        if (dateFilterSelect.value === 'custom') {
            customDateInput.style.display = 'inline-block';
            customDateInput.focus();
        } else {
            customDateInput.style.display = 'none';
            customDateInput.value = '';
        }
    }

    // Event: Priority dropdown change
    if (prioritySelect) {
        prioritySelect.addEventListener('change', () => fetchMonitoringData());
    }

    // Event: Date filter dropdown change
    if (dateFilterSelect) {
        dateFilterSelect.addEventListener('change', () => {
            handleDateFilterChange();
            fetchMonitoringData();
        });
    }

    // Event: Custom date input change
    if (customDateInput) {
        customDateInput.addEventListener('change', () => fetchMonitoringData());
    }

    // Event: Live Search debounce (350ms)
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                fetchMonitoringData();
            }, 350);
        });
    }

    // Event: Form submit (Enter key or "খুঁজুন" button)
    if (filterForm) {
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            clearTimeout(searchDebounceTimer);
            fetchMonitoringData();
        });
    }

    // Event: Tab click (Delegated on tabsContainer)
    if (tabsContainer) {
        tabsContainer.addEventListener('click', (e) => {
            const tab = e.target.closest('a.tab');
            if (!tab) return;
            e.preventDefault();
            const status = tab.dataset.status ?? '';
            if (statusInput) statusInput.value = status;
            fetchMonitoringData();
        });
    }

    // Event: Pagination link click (Delegated on tableContainer)
    if (tableContainer) {
        tableContainer.addEventListener('click', (e) => {
            const pageLink = e.target.closest('.pagination-wrapper a, .pagination a');
            if (!pageLink || !pageLink.href) return;
            e.preventDefault();
            fetchMonitoringData(pageLink.href);
            tableContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    }

    // Event: Reset button click
    if (resetBtn) {
        resetBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (searchInput) searchInput.value = '';
            if (prioritySelect) prioritySelect.value = '';
            if (dateFilterSelect) dateFilterSelect.value = 'today';
            if (customDateInput) {
                customDateInput.value = '';
                customDateInput.style.display = 'none';
            }
            fetchMonitoringData();
        });
    }

    // Event: Refresh button click
    if (refreshBtn) {
        refreshBtn.addEventListener('click', (e) => {
            e.preventDefault();
            fetchMonitoringData();
        });
    }

    // Event: Browser Back / Forward buttons (popstate)
    window.addEventListener('popstate', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (searchInput) searchInput.value = urlParams.get('search') || '';
        if (prioritySelect) prioritySelect.value = urlParams.get('priority') || '';
        if (dateFilterSelect) dateFilterSelect.value = urlParams.get('date_filter') || 'today';
        if (customDateInput) {
            customDateInput.value = urlParams.get('date') || '';
            customDateInput.style.display = (dateFilterSelect && dateFilterSelect.value === 'custom') ? 'inline-block' : 'none';
        }
        if (statusInput) statusInput.value = urlParams.get('status') || '';

        fetchMonitoringData(window.location.href, { isBackground: false });
    });

    // Background live auto-synchronization (every 30 seconds, silent)
    function scheduleAutoSync() {
        autoSyncTimer = setTimeout(() => {
            const activeEl = document.activeElement;
            const isTyping = activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'SELECT' || activeEl.tagName === 'TEXTAREA');
            if (!isTyping && !document.hidden) {
                fetchMonitoringData(null, { isBackground: true });
            }
            scheduleAutoSync();
        }, 30000);
    }
    scheduleAutoSync();
});
</script>
@endpush
