@extends('layouts.admin')

@section('title', "লোকেশন বিবরণ — {$location->name}")

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet" />
    <style>
        .map-wrapper {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--line);
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            background: #111;
        }
        #field-show-map {
            width: 100%;
            height: 380px;
        }
        .boundary-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .boundary-status-badge.is-polygon {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        .boundary-status-badge.is-point {
            background: #fff3e0;
            color: #e65100;
            border: 1px solid #ffe0b2;
        }
    </style>
@endpush

@section('content')
<div class="page">
    <div class="head">
        <div>
            <h1>লোকেশন ও মাঠের সীমানা বিবরণ</h1>
            <p>লোকেশন কোড: <span style="font-weight:700;color:var(--g800)">{{ $location->location_code }}</span> • {{ $location->name }}</p>
        </div>
        <div class="actions">
            <a href="{{ route('admin.locations.edit', $location) }}" class="btn primary">সীমানা ও তথ্য সম্পাদনা</a>
            <a href="{{ route('admin.locations.index') }}" class="btn">← তালিকায় ফিরুন</a>
        </div>
    </div>

    <div class="grid cols-2">
        <section class="card">
            <div class="card-title">
                <div class="title-left">
                    <span class="iconbox">⌖</span>
                    <div>
                        <h2>লোকেশন তথ্য</h2>
                        <div class="sub">প্লট বা ফার্মের মূল বিবরণ ও সীমানা অবস্থা</div>
                    </div>
                </div>
            </div>

            <div class="form-grid">
                <div>
                    <span class="label">লোকেশন নাম</span>
                    <b style="font-size:16px;">{{ $location->name }}</b>
                </div>
                <div>
                    <span class="label">লোকেশন কোড</span>
                    <span style="font-weight:700;color:var(--g800)">{{ $location->location_code }}</span>
                </div>
                <div>
                    <span class="label">ধরন</span>
                    <span class="pill blue"><span class="dot"></span>{{ $location->type }}</span>
                </div>
                <div>
                    <span class="label">অবস্থা</span>
                    @include('components.status-pill', ['status' => $location->status])
                </div>
                <div>
                    <span class="label">সীমানা অবস্থা</span>
                    @if($location->hasBoundaryPolygon())
                        <span class="boundary-status-badge is-polygon">
                            <span>🛰️</span> পোস্টজিআইএস পলিগন সক্রিয়
                        </span>
                    @else
                        <span class="boundary-status-badge is-point">
                            <span>📍</span> শুধু কেন্দ্রবিন্দু স্থানাঙ্ক
                        </span>
                    @endif
                </div>
                <div>
                    <span class="label">দায়িত্বপ্রাপ্ত প্রধান কৃষক</span>
                    @if($location->farmer)
                        <a href="{{ route('admin.farmers.show', $location->farmer) }}" style="font-weight:600;color:var(--g800);text-decoration:underline;">
                            {{ $location->farmer->full_name }} ({{ $location->farmer->farmer_code }})
                        </a>
                    @else
                        <small style="color:var(--muted)">নির্ধারিত নেই</small>
                    @endif
                </div>
                <div>
                    <span class="label">জমির পরিমাণ</span>
                    <b>{{ $location->area ? "{$location->area} {$location->area_unit}" : 'তথ্য নেই' }}</b>
                </div>
                <div>
                    <span class="label">বাফার রেডিয়াস</span>
                    <b>{{ $location->geofence_radius_meters }} মিটার</b>
                </div>
                <div class="field full">
                    <span class="label">প্রশাসনিক ঠিকানা</span>
                    <b>{{ $location->address }}</b>
                </div>
                <div>
                    <span class="label">কেন্দ্র অক্ষাংশ (Latitude)</span>
                    <small>{{ $location->latitude ?? 'সেট করা নেই' }}</small>
                </div>
                <div>
                    <span class="label">কেন্দ্র দ্রাঘিমাংশ (Longitude)</span>
                    <small>{{ $location->longitude ?? 'সেট করা নেই' }}</small>
                </div>
            </div>
        </section>

        <div>
            <section class="card">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">🛰</span>
                        <div>
                            <h2>স্যাটেলাইট মানচিত্র ভিউ</h2>
                            <div class="sub">
                                কেন্দ্র: {{ $location->latitude ?? '24.7471' }}, {{ $location->longitude ?? '90.4203' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="map-wrapper">
                    <div id="field-show-map"></div>
                </div>

                <div class="notice section-gap">
                    @if($location->hasBoundaryPolygon())
                        ✅ এই ফার্মে <b>PostGIS বহুভুজ সীমানা (Field Boundary)</b> কার্যকর রয়েছে। কৃষক কাজ জমা দেওয়ার সময় ব্যাকএন্ড তার মোবাইল জিপিএস এই সীমানার ভেতর অবস্থান করছে কিনা তা যাচাই করবে।
                    @else
                        ℹ️ এই ফার্মে এখনও বহুভুজ সীমানা আঁকা হয়নি। কেন্দ্রবিন্দুর <b>{{ $location->geofence_radius_meters }} মিটার</b> রেডিয়াসে জিও-ফেন্সিং সক্রিয় রয়েছে।
                    @endif
                </div>
            </section>
        </div>
    </div>

    <section class="card section-gap">
        <div class="card-title">
            <div class="title-left">
                <span class="iconbox">♟</span>
                <div>
                    <h2>বরাদ্দকৃত কৃষকবৃন্দ (মোট {{ $location->farmers->count() }} জন)</h2>
                    <div class="sub">যে কৃষকরা এই লোকেশনে নির্ধারিত কাজ করার জন্য অনুমোদিত</div>
                </div>
            </div>

            @if($availableFarmers->isNotEmpty())
                <form method="POST" action="{{ route('admin.locations.farmers.attach', $location) }}" style="display:flex;gap:8px;">
                    @csrf
                    <select class="select" name="farmer_id" style="width:220px;padding:6px 10px;font-size:12px;" required>
                        <option value="">+ কৃষক নির্বাচন করুন</option>
                        @foreach($availableFarmers as $af)
                            <option value="{{ $af->id }}">{{ $af->full_name }} ({{ $af->farmer_code }})</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn soft" style="padding:6px 12px;font-size:12px;">যুক্ত করুন</button>
                </form>
            @endif
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>কৃষক</th>
                        <th>আইডি</th>
                        <th>মোবাইল</th>
                        <th>প্রধান কার্যক্রম</th>
                        <th>অ্যাপ অ্যাক্সেস</th>
                        <th style="text-align:right">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($location->farmers as $farmer)
                        <tr>
                            <td>
                                <b>{{ $farmer->full_name }}</b>
                            </td>
                            <td><span style="font-weight:600;color:var(--g800)">{{ $farmer->farmer_code }}</span></td>
                            <td>{{ $farmer->phone }}</td>
                            <td>{{ $farmer->primary_activity }}</td>
                            <td>
                                @if($farmer->app_access_enabled)
                                    <span class="pill"><span class="dot"></span>সক্রিয়</span>
                                @else
                                    <span class="pill red"><span class="dot"></span>ব্লক</span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex;gap:6px;">
                                    <a href="{{ route('admin.farmers.show', $farmer) }}" class="btn" style="padding:4px 8px;font-size:11px">প্রোফাইল</a>
                                    <form method="POST" action="{{ route('admin.locations.farmers.detach', [$location, $farmer]) }}" onsubmit="return confirm('আপনি কি এই কৃষককে এই লোকেশন থেকে প্রত্যাহার করতে চান? (কৃষক অ্যাকাউন্ট মুছে যাবে না)');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn danger" style="padding:4px 8px;font-size:11px">প্রত্যাহার</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty">
                                এই লোকেশনে বর্তমানে কোনো কৃষক বরাদ্দ নেই।
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if($location->taskAssignments->isNotEmpty())
        <section class="card section-gap">
            <div class="card-title">
                <div class="title-left">
                    <span class="iconbox">▣</span>
                    <div>
                        <h2>সাম্প্রতিক কাজের অ্যাসাইনমেন্ট</h2>
                        <div class="sub">এই লোকেশনে পরিচালিত সাম্প্রতিক কার্যক্রম</div>
                    </div>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>কাজ</th>
                            <th>দায়িত্বপ্রাপ্ত কৃষক</th>
                            <th>তারিখ</th>
                            <th>সময়</th>
                            <th>অবস্থা</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($location->taskAssignments as $assignment)
                            <tr>
                                <td>
                                    <b>{{ $assignment->taskTemplate->name ?? 'অজ্ঞাত কাজ' }}</b>
                                    <br><small style="color:var(--muted)">{{ $assignment->taskTemplate->task_code ?? '' }}</small>
                                </td>
                                <td>
                                    {{ $assignment->farmer->full_name ?? '—' }}
                                    <br><small style="color:var(--muted)">{{ $assignment->farmer->farmer_code ?? '' }}</small>
                                </td>
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
        </section>
    @endif
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6.5.0/turf.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Ultra high-res Google Hybrid Satellite (Photographic + Village Roads + Boundaries)
            const satelliteStyle = {
                version: 8,
                sources: {
                    'google-hybrid': {
                        type: 'raster',
                        tiles: [
                            'https://mt0.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
                            'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
                            'https://mt2.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
                            'https://mt3.google.com/vt/lyrs=y&x={x}&y={y}&z={z}'
                        ],
                        tileSize: 256,
                        attribution: '&copy; Google Satellite'
                    }
                },
                layers: [
                    {
                        id: 'google-hybrid-layer',
                        type: 'raster',
                        source: 'google-hybrid',
                        minzoom: 0,
                        maxzoom: 22
                    }
                ]
            };

            const lat = {{ $location->latitude ?: 24.7471 }};
            const lng = {{ $location->longitude ?: 90.4203 }};

            const map = new maplibregl.Map({
                container: 'field-show-map',
                style: satelliteStyle,
                center: [lng, lat],
                zoom: 16
            });

            // Prevent layout collapse & ensure tiles render smoothly
            map.on('load', function () {
                setTimeout(function () {
                    map.resize();
                }, 150);
            });
            window.addEventListener('resize', function () {
                map.resize();
            });

            map.addControl(new maplibregl.NavigationControl(), 'top-right');
            map.addControl(new maplibregl.FullscreenControl(), 'top-right');

            new maplibregl.Marker({ color: '#ff5722' })
                .setLngLat([lng, lat])
                .setPopup(new maplibregl.Popup({ offset: 25 }).setHTML(`<b>{{ $location->name }}</b><br>{{ $location->type }}`))
                .addTo(map);

            const geoJsonData = @json($location->boundary_geojson);

            if (geoJsonData && geoJsonData.coordinates) {
                function renderBoundary() {
                    if (map.getSource('farm-boundary-source')) return;

                    map.addSource('farm-boundary-source', {
                        'type': 'geojson',
                        'data': {
                            'type': 'Feature',
                            'geometry': geoJsonData
                        }
                    });

                    // Polygon Fill
                    map.addLayer({
                        'id': 'farm-boundary-fill',
                        'type': 'fill',
                        'source': 'farm-boundary-source',
                        'layout': {},
                        'paint': {
                            'fill-color': '#00e676',
                            'fill-opacity': 0.35
                        }
                    });

                    // Polygon Line Stroke
                    map.addLayer({
                        'id': 'farm-boundary-stroke',
                        'type': 'line',
                        'source': 'farm-boundary-source',
                        'layout': {},
                        'paint': {
                            'line-color': '#00e676',
                            'line-width': 3
                        }
                    });

                    try {
                        const bbox = turf.bbox({ type: 'Feature', geometry: geoJsonData });
                        map.fitBounds(bbox, { padding: 50, maxZoom: 18 });
                    } catch (e) {
                        console.error('BBox fit error', e);
                    }
                }

                if (map.loaded()) {
                    renderBoundary();
                } else {
                    map.on('load', renderBoundary);
                }
            }
        });
    </script>
@endpush
