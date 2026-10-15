@extends('layouts.admin')

@section('title', 'ফার্ম লোকেশন — আমার ফার্ম')

@section('content')
<div class="page">
    <div class="head">
        <div>
            <h1>ফার্ম লোকেশন</h1>
            <p>মাঠ, পুকুর, খামার ও জিও-ফেন্স পরিচালনা করুন</p>
        </div>
        <div class="actions">
            <a href="{{ route('admin.locations.create') }}" class="btn primary">+ নতুন লোকেশন</a>
        </div>
    </div>

    <div class="grid cols-2">
        <div>
            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">⌖</span>
                        <div>
                            <h2>লোকেশনসমূহ</h2>
                            <div class="sub">Owner তৈরি করা লোকেশন সঙ্গে সঙ্গে সক্রিয় হয়</div>
                        </div>
                    </div>
                </div>

                <div style="display:flex;gap:8px;align-items:center;margin-bottom:14px;flex-wrap:wrap;">
                    <form method="GET" action="{{ route('admin.locations.index') }}" style="display:flex;gap:8px;flex:1;">
                        @if($status)
                            <input type="hidden" name="status" value="{{ $status }}">
                        @endif
                        <input class="input" type="text" name="search" value="{{ $search }}" placeholder="নাম, কোড বা ধরন দিয়ে খুঁজুন..." style="padding:6px 10px;font-size:12px">
                        <button type="submit" class="btn soft" style="padding:6px 12px">খুঁজুন</button>
                        @if($search)
                            <a href="{{ route('admin.locations.index', array_filter(['status' => $status])) }}" class="btn" style="padding:6px 10px" title="সার্চ মুছুন">✕</a>
                        @endif
                    </form>
                </div>

                <div class="tabs">
                    <a href="{{ route('admin.locations.index', array_filter(['search' => $search])) }}" class="tab {{ !$status ? 'active' : '' }}">
                        সব ({{ $totalLocationsCount }})
                    </a>
                    <a href="{{ route('admin.locations.index', array_filter(['status' => 'active', 'search' => $search])) }}" class="tab {{ $status === 'active' ? 'active' : '' }}">
                        সক্রিয় ({{ $activeLocationsCount }})
                    </a>
                    <a href="{{ route('admin.locations.index', array_filter(['status' => 'inactive', 'search' => $search])) }}" class="tab {{ $status === 'inactive' ? 'active' : '' }}">
                        নিষ্ক্রিয় ({{ $inactiveLocationsCount }})
                    </a>
                </div>

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>লোকেশন</th>
                                <th>ধরন</th>
                                <th>এলাকা</th>
                                <th>কৃষক</th>
                                <th>বাফার</th>
                                <th>অবস্থা</th>
                                <th style="text-align:right">অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($locations as $location)
                                <tr>
                                    <td>
                                        <b>{{ $location->name }}</b>
                                        <br><small style="color:var(--muted)">{{ $location->location_code }}</small>
                                        @if($location->hasBoundaryPolygon())
                                            <br><span class="pill" style="padding:1px 6px;font-size:10px;background:#e8f5e9;color:#2e7d32;border:1px solid #c8e6c9;margin-top:2px;">
                                                <span class="dot" style="background:#2e7d32"></span>বহুভুজ সীমানা
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $location->type }}</td>
                                    <td>{{ $location->area ? "{$location->area} {$location->area_unit}" : '—' }}</td>
                                    <td>
                                        <span class="pill blue" style="padding:2px 7px;font-size:11px;">
                                            {{ $location->farmers_count }} জন
                                        </span>
                                    </td>
                                    <td>{{ $location->geofence_radius_meters }} মি</td>
                                    <td>
                                        @include('components.status-pill', ['status' => $location->status])
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:inline-flex;gap:4px;">
                                            <a href="{{ route('admin.locations.show', $location) }}" class="btn" style="padding:4px 8px;font-size:11px">দেখুন</a>
                                            <a href="{{ route('admin.locations.edit', $location) }}" class="btn soft" style="padding:4px 8px;font-size:11px">সম্পাদনা</a>
                                            <form method="POST" action="{{ route('admin.locations.destroy', $location) }}" style="display:inline;" onsubmit="return confirm('আপনি কি নিশ্চিতভাবে এই লোকেশন মুছে ফেলতে চান? এটি সফট ডিলিট হবে।');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn danger" style="padding:4px 8px;font-size:11px">মুছুন</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="empty">
                                        কোনো লোকেশন খুঁজে পাওয়া যায়নি।
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($locations->hasPages())
                    <div style="margin-top:16px;display:flex;justify-content:center;">
                        {{ $locations->links() }}
                    </div>
                @endif
            </section>
        </div>

        <div>
            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">⌖</span>
                        <div>
                            <h2>লোকেশন মানচিত্র</h2>
                            <div class="sub">নির্বাচিত লোকেশনের সীমানা ও কেন্দ্র (ভিজ্যুয়াল প্রোটোটাইপ)</div>
                        </div>
                    </div>
                </div>

                <div class="geo">
                    <div class="polygon"></div>
                    <div class="pin">📍</div>
                    <div class="geo-tools">
                        <button type="button">সীমানা আঁকুন</button>
                        <button type="button">পিন বসান</button>
                        <button type="button">রিসেট</button>
                    </div>
                </div>

                <div class="notice section-gap">
                    ডিফল্ট অনুমোদিত বাফার: <b>২০ মিটার</b>। প্রতিটি লোকেশনে Admin প্রয়োজনে পরিবর্তন করতে পারবেন।
                </div>

                <div style="margin-top:14px;background:#f9fcf9;border:1px solid var(--line);border-radius:10px;padding:12px;font-size:12px;">
                    <div style="font-weight:700;margin-bottom:6px;color:var(--g800)">💡 অটোমেটেড জিও-ফেন্সিং নির্দেশনা:</div>
                    <p style="margin:0;color:var(--muted);line-height:1.5;">
                        সেন্টার GPS স্থানাঙ্ক (Latitude / Longitude) এবং নির্ধারিত বাফার রেডিয়াস অনুযায়ী কৃষক মোবাইল অ্যাপে কাজ শুরুর সময় অবস্থান স্বয়ংক্রিয়ভাবে যাচাই হবে।
                    </p>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
