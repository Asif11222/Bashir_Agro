@extends('layouts.admin')

@section('title', 'নতুন লোকেশন ও ফার্ম সীমানা যোগ করুন — আমার ফার্ম')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-draw/v1.4.3/mapbox-gl-draw.css" type="text/css" />
    <style>
        .map-wrapper {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--line);
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            background: #1e293b;
        }
        #field-map {
            width: 100%;
            height: 480px;
            min-height: 480px;
            position: relative;
        }
        @media (max-width: 768px) {
            #field-map {
                height: 320px;
                min-height: 320px;
            }
            .map-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }
            .map-tools {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 4px;
            }
        }
        .map-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            background: #fff;
            border-bottom: 1px solid var(--line);
            gap: 10px;
            flex-wrap: wrap;
        }
        .map-tools {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .map-tool-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 500;
            background: #fbfdfc;
            border: 1px solid var(--line);
            border-radius: 7px;
            cursor: pointer;
            color: var(--text);
            transition: all 0.15s ease;
        }
        .map-tool-btn:hover {
            background: var(--bg);
            border-color: var(--g800);
        }
        .map-tool-btn.active {
            background: var(--g800);
            color: #fff;
            border-color: var(--g800);
        }
        .map-tool-btn.danger {
            color: #d32f2f;
        }
        .map-tool-btn.danger:hover {
            background: #ffebee;
            border-color: #ef9a9a;
        }
        .live-area-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: #e8f5e9;
            color: #1b5e20;
            border: 1px solid #c8e6c9;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
        }
        .map-search-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .map-search-input {
            padding: 6px 12px;
            font-size: 13px;
            width: 260px;
            border: 1px solid var(--line);
            border-radius: 7px;
            background: #fbfdfc;
            outline: none;
            transition: all 0.2s ease;
        }
        .map-search-input:focus {
            border-color: var(--g800);
            box-shadow: 0 0 0 2px rgba(46,125,50,0.15);
            background: #fff;
        }
        .map-search-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 8px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
            z-index: 9999;
            max-height: 220px;
            overflow-y: auto;
            margin-top: 4px;
        }
        .map-search-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f5f5f5;
            font-size: 12px;
            transition: background 0.1s;
        }
        .map-search-item:hover {
            background: #f0f7f2;
        }
    </style>
@endpush

