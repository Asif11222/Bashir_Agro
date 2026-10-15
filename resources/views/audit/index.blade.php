@extends('layouts.admin')

@section('title', 'অডিট ট্রেইল লগ — আমার ফার্ম')

@section('content')
<div class="page">
    <div class="head">
        <div>
            <h1>অডিট ট্রেইল ও নিরাপত্তা লগ</h1>
            <p>অ্যাডমিন প্যানেলের সকল গুরুত্বপূর্ণ কার্যক্রম, পরিবর্তন, সময় ও ব্যবহারকারী তথ্যের সম্পূর্ণ হিস্টোরি</p>
        </div>
        <div class="actions">
            <a href="{{ route('admin.audit') }}" class="btn soft">🔄 রিফ্রেশ লগ</a>
        </div>
    </div>

    {{-- Filter & Search Form --}}
    <section class="card" style="margin-bottom:16px;">
        <div class="card-title">
            <div class="title-left">
                <span class="iconbox">⌕</span>
                <div>
                    <h2>ফিল্টার ও অনুসন্ধান</h2>
                    <div class="sub">তারিখ, মডিউল, অ্যাকশন বা ব্যবহারকারী দিয়ে অডিট রেকর্ড খুঁজুন</div>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.audit') }}" style="padding:16px 20px;">
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:12px;align-items:end;">
                <div>
                    <label class="label" style="font-weight:600;display:block;margin-bottom:4px;font-size:12px;">অনুসন্ধান (Search)</label>
                    <input type="text" name="search" class="input" style="width:100%;font-size:12px;" 
                           placeholder="বিবরণ, অ্যাকশন, মডিউল বা ব্যবহারকারী..." value="{{ $filters['search'] ?? '' }}">
                </div>

                <div>
                    <label class="label" style="font-weight:600;display:block;margin-bottom:4px;font-size:12px;">মডিউল (Module)</label>
                    <select name="module" class="input" style="width:100%;font-size:12px;">
                        <option value="all">সব মডিউল</option>
                        <option value="farmer" {{ ($filters['module'] ?? '') === 'farmer' ? 'selected' : '' }}>কৃষক (Farmer)</option>
                        <option value="location" {{ ($filters['module'] ?? '') === 'location' ? 'selected' : '' }}>লোকেশন (Location)</option>
                        <option value="task" {{ ($filters['module'] ?? '') === 'task' ? 'selected' : '' }}>কাজ টেমপ্লেট (Task)</option>
                        <option value="assignment" {{ ($filters['module'] ?? '') === 'assignment' ? 'selected' : '' }}>কাজ বরাদ্দ (Assignment)</option>
                        <option value="settings" {{ ($filters['module'] ?? '') === 'settings' ? 'selected' : '' }}>সেটিংস (Settings)</option>
                        <option value="system" {{ ($filters['module'] ?? '') === 'system' ? 'selected' : '' }}>সিস্টেম (System)</option>
                    </select>
                </div>

                <div>
                    <label class="label" style="font-weight:600;display:block;margin-bottom:4px;font-size:12px;">ব্যবহারকারী (User)</label>
                    <select name="user_id" class="input" style="width:100%;font-size:12px;">
                        <option value="all">সব ব্যবহারকারী</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (string)($filters['user_id'] ?? '') === (string)$user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->role ?? 'অ্যাডমিন' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label" style="font-weight:600;display:block;margin-bottom:4px;font-size:12px;">শুরুর তারিখ (From)</label>
                    <input type="date" name="start_date" class="input" style="width:100%;font-size:12px;" value="{{ $filters['start_date'] ?? '' }}">
                </div>

                <div>
                    <label class="label" style="font-weight:600;display:block;margin-bottom:4px;font-size:12px;">শেষ তারিখ (To)</label>
                    <input type="date" name="end_date" class="input" style="width:100%;font-size:12px;" value="{{ $filters['end_date'] ?? '' }}">
                </div>

                <div style="display:flex;gap:6px;">
                    <button type="submit" class="btn primary" style="padding:7px 14px;font-size:12px;flex:1;">
                        ফিল্টার প্রয়োগ
                    </button>
                    <a href="{{ route('admin.audit') }}" class="btn soft" style="padding:7px 10px;font-size:12px;" title="রিসেট">
                        ✕
                    </a>
                </div>
            </div>
        </form>
    </section>

    {{-- Audit Log Table Card --}}
    <section class="card">
        <div class="card-title">
            <div class="title-left">
                <span class="iconbox">📜</span>
                <div>
                    <h2>লগ রেকর্ড তালিকা (মোট {{ $logs->total() }} টি)</h2>
                    <div class="sub">সর্বশেষ কার্যক্রম সবার উপরে সংরক্ষিত রয়েছে</div>
                </div>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:150px">সময় ও তারিখ</th>
                        <th style="width:160px">ব্যবহারকারী</th>
                        <th style="width:110px">মডিউল</th>
                        <th style="width:140px">অ্যাকশন</th>
                        <th>কার্যক্রম বিবরণ</th>
                        <th style="width:110px">আইপি অ্যাড্রেস</th>
                        <th style="width:90px;text-align:right">পরিবর্তন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>
                                <div style="display:flex;flex-direction:column;">
                                    <strong style="font-size:12px;">{{ $log->created_at->format('d M Y') }}</strong>
                                    <small style="color:var(--muted);font-size:11px;">{{ $log->created_at->format('h:i:s A') }}</small>
                                </div>
                            </td>
                            <td>
                                @if($log->user)
                                    <div style="display:flex;gap:8px;align-items:center;">
                                        <div class="avatar" style="width:26px;height:26px;font-size:11px;flex:none;">
                                            {{ mb_substr($log->user->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <b style="font-size:12px;display:block;line-height:1.2;">{{ $log->user->name }}</b>
                                            <small style="color:var(--muted);font-size:10px;">{{ $log->user->role ?? 'অ্যাডমিন' }}</small>
                                        </div>
                                    </div>
                                @else
                                    <span style="color:var(--muted);font-size:12px;">সিস্টেম / স্বয়ংক্রিয়</span>
                                @endif
                            </td>
                            <td>
                                @php $mod = strtolower($log->module); @endphp
                                @if($mod === 'farmer')
                                    <span class="pill" style="background:#e8f5e9;color:#2e7d32">♟ কৃষক</span>
                                @elseif($mod === 'location')
                                    <span class="pill" style="background:#fff8e1;color:#f57f17">⌖ লোকেশন</span>
                                @elseif($mod === 'task')
                                    <span class="pill" style="background:#e3f2fd;color:#1565c0">▣ কাজ</span>
                                @elseif($mod === 'assignment')
                                    <span class="pill" style="background:#f3e5f5;color:#7b1fa2">✓ বরাদ্দ</span>
                                @elseif($mod === 'settings')
                                    <span class="pill" style="background:#fce4ec;color:#c2185b">⚙ সেটিংস</span>
                                @else
                                    <span class="pill" style="background:#eceff1;color:#455a64">🛡 {{ $log->module }}</span>
                                @endif
                            </td>
                            <td>
                                <code style="font-size:11px;background:#f5f5f5;padding:2px 6px;border-radius:4px;color:#333;">
                                    {{ $log->action }}
                                </code>
                            </td>
                            <td>
                                <span style="font-size:13px;line-height:1.4;">
                                    {{ $log->description }}
                                </span>
                            </td>
                            <td>
                                <small style="font-family:monospace;color:var(--muted);font-size:11px;">
                                    {{ $log->ip_address ?: '127.0.0.1' }}
                                </small>
                            </td>
                            <td style="text-align:right">
                                @if(!empty($log->old_values) || !empty($log->new_values))
                                    <button type="button" class="btn soft" style="padding:3px 8px;font-size:11px;" 
                                            onclick="showAuditModal('{{ $log->id }}')">
                                        বিস্তারিত
                                    </button>

                                    {{-- Hidden JSON payload for modal --}}
                                    <template id="audit-data-{{ $log->id }}">
                                        {
                                            "action": "{{ $log->action }}",
                                            "description": "{{ addslashes($log->description) }}",
                                            "user": "{{ $log->user ? addslashes($log->user->name) : 'সিস্টেম' }}",
                                            "time": "{{ $log->created_at->format('d M Y, h:i:s A') }}",
                                            "old_values": @json($log->old_values),
                                            "new_values": @json($log->new_values)
                                        }
                                    </template>
                                @else
                                    <span style="color:var(--muted);font-size:11px;">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;padding:48px 16px;color:var(--muted);">
                                <div style="font-size:32px;margin-bottom:8px;">📜</div>
                                <p style="margin:0;font-size:14px;">কোনো অডিট লগ পাওয়া যায়নি।</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div style="padding:16px 20px;border-top:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;">
                <div style="font-size:12px;color:var(--muted);">
                    মোট {{ $logs->total() }} টির মধ্যে {{ $logs->firstItem() }} থেকে {{ $logs->lastItem() }} দেখানো হচ্ছে
                </div>
                <div>
                    {{ $logs->links() }}
                </div>
            </div>
        @endif
    </section>
</div>

{{-- Change Details Modal --}}
<div id="auditModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;width:90%;max-width:650px;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <div style="padding:16px 20px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h3 id="modalTitle" style="margin:0;font-size:16px;">অডিট পরিবর্তন বিস্তারিত</h3>
                <small id="modalSubtitle" style="color:var(--muted);font-size:12px;"></small>
            </div>
            <button type="button" onclick="closeAuditModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--muted);">&times;</button>
        </div>
        <div style="padding:20px;overflow-y:auto;display:flex;flex-direction:column;gap:16px;">
            <div>
                <b style="font-size:13px;display:block;margin-bottom:6px;color:var(--red);">পূর্ববর্তী মান (Old Values):</b>
                <pre id="modalOldValues" style="background:#fff5f5;border:1px solid #ffcdd2;padding:12px;border-radius:6px;font-size:12px;overflow-x:auto;margin:0;white-space:pre-wrap;"></pre>
            </div>
            <div>
                <b style="font-size:13px;display:block;margin-bottom:6px;color:var(--green);">নতুন মান (New Values):</b>
                <pre id="modalNewValues" style="background:#f1f8e9;border:1px solid #c8e6c9;padding:12px;border-radius:6px;font-size:12px;overflow-x:auto;margin:0;white-space:pre-wrap;"></pre>
            </div>
        </div>
        <div style="padding:12px 20px;border-top:1px solid var(--line);text-align:right;">
            <button type="button" class="btn soft" onclick="closeAuditModal()">বন্ধ করুন</button>
        </div>
    </div>
</div>

<script>
function showAuditModal(logId) {
    const tpl = document.getElementById('audit-data-' + logId);
    if (!tpl) return;
    try {
        const data = JSON.parse(tpl.innerHTML);
        document.getElementById('modalTitle').innerText = data.action + ' — পরিবর্তন রেকর্ড';
        document.getElementById('modalSubtitle').innerText = data.user + ' • ' + data.time;
        document.getElementById('modalOldValues').innerText = data.old_values ? JSON.stringify(data.old_values, null, 2) : 'কোনো পূর্ববর্তী মান নেই';
        document.getElementById('modalNewValues').innerText = data.new_values ? JSON.stringify(data.new_values, null, 2) : 'কোনো নতুন মান নেই';
        
        const modal = document.getElementById('auditModal');
        modal.style.display = 'flex';
    } catch(e) {
        console.error(e);
    }
}

function closeAuditModal() {
    document.getElementById('auditModal').style.display = 'none';
}

window.addEventListener('click', function(e) {
    const modal = document.getElementById('auditModal');
    if (e.target === modal) {
        closeAuditModal();
    }
});
</script>
@endsection
