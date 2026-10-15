@extends('layouts.admin')

@section('title', "কৃষক বিবরণ — {$farmer->full_name}")

@section('content')
<div class="page" style="max-width: 1000px;">
    <div class="head">
        <div>
            <h1>কৃষক বিবরণ</h1>
            <p>কৃষক আইডি: <span style="font-weight:700;color:var(--g800)">{{ $farmer->farmer_code }}</span> • {{ $farmer->full_name }}</p>
        </div>
        <div class="actions">
            <a href="{{ route('admin.assignments.create', ['farmer_id' => $farmer->id]) }}" class="btn soft">+ কাজ বরাদ্দ</a>
            <a href="{{ route('admin.farmers.edit', $farmer) }}" class="btn primary">তথ্য সম্পাদনা</a>
            <a href="{{ route('admin.farmers.index') }}" class="btn">← তালিকায় ফিরুন</a>
        </div>
    </div>

    <div class="grid cols-2">
        <section class="card">
            <div class="card-title">
                <div class="title-left">
                    <span class="iconbox">♟</span>
                    <div>
                        <h2>ব্যক্তিগত ও যোগাযোগ তথ্য</h2>
                        <div class="sub">কৃষকের মৌলিক প্রোফাইল</div>
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:16px;align-items:center;margin-bottom:18px;">
                @if ($farmer->profile_photo_path)
                    <img src="{{ asset('storage/' . $farmer->profile_photo_path) }}" alt="{{ $farmer->full_name }}" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:2px solid var(--line);">
                @else
                    <div class="avatar" style="width:72px;height:72px;font-size:26px;">
                        {{ mb_substr($farmer->full_name, 0, 1) }}
                    </div>
                @endif
                <div>
                    <h3 style="margin:0;font-size:18px;">{{ $farmer->full_name }}</h3>
                    <div style="color:var(--muted);margin-top:2px;">মোবাইল: <b>{{ $farmer->phone }}</b></div>
                    <div style="margin-top:6px;display:flex;gap:6px;">
                        @include('components.status-pill', ['status' => $farmer->status])
                        @if ($farmer->app_access_enabled)
                            <span class="pill"><span class="dot"></span>অ্যাপ সক্রিয়</span>
                        @else
                            <span class="pill red"><span class="dot"></span>অ্যাপ ব্লক</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="form-grid">
                <div>
                    <span class="label">কৃষক আইডি</span>
                    <b>{{ $farmer->farmer_code }}</b>
                </div>
                <div>
                    <span class="label">জাতীয় পরিচয়পত্র (NID)</span>
                    <b>{{ $farmer->nid ?? 'তথ্য নেই' }}</b>
                </div>
                <div>
                    <span class="label">জন্ম তারিখ</span>
                    <b>{{ $farmer->date_of_birth ? $farmer->date_of_birth->format('d/m/Y') : 'তথ্য নেই' }}</b>
                </div>
                <div>
                    <span class="label">লিঙ্গ</span>
                    <b>{{ $farmer->gender ?? 'পুরুষ' }}</b>
                </div>
                <div class="field full">
                    <span class="label">বর্তমান ঠিকানা</span>
                    <b>{{ $farmer->address }}</b>
                </div>
            </div>

            <div class="section-gap">
                <div class="form-grid">
                    <div>
                        <span class="label">প্রধান কার্যক্রম</span>
                        <span class="pill blue"><span class="dot"></span>{{ $farmer->primary_activity }}</span>
                    </div>
                    <div>
                        <span class="label">অভিজ্ঞতা</span>
                        <b>{{ $farmer->experience_years ? "{$farmer->experience_years} বছর" : 'তথ্য নেই' }}</b>
                    </div>
                    <div>
                        <span class="label">নিবন্ধনের তারিখ</span>
                        <small style="color:var(--muted)">{{ $farmer->created_at->format('d/m/Y h:i A') }}</small>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid">
            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">☎</span>
                        <div>
                            <h2>জরুরি যোগাযোগ</h2>
                            <div class="sub">জরুরি পরিস্থিতিতে যোগাযোগের তথ্য</div>
                        </div>
                    </div>
                </div>

                @if ($farmer->emergencyContact)
                    <div class="form-grid">
                        <div>
                            <span class="label">নাম</span>
                            <b>{{ $farmer->emergencyContact->name }}</b>
                        </div>
                        <div>
                            <span class="label">মোবাইল নম্বর</span>
                            <b>{{ $farmer->emergencyContact->phone }}</b>
                        </div>
                        <div class="field full">
                            <span class="label">সম্পর্ক</span>
                            <span class="pill"><span class="dot"></span>{{ $farmer->emergencyContact->relationship }}</span>
                        </div>
                    </div>
                @else
                    <div class="empty">কোনো জরুরি যোগাযোগ তথ্য পাওয়া যায়নি।</div>
                @endif
            </section>

            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">⌖</span>
                        <div>
                            <h2>বরাদ্দকৃত ফার্ম লোকেশন</h2>
                            <div class="sub">মোট {{ $farmer->farmLocations->count() }}টি লোকেশন সংযুক্ত</div>
                        </div>
                    </div>
                </div>

                @if ($farmer->farmLocations->isNotEmpty())
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>লোকেশন নাম</th>
                                    <th>কোড</th>
                                    <th>ধরন</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($farmer->farmLocations as $loc)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.locations.show', $loc) }}" style="color:var(--text);text-decoration:underline;">
                                                <b>{{ $loc->name }}</b>
                                            </a>
                                            @if($loc->hasBoundaryPolygon())
                                                <span class="pill" style="padding:1px 5px;font-size:9px;background:#e8f5e9;color:#2e7d32;border:1px solid #c8e6c9;">সীমানা আছে</span>
                                            @endif
                                        </td>
                                        <td>{{ $loc->location_code }}</td>
                                        <td>{{ $loc->type }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty" style="padding:15px;">এখনও কোনো লোকেশন বরাদ্দ করা হয়নি।</div>
                @endif
            </section>
        </div>
    </div>

    <section class="card section-gap">
        <div class="card-title">
            <div class="title-left">
                <span class="iconbox">▣</span>
                <div>
                    <h2>কাজের ইতিহাস ও অ্যাসাইনমেন্ট সারাংশ</h2>
                    <div class="sub">সর্বশেষ ১০টি কাজের তালিকা</div>
                </div>
            </div>
        </div>

        @if ($farmer->taskAssignments->isNotEmpty())
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>কাজ</th>
                            <th>লোকেশন</th>
                            <th>তারিখ</th>
                            <th>সময়</th>
                            <th>অবস্থা</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($farmer->taskAssignments as $assignment)
                            <tr>
                                <td>
                                    <b>{{ $assignment->taskTemplate->name ?? 'অজ্ঞাত কাজ' }}</b>
                                    <br><small style="color:var(--muted)">{{ $assignment->taskTemplate->task_code ?? '' }}</small>
                                </td>
                                <td>{{ $assignment->farmLocation->name ?? '—' }}</td>
                                <td>{{ $assignment->scheduled_date->format('d/m/Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($assignment->start_time)->format('H:i') }}</td>
                                <td>
                                    @include('components.status-pill', ['status' => $assignment->status])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty">এই কৃষকের জন্য এখনও কোনো কাজ রেকর্ড নেই।</div>
        @endif
    </section>
</div>
@endsection