@section('content')
<div class="page">
    <div class="head">
        <div>
            <h1>নতুন ফার্ম ও মাঠের সীমানা যোগ করুন</h1>
            <p>স্যাটেলাইট মানচিত্রে ফসলি জমির বহুভুজ সীমানা (PostGIS Polygon) চিহ্নিত করুন ও সংরক্ষণ করুন</p>
        </div>
        <div class="actions">
            <a href="{{ route('admin.locations.index') }}" class="btn">← লোকেশন তালিকায় ফিরুন</a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.locations.store') }}" id="farm-location-form">
        @csrf
        <input type="hidden" name="boundary" id="boundary_input" value="{{ old('boundary') }}">

        <section class="card">
            <div class="card-title">
                <div class="title-left">
                    <span class="iconbox">⌖</span>
                    <div>
                        <h2>প্রাথমিক বিবরণ</h2>
                        <div class="sub">ফার্ম বা প্লটের মৌলিক তথ্য ও কৃষক নির্ধারণ</div>
                    </div>
                </div>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span class="label">লোকেশনের নাম <span class="req">*</span></span>
                    <input class="input" type="text" name="name" value="{{ old('name') }}" placeholder="যেমন: উত্তর ধানের মাঠ ১" required>
                    @error('name')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </label>

                <label class="field">
                    <span class="label">দায়িত্বপ্রাপ্ত প্রধান কৃষক (ঐচ্ছিক)</span>
                    <select class="select" name="farmer_id" id="farmer_select">
                        <option value="">-- কৃষক নির্বাচন করুন --</option>
                        @foreach($farmers as $f)
                            <option value="{{ $f->id }}" {{ old('farmer_id') == $f->id ? 'selected' : '' }}>
                                {{ $f->full_name }} ({{ $f->farmer_code }}) • {{ $f->primary_activity }}
                            </option>
                        @endforeach
                    </select>
                    @error('farmer_id')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </label>

                <label class="field">
                    <span class="label">লোকেশনের ধরন <span class="req">*</span></span>
                    <select class="select" name="type" required>
                        <option value="ধানের জমি" {{ old('type') === 'ধানের জমি' ? 'selected' : '' }}>ধানের জমি</option>
                        <option value="সবজি ক্ষেত" {{ old('type') === 'সবজি ক্ষেত' ? 'selected' : '' }}>সবজি ক্ষেত</option>
                        <option value="মাছের পুকুর" {{ old('type') === 'মাছের পুকুর' ? 'selected' : '' }}>মাছের পুকুর</option>
                        <option value="গরুর খামার" {{ old('type') === 'গরুর খামার' ? 'selected' : '' }}>গরুর খামার</option>
                        <option value="ফলের বাগান" {{ old('type') === 'ফলের বাগান' ? 'selected' : '' }}>ফলের বাগান</option>
                        <option value="স্টোর" {{ old('type') === 'স্টোর' ? 'selected' : '' }}>স্টোর</option>
                        <option value="অন্যান্য" {{ old('type') === 'অন্যান্য' ? 'selected' : '' }}>অন্যান্য</option>
                    </select>
                    @error('type')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </label>

                <div class="field">
                    <span class="label">জমির পরিমাণ ও একক</span>
                    <div style="display:flex;gap:8px;">
                        <input class="input" type="number" step="0.01" name="area" id="area_input" value="{{ old('area') }}" placeholder="স্বয়ংক্রিয় হিসেব হবে" style="flex:1;">
                        <select class="select" name="area_unit" id="area_unit_select" style="width:115px;">
                            <option value="একর" {{ old('area_unit', 'একর') === 'একর' ? 'selected' : '' }}>একর</option>
                            <option value="শতাংশ" {{ old('area_unit') === 'শতাংশ' ? 'selected' : '' }}>শতাংশ</option>
                            <option value="বিঘা" {{ old('area_unit') === 'বিঘা' ? 'selected' : '' }}>বিঘা</option>
                            <option value="হেক্টর" {{ old('area_unit') === 'হেক্টর' ? 'selected' : '' }}>হেক্টর</option>
                        </select>
                    </div>
                    <small style="color:var(--muted);font-size:12px;margin-top:3px;">স্যাটেলাইটে বহুভুজ সীমানা আঁকার সাথে সাথে স্বয়ংক্রিয়ভাবে গণনা হবে।</small>
                    @error('area')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <label class="field full">
                    <span class="label">প্রশাসনিক ঠিকানা <span class="req">*</span></span>
                    <textarea class="textarea" name="address" placeholder="গ্রাম, ইউনিয়ন, উপজেলা, জেলা" required style="min-height:65px;">{{ old('address') }}</textarea>
                    @error('address')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </label>
            </div>
        </section>

        <section class="card section-gap">
            <div class="card-title">
                <div class="title-left">
                    <span class="iconbox">🛰</span>
                    <div>
                        <h2>স্যাটেলাইট মানচিত্র ও বহুভুজ সীমানা নির্ধারণ (Field Boundary)</h2>
                        <div class="sub">স্যাটেলাইট ভিউতে জুম করুন এবং মাঠের চারপাশে ক্লিক করে সীমানা আঁকুন</div>
                    </div>
                </div>
            </div>

            <div class="map-wrapper">
                <div class="map-bar">
                    <div class="map-tools">
                        <button type="button" class="map-tool-btn" id="btn-draw-polygon">
                            <span>✏️</span> <b>সীমানা আঁকুন</b>
                        </button>
                        <button type="button" class="map-tool-btn danger" id="btn-delete-polygon">
                            <span>🗑️</span> মুছুন
                        </button>
                        <button type="button" class="map-tool-btn" id="btn-toggle-style">
                            <span>🗺️</span> রাস্তাঘাট ভিউ
                        </button>
                        <button type="button" class="map-tool-btn" id="btn-my-location">
                            <span>📍</span> আমার অবস্থান
                        </button>
                    </div>

                    <div class="map-search-wrap">
                        <input type="text" id="map-search-input" class="map-search-input" placeholder="🔍 মাঠ বা এলাকা খুঁজুন (যেমন: পাবনা, ঈশ্বরদী)...">
                        <div id="map-search-dropdown" class="map-search-dropdown"></div>
                    </div>

                    <div class="live-area-badge" id="area-badge" style="display:none;">
                        <span>📐 ক্ষেত্রফল:</span>
                        <span id="badge-area-text">০ একর</span>
                    </div>
                </div>

                <div id="field-map"></div>
            </div>

            <div class="form-grid section-gap">
                <label class="field">
                    <span class="label">কেন্দ্র অক্ষাংশ (Latitude)</span>
                    <input class="input" type="number" step="0.0000001" name="latitude" id="latitude_input" value="{{ old('latitude', '24.7471000') }}" placeholder="যেমন: 24.7471000">
                    @error('latitude')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </label>

                <label class="field">
                    <span class="label">কেন্দ্র দ্রাঘিমাংশ (Longitude)</span>
                    <input class="input" type="number" step="0.0000001" name="longitude" id="longitude_input" value="{{ old('longitude', '90.4203000') }}" placeholder="যেমন: 90.4203000">
                    @error('longitude')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </label>

                <label class="field">
                    <span class="label">বাফার রেডিয়াস (মিটার) <span class="req">*</span></span>
                    <input class="input" type="number" name="geofence_radius_meters" value="{{ old('geofence_radius_meters', 20) }}" min="5" max="1000" required>
                    <span class="help">ডিফল্ট বাফার: ২০ মিটার। বহুভুজ সীমানার বাইরে অতিরিক্ত গ্রহণযোগ্য দূরত্ব।</span>
                    @error('geofence_radius_meters')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </label>

                <label class="field">
                    <span class="label">অবস্থা <span class="req">*</span></span>
                    <select class="select" name="status" required>
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>সক্রিয় (Active)</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>নিষ্ক্রিয় (Inactive)</option>
                    </select>
                </label>
            </div>

            <div class="notice section-gap">
                💡 <b>সীমানা আঁকার নিয়মাবলী:</b> স্যাটেলাইট মানচিত্রে কাঙ্ক্ষিত স্থানে 'সীমানা আঁকুন' চাপুন। মাঠের প্রতিটি কোনায় ক্লিক করে শেষ বিন্দুতে প্রথম বিন্দুর উপর ডাবল ক্লিক করুন। স্বয়ংক্রিয়ভাবে PostGIS পলিগন জেনারেট হবে।
            </div>
        </section>

        @if($farmers->isNotEmpty())
            <section class="card section-gap">
                <div class="card-title">
                    <div class="title-left">
                        <span class="iconbox">♟</span>
                        <div>
                            <h2>অতিরিক্ত কৃষক বরাদ্দ (ঐচ্ছিক)</h2>
                            <div class="sub">এই লোকেশনে একাধিক কৃষক কাজ করার অনুমতি দিতে পারেন</div>
                        </div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:10px;">
                    @foreach($farmers as $f)
                        <label style="display:flex;align-items:center;gap:8px;background:#fbfdfc;border:1px solid var(--line);border-radius:9px;padding:8px 12px;cursor:pointer;">
                            <input type="checkbox" name="farmer_ids[]" value="{{ $f->id }}" {{ in_array($f->id, old('farmer_ids', [])) ? 'checked' : '' }}>
                            <div>
                                <b style="font-size:13px;">{{ $f->full_name }}</b>
                                <br><small style="color:var(--muted)">{{ $f->farmer_code }} • {{ $f->primary_activity }}</small>
                            </div>
                        </label>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="sticky-actions">
            <a href="{{ route('admin.locations.index') }}" class="btn">বাতিল</a>
            <button type="submit" class="btn primary">ফার্ম ও সীমানা সংরক্ষণ করুন</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>
    <script>
        // Set Mapbox alias for complete plugin compatibility without DRM token checks
        window.mapboxgl = window.maplibregl;
    </script>
    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-draw/v1.4.3/mapbox-gl-draw.js"></script>
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

            // High-precision OpenStreetMap Streets
            const streetsStyle = {
                version: 8,
                sources: {
                    'osm-tiles': {
                        type: 'raster',
                        tiles: [
                            'https://tile.openstreetmap.org/{z}/{x}/{y}.png'
                        ],
                        tileSize: 256,
                        attribution: '&copy; OpenStreetMap contributors'
                    }
                },
                layers: [
                    {
                        id: 'osm-tiles-layer',
                        type: 'raster',
                        source: 'osm-tiles',
                        minzoom: 0,
                        maxzoom: 19
                    }
                ]
            };

            let isSatellite = true;
            let currentStyle = satelliteStyle;

            const initialLat = parseFloat(document.getElementById('latitude_input').value) || 24.7471;
            const initialLng = parseFloat(document.getElementById('longitude_input').value) || 90.4203;

            const map = new maplibregl.Map({
                container: 'field-map',
                style: currentStyle,
                center: [initialLng, initialLat],
                zoom: 15
            });

            // Prevent blank layout collapse
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

            // Draw plugin
            const draw = new MapboxDraw({
                displayControlsDefault: false,
                controls: {
                    polygon: false,
                    trash: false
                },
                defaultMode: 'simple_select',
                styles: [
                    {
                        'id': 'gl-draw-polygon-fill-active',
                        'type': 'fill',
                        'filter': ['all', ['==', '$type', 'Polygon'], ['==', 'active', 'true']],
                        'paint': {
                            'fill-color': '#00e676',
                            'fill-outline-color': '#00e676',
                            'fill-opacity': 0.35
                        }
                    },
                    {
                        'id': 'gl-draw-polygon-fill-inactive',
                        'type': 'fill',
                        'filter': ['all', ['==', '$type', 'Polygon'], ['==', 'active', 'false']],
                        'paint': {
                            'fill-color': '#2e7d32',
                            'fill-outline-color': '#2e7d32',
                            'fill-opacity': 0.3
                        }
                    },
                    {
                        'id': 'gl-draw-polygon-stroke-active',
                        'type': 'line',
                        'filter': ['all', ['==', '$type', 'Polygon']],
                        'paint': {
                            'line-color': '#00e676',
                            'line-width': 3
                        }
                    },
                    {
                        'id': 'gl-draw-polygon-and-line-vertex-active',
                        'type': 'circle',
                        'filter': ['all', ['==', 'meta', 'vertex'], ['==', '$type', 'Point']],
                        'paint': {
                            'circle-radius': 6,
                            'circle-color': '#ffffff',
                            'circle-stroke-width': 2,
                            'circle-stroke-color': '#00e676'
                        }
                    }
                ]
            });
            map.addControl(draw);

            let centerMarker = null;

            function updateMarker(lng, lat) {
                if (!centerMarker) {
                    centerMarker = new maplibregl.Marker({ color: '#ff5722', draggable: true })
                        .setLngLat([lng, lat])
                        .addTo(map);

                    centerMarker.on('dragend', function() {
                        const pos = centerMarker.getLngLat();
                        document.getElementById('latitude_input').value = pos.lat.toFixed(7);
                        document.getElementById('longitude_input').value = pos.lng.toFixed(7);
                    });
                } else {
                    centerMarker.setLngLat([lng, lat]);
                }
            }

            updateMarker(initialLng, initialLat);

            // Calculate Area via Turf.js and update UI
            function updateAreaAndCentroid() {
                const data = draw.getAll();
                const boundaryInput = document.getElementById('boundary_input');
                const areaBadge = document.getElementById('area-badge');
                const badgeText = document.getElementById('badge-area-text');
                const areaInput = document.getElementById('area_input');
                const unitSelect = document.getElementById('area_unit_select');

                if (data.features.length > 0) {
                    const feature = data.features[0];
                    boundaryInput.value = JSON.stringify(feature.geometry);

                    const sqMeters = turf.area(feature);
                    const selectedUnit = unitSelect.value;
                    let displayArea = 0;

                    if (selectedUnit === 'একর') {
                        displayArea = sqMeters * 0.000247105;
                    } else if (selectedUnit === 'শতাংশ') {
                        displayArea = sqMeters * 0.0247105;
                    } else if (selectedUnit === 'বিঘা') {
                        displayArea = sqMeters * 0.00074749;
                    } else if (selectedUnit === 'হেক্টর') {
                        displayArea = sqMeters / 10000;
                    } else {
                        displayArea = sqMeters;
                    }

                    const roundedArea = displayArea.toFixed(2);
                    areaInput.value = roundedArea;

                    areaBadge.style.display = 'inline-flex';
                    badgeText.innerHTML = `${roundedArea} ${selectedUnit} <small style="opacity:0.8">(${Math.round(sqMeters)} ব.মি.)</small>`;

                    const centroid = turf.centroid(feature);
                    const cLng = centroid.geometry.coordinates[0];
                    const cLat = centroid.geometry.coordinates[1];

                    document.getElementById('latitude_input').value = cLat.toFixed(7);
                    document.getElementById('longitude_input').value = cLng.toFixed(7);
                    updateMarker(cLng, cLat);
                } else {
                    boundaryInput.value = '';
                    areaBadge.style.display = 'none';
                }
            }

            map.on('draw.create', updateAreaAndCentroid);
            map.on('draw.update', updateAreaAndCentroid);
            map.on('draw.delete', updateAreaAndCentroid);

            document.getElementById('area_unit_select').addEventListener('change', updateAreaAndCentroid);

            // Draw button
            const btnDraw = document.getElementById('btn-draw-polygon');
            btnDraw.addEventListener('click', function () {
                draw.deleteAll();
                draw.changeMode('draw_polygon');
            });

            // Delete button
            const btnDelete = document.getElementById('btn-delete-polygon');
            btnDelete.addEventListener('click', function () {
                draw.deleteAll();
                updateAreaAndCentroid();
            });

            // Toggle Style: Satellite vs Streets
            const btnToggleStyle = document.getElementById('btn-toggle-style');
            btnToggleStyle.addEventListener('click', function () {
                const currentData = draw.getAll();
                isSatellite = !isSatellite;
                currentStyle = isSatellite ? satelliteStyle : streetsStyle;

                btnToggleStyle.innerHTML = isSatellite ? '<span>🗺️</span> রাস্তাঘাট ভিউ' : '<span>🛰️</span> স্যাটেলাইট ভিউ';

                map.setStyle(currentStyle);
                map.once('style.load', function () {
                    if (currentData.features.length > 0) {
                        draw.add(currentData);
                    }
                    map.resize();
                });
            });

            // My location
            const btnMyLoc = document.getElementById('btn-my-location');
            btnMyLoc.addEventListener('click', function () {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function (position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        map.flyTo({ center: [lng, lat], zoom: 17 });
                        updateMarker(lng, lat);
                        document.getElementById('latitude_input').value = lat.toFixed(7);
                        document.getElementById('longitude_input').value = lng.toFixed(7);
                    }, function (err) {
                        alert('জিপিএস অবস্থান চিহ্নিত করা যায়নি: ' + err.message);
                    });
                }
            });

            // Search (Bangladesh OSM Nominatim)
            const searchInput = document.getElementById('map-search-input');
            const searchDropdown = document.getElementById('map-search-dropdown');
            let searchDebounce = null;

            searchInput.addEventListener('input', function () {
                clearTimeout(searchDebounce);
                const query = this.value.trim();
                if (query.length < 2) {
                    searchDropdown.style.display = 'none';
                    return;
                }

                searchDebounce = setTimeout(async function () {
                    try {
                        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=bd&limit=5`);
                        const results = await response.json();
                        if (results && results.length > 0) {
                            searchDropdown.innerHTML = '';
                            results.forEach(item => {
                                const el = document.createElement('div');
                                el.className = 'map-search-item';
                                const mainTitle = item.display_name.split(',')[0];
                                el.innerHTML = `<b>${mainTitle}</b><br><span style="color:#777;font-size:11px;">${item.display_name}</span>`;
                                el.addEventListener('click', () => {
                                    const itemLat = parseFloat(item.lat);
                                    const itemLon = parseFloat(item.lon);
                                    map.flyTo({ center: [itemLon, itemLat], zoom: 16 });
                                    updateMarker(itemLon, itemLat);
                                    document.getElementById('latitude_input').value = itemLat.toFixed(7);
                                    document.getElementById('longitude_input').value = itemLon.toFixed(7);
                                    searchDropdown.style.display = 'none';
                                    searchInput.value = mainTitle;
                                });
                                searchDropdown.appendChild(el);
                            });
                            searchDropdown.style.display = 'block';
                        } else {
                            searchDropdown.style.display = 'none';
                        }
                    } catch (e) {
                        console.error('Location search failed', e);
                    }
                }, 350);
            });

            document.addEventListener('click', function (e) {
                if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                    searchDropdown.style.display = 'none';
                }
            });

            // Load existing boundary if reloaded with old input
            const oldBoundary = document.getElementById('boundary_input').value;
            if (oldBoundary) {
                try {
                    const parsed = JSON.parse(oldBoundary);
                    function addOldBoundary() {
                        draw.add({
                            type: 'Feature',
                            properties: {},
                            geometry: parsed
                        });
                        updateAreaAndCentroid();
                        const bbox = turf.bbox({ type: 'Feature', geometry: parsed });
                        map.fitBounds(bbox, { padding: 40 });
                    }
                    if (map.loaded()) {
                        addOldBoundary();
                    } else {
                        map.on('load', addOldBoundary);
                    }
                } catch (e) {
                    console.error('Invalid old boundary GeoJSON', e);
                }
            }
        });
    </script>
@endpush
