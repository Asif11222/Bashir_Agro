@extends('layouts.admin')

@section('title', 'কাজ ব্যবস্থাপনা — আমার ফার্ম')

@section('content')
<div class="page">
    <div class="head">
        <div>
            <h1>কাজ ব্যবস্থাপনা</h1>
            <p>কাজের টেমপ্লেট, সময়, প্রমাণ, জিও-ফেন্স ও বরাদ্দ নিয়ম পরিচালনা করুন</p>
        </div>
        <div class="actions">
            <a href="{{ route('admin.tasks.create') }}" class="btn primary">+ নতুন কাজ</a>
        </div>
    </div>

    <div class="grid cols-2">
        <div>
            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">▣</span>
                        <div>
                            <h2>কাজের টেমপ্লেট</h2>
                            <div class="sub">পুনরায় ব্যবহারযোগ্য কাজের কাঠামো ও নিয়মাবলী</div>
                        </div>
                    </div>
                </div>

                <div style="display:flex;gap:8px;align-items:center;margin-bottom:14px;flex-wrap:wrap;">
                    <form method="GET" action="{{ route('admin.tasks.index') }}" style="display:flex;gap:8px;flex:1;">
                        @if($status)
                            <input type="hidden" name="status" value="{{ $status }}">
                        @endif
                        <input class="input" type="text" name="search" value="{{ $search }}" placeholder="কাজের নাম, কোড বা শ্রেণি দিয়ে খুঁজুন..." style="padding:6px 10px;font-size:12px">
                        <button type="submit" class="btn soft" style="padding:6px 12px">খুঁজুন</button>
                        @if($search)
                            <a href="{{ route('admin.tasks.index', array_filter(['status' => $status])) }}" class="btn" style="padding:6px 10px" title="সার্চ মুছুন">✕</a>
                        @endif
                    </form>
                </div>

                <div class="tabs">
                    <a href="{{ route('admin.tasks.index', array_filter(['search' => $search])) }}" class="tab {{ !$status ? 'active' : '' }}">
                        সব ({{ $totalTasksCount }})
                    </a>
                    <a href="{{ route('admin.tasks.index', array_filter(['status' => 'active', 'search' => $search])) }}" class="tab {{ $status === 'active' ? 'active' : '' }}">
                        সক্রিয় ({{ $activeTasksCount }})
                    </a>
                    <a href="{{ route('admin.tasks.index', array_filter(['status' => 'inactive', 'search' => $search])) }}" class="tab {{ $status === 'inactive' ? 'active' : '' }}">
                        নিষ্ক্রিয় ({{ $inactiveTasksCount }})
                    </a>
                </div>

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>কাজ</th>
                                <th>শ্রেণি</th>
                                <th>ছবি</th>
                                <th>জিও-ফেন্স</th>
                                <th>পুনরাবৃত্তি</th>
                                <th>অবস্থা</th>
                                <th style="text-align:right">অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tasks as $task)
                                <tr>
                                    <td>
                                        <b>{{ $task->name }}</b>
                                        <br><small style="color:var(--muted)">{{ $task->task_code }}</small>
                                    </td>
                                    <td>{{ $task->category }}</td>
                                    <td>
                                        <span>{{ $task->required_photo_count }}টি</span>
                                        @if($task->require_realtime_photo)
                                            • <small style="color:var(--g700)">ক্যামেরা</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->require_geofence)
                                            <span class="pill"><span class="dot"></span>চালু</span>
                                        @else
                                            <span class="pill red"><span class="dot"></span>বন্ধ</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $recurrenceMap = [
                                                'once' => 'এককালীন',
                                                'daily' => 'দৈনিক',
                                                'weekly' => 'সাপ্তাহিক',
                                                'monthly' => 'মাসিক',
                                                'এককালীন' => 'এককালীন',
                                                'দৈনিক' => 'দৈনিক',
                                                'সাপ্তাহিক' => 'সাপ্তাহিক',
                                                'মাসিক' => 'মাসিক',
                                            ];
                                        @endphp
                                        {{ $recurrenceMap[$task->recurrence_type] ?? $task->recurrence_type }}
                                    </td>
                                    <td>
                                        @include('components.status-pill', ['status' => $task->status])
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:inline-flex;gap:4px;">
                                            <a href="{{ route('admin.tasks.show', $task) }}" class="btn" style="padding:4px 8px;font-size:11px">দেখুন</a>
                                            <a href="{{ route('admin.tasks.edit', $task) }}" class="btn soft" style="padding:4px 8px;font-size:11px">সম্পাদনা</a>
                                            <form method="POST" action="{{ route('admin.tasks.destroy', $task) }}" style="display:inline;" onsubmit="return confirm('আপনি কি নিশ্চিতভাবে এই কাজের টেমপ্লেট মুছে ফেলতে চান? এটি সফট ডিলিট হবে।');">
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
                                        কোনো কাজের টেমপ্লেট পাওয়া যায়নি।
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($tasks->hasPages())
                    <div style="margin-top:16px;display:flex;justify-content:center;">
                        {{ $tasks->links() }}
                    </div>
                @endif
            </section>
        </div>

        <div>
            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">✓</span>
                        <div>
                            <h2>সিস্টেম নিয়ম</h2>
                            <div class="sub">বর্তমান ওয়ার্কফ্লো ও সাবমিশন নীতিমালা</div>
                        </div>
                    </div>
                </div>

                <div class="rule">
                    <span>
                        <b>এক কাজ = এক কৃষক</b>
                        <div class="sub">একই কাজ নির্দিষ্ট সময়ে একাধিক কৃষককে দেওয়া যাবে না</div>
                    </span>
                    <span class="pill"><span class="dot"></span>প্রয়োগকৃত</span>
                </div>

                <div class="rule">
                    <span>
                        <b>দেরিতে জমা দেওয়া যাবে</b>
                        <div class="sub">সাবমিশন গ্রহণ হবে, কিন্তু লাল পতাকা (Late flag) দেখাবে</div>
                    </span>
                    <span class="pill red"><span class="dot"></span>Late flag</span>
                </div>

                <div class="rule">
                    <span>
                        <b>লোকেশন যাচাই</b>
                        <div class="sub">নির্ধারিত জিও-ফেন্সের বাইরে বৈধ সাবমিশন ব্লক হবে</div>
                    </span>
                    <span class="pill"><span class="dot"></span>চালু</span>
                </div>

                <div class="notice section-gap">
                    <b>নির্দেশনা:</b> কাজ তৈরির সময় উপযুক্ত প্রমাণের সংখ্যা এবং জিও-ফেন্সিং সক্রিয় রাখুন যাতে কৃষক মাঠে অবস্থানকালীন সত্যনিষ্ঠ প্রতিবেদন জমা দিতে পারেন।
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
