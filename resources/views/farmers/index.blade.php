@extends('layouts.admin')

@section('title', 'কৃষক ব্যবস্থাপনা — আমার ফার্ম')

@section('content')
<div class="page">
    <div class="head">
        <div>
            <h1>কৃষক ব্যবস্থাপনা</h1>
            <p>কৃষক, অ্যাপ অ্যাক্সেস, বরাদ্দ ও বর্তমান কাজ পরিচালনা করুন</p>
        </div>
        <div class="actions">
            <a href="{{ route('admin.farmers.create') }}" class="btn primary">+ নতুন কৃষক</a>
        </div>
    </div>

    <section class="card">
        <div class="card-title">
            <div class="title-left">
                <span class="iconbox">♟</span>
                <div>
                    <h2>কৃষক তালিকা</h2>
                    <div class="sub">একজন কৃষক একাধিক কাজ করতে পারবেন; প্রতিটি কাজ একজন কৃষককেই বরাদ্দ হবে</div>
                </div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <form method="GET" action="{{ route('admin.farmers.index') }}" style="display:flex;gap:8px;">
                    @if($status)
                        <input type="hidden" name="status" value="{{ $status }}">
                    @endif
                    <input class="input" type="text" name="search" value="{{ $search }}" placeholder="নাম, আইডি বা ফোন..." style="width:220px;padding:6px 10px;font-size:12px">
                    <button type="submit" class="btn soft" style="padding:6px 12px">খুঁজুন</button>
                    @if($search)
                        <a href="{{ route('admin.farmers.index', array_filter(['status' => $status])) }}" class="btn" style="padding:6px 10px" title="সার্চ মুছুন">✕</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="tabs">
            <a href="{{ route('admin.farmers.index', array_filter(['search' => $search])) }}" class="tab {{ !$status ? 'active' : '' }}">
                সব কৃষক ({{ $totalFarmersCount }})
            </a>
            <a href="{{ route('admin.farmers.index', array_filter(['status' => 'active', 'search' => $search])) }}" class="tab {{ $status === 'active' ? 'active' : '' }}">
                সক্রিয় ({{ $activeFarmersCount }})
            </a>
            <a href="{{ route('admin.farmers.index', array_filter(['status' => 'inactive', 'search' => $search])) }}" class="tab {{ $status === 'inactive' ? 'active' : '' }}">
                নিষ্ক্রিয় ({{ $inactiveFarmersCount }})
            </a>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>কৃষক</th>
                        <th>আইডি</th>
                        <th>প্রধান কার্যক্রম</th>
                        <th>লোকেশন</th>
                        <th>সক্রিয় কাজ</th>
                        <th>অ্যাপ</th>
                        <th>অবস্থা</th>
                        <th style="text-align:right">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($farmers as $farmer)
                        <tr>
                            <td>
                                <div style="display:flex;gap:10px;align-items:center;">
                                    @if ($farmer->profile_photo_path)
                                        <img src="{{ asset('storage/' . $farmer->profile_photo_path) }}" alt="{{ $farmer->full_name }}" style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:1px solid var(--line);">
                                    @else
                                        <div class="avatar" style="width:34px;height:34px;font-size:13px;flex:none;">
                                            {{ mb_substr($farmer->full_name, 0, 1) }}
                                        </div>
                                    @endif
                                    <div>
                                        <b>{{ $farmer->full_name }}</b>
                                        <br><small style="color:var(--muted)">{{ $farmer->phone }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><span style="font-weight:600;color:var(--g800)">{{ $farmer->farmer_code }}</span></td>
                            <td>{{ $farmer->primary_activity }}</td>
                            <td>{{ $farmer->farm_locations_count }}</td>
                            <td>{{ $farmer->active_tasks_count }}</td>
                            <td>
                                @if ($farmer->app_access_enabled)
                                    <span class="pill"><span class="dot"></span>সক্রিয়</span>
                                @else
                                    <span class="pill red"><span class="dot"></span>ব্লক</span>
                                @endif
                            </td>
                            <td>
                                @include('components.status-pill', ['status' => $farmer->status])
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex;gap:5px;">
                                    <a href="{{ route('admin.farmers.show', $farmer) }}" class="btn" style="padding:5px 9px;font-size:11px">দেখুন</a>
                                    <a href="{{ route('admin.farmers.edit', $farmer) }}" class="btn soft" style="padding:5px 9px;font-size:11px">সম্পাদনা</a>
                                    <form method="POST" action="{{ route('admin.farmers.destroy', $farmer) }}" style="display:inline;" onsubmit="return confirm('আপনি কি নিশ্চিতভাবে এই কৃষককে মুছে ফেলতে চান? এটি ডাটাবেজ থেকে স্থায়ীভাবে মুছে যাবে।');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn danger" style="padding:5px 9px;font-size:11px">মুছুন</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty">
                                কোনো কৃষক খুঁজে পাওয়া যায়নি।
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($farmers->hasPages())
            <div style="margin-top:20px;display:flex;justify-content:center;">
                {{ $farmers->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
