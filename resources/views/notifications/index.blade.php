@extends('layouts.admin')

@section('title', 'সিস্টেম নোটিফিকেশন — আমার ফার্ম')

@section('content')
<div class="page">
    <div class="head">
        <div>
            <h1>সিস্টেম নোটিফিকেশন</h1>
            <p>সিস্টেম কার্যক্রম, কৃষক নিবন্ধন, কাজ বরাদ্দ এবং সমাপ্তির রিয়েল-টাইম আপডেট ও অ্যালার্ট</p>
        </div>
        <div class="actions">
            @if($unreadCount > 0)
                <form method="POST" action="{{ route('admin.notifications.read-all') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn primary">✓ সব পঠিত হিসেবে চিহ্নিত করুন</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Notification Stats Row --}}
    <div class="grid cols-3" style="margin-bottom:16px;">
        <div class="card kpi">
            <span class="iconbox">♧</span>
            <div>
                <b>{{ $totalCount }}</b>
                <small>মোট নোটিফিকেশন</small>
            </div>
            <span class="trend">সর্বমোট</span>
        </div>

        <div class="card kpi">
            <span class="iconbox" style="background:#fff3e0;color:#e65100">⚠</span>
            <div>
                <b>{{ $unreadCount }}</b>
                <small>অপঠিত নোটিফিকেশন</small>
            </div>
            @if($unreadCount > 0)
                <span class="trend" style="color:var(--red);background:#ffe8e6">মনোযোগ প্রয়োজন</span>
            @else
                <span class="trend">সব পঠিত</span>
            @endif
        </div>

        <div class="card kpi">
            <span class="iconbox" style="background:#e8f5e9;color:#2e7d32">✓</span>
            <div>
                <b>{{ max(0, $totalCount - $unreadCount) }}</b>
                <small>পঠিত নোটিফিকেশন</small>
            </div>
            <span class="trend">সংরক্ষিত</span>
        </div>
    </div>

    {{-- Main Notification Center Card --}}
    <section class="card">
        <div class="card-title">
            <div class="title-left">
                <span class="iconbox">♧</span>
                <div>
                    <h2>নোটিফিকেশন সেন্টার</h2>
                    <div class="sub">সকল ফিল্টারকৃত নোটিফিকেশনের তালিকা ও বিস্তারিত তথ্য</div>
                </div>
            </div>
        </div>

        {{-- Filter Tabs --}}
        <div class="tabs">
            <a href="{{ route('admin.notifications', ['filter' => 'all']) }}" class="tab {{ $filter === 'all' ? 'active' : '' }}">
                সব নোটিফিকেশন ({{ $totalCount }})
            </a>
            <a href="{{ route('admin.notifications', ['filter' => 'unread']) }}" class="tab {{ $filter === 'unread' ? 'active' : '' }}">
                অপঠিত ({{ $unreadCount }})
            </a>
            <a href="{{ route('admin.notifications', ['filter' => 'read']) }}" class="tab {{ $filter === 'read' ? 'active' : '' }}">
                পঠিত ({{ max(0, $totalCount - $unreadCount) }})
            </a>
        </div>

        {{-- Notifications List / Table --}}
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:120px">ধরন</th>
                        <th>শিরোনাম ও বিবরণ</th>
                        <th style="width:160px">সময়</th>
                        <th style="width:100px">অবস্থা</th>
                        <th style="width:200px;text-align:right">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notifications as $notification)
                        @php
                            $data = $notification->data ?? [];
                            $module = $data['module'] ?? 'system';
                            $titleText = $data['title'] ?? 'নোটিফিকেশন';
                            $messageText = $data['message'] ?? '';
                            $actionUrl = $data['action_url'] ?? $data['link'] ?? null;
                            $isUnread = is_null($notification->read_at);
                        @endphp
                        <tr style="{{ $isUnread ? 'background-color: rgba(30, 126, 52, 0.04);' : '' }}">
                            <td>
                                @if($module === 'farmer')
                                    <span class="pill" style="background:#e8f5e9;color:#2e7d32">♟ কৃষক</span>
                                @elseif($module === 'task')
                                    <span class="pill" style="background:#e3f2fd;color:#1565c0">▣ কাজ</span>
                                @elseif($module === 'assignment')
                                    <span class="pill" style="background:#f3e5f5;color:#7b1fa2">✓ বরাদ্দ</span>
                                @elseif($module === 'location')
                                    <span class="pill" style="background:#fff8e1;color:#f57f17">⌖ লোকেশন</span>
                                @else
                                    <span class="pill" style="background:#eceff1;color:#455a64">⚙ সিস্টেম</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;flex-direction:column;gap:4px;">
                                    <strong style="color:var(--text);font-size:14px;">
                                        @if($isUnread)
                                            <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#1e7e34;margin-right:6px;"></span>
                                        @endif
                                        {{ $titleText }}
                                    </strong>
                                    <span style="color:var(--muted);font-size:12px;line-height:1.4;">
                                        {{ $messageText }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div style="display:flex;flex-direction:column;">
                                    <span style="font-weight:600;font-size:12px;">{{ $notification->created_at->diffForHumans() }}</span>
                                    <small style="color:var(--muted);font-size:11px;">{{ $notification->created_at->format('d M Y, h:i A') }}</small>
                                </div>
                            </td>
                            <td>
                                @if($isUnread)
                                    <span class="pill warn">অপঠিত</span>
                                @else
                                    <span class="pill ok">পঠিত</span>
                                @endif
                            </td>
                            <td style="text-align:right">
                                <div style="display:flex;gap:6px;justify-content:flex-end;align-items:center;">
                                    @if($actionUrl)
                                        <a href="{{ $actionUrl }}" class="btn soft" style="padding:4px 8px;font-size:11px;" title="সংশ্লিষ্ট পেজে যান">
                                            বিস্তারিত
                                        </a>
                                    @endif

                                    @if($isUnread)
                                        <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn soft" style="padding:4px 8px;font-size:11px;" title="পঠিত হিসেবে চিহ্নিত করুন">
                                                ✓ পঠিত
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('admin.notifications.destroy', $notification->id) }}" style="display:inline;" onsubmit="return confirm('আপনি কি নিশ্চিত যে এই নোটিফিকেশনটি মুছে ফেলতে চান?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn danger" style="padding:4px 8px;font-size:11px;" title="মুছে ফেলুন">
                                            ✕
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center;padding:48px 16px;color:var(--muted);">
                                <div style="font-size:32px;margin-bottom:8px;">♧</div>
                                <p style="margin:0;font-size:14px;">কোনো নোটিফিকেশন পাওয়া যায়নি।</p>
                                @if($filter !== 'all')
                                    <a href="{{ route('admin.notifications', ['filter' => 'all']) }}" class="btn soft" style="margin-top:12px;display:inline-block;">
                                        সব নোটিফিকেশন দেখুন
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($notifications->hasPages())
            <div style="padding:16px 20px;border-top:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;">
                <div style="font-size:12px;color:var(--muted);">
                    মোট {{ $notifications->total() }} টির মধ্যে {{ $notifications->firstItem() }} থেকে {{ $notifications->lastItem() }} দেখানো হচ্ছে
                </div>
                <div>
                    {{ $notifications->links() }}
                </div>
            </div>
        @endif
    </section>
</div>
@endsection
