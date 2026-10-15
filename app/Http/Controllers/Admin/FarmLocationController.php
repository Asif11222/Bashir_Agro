<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Models\ActivityLog;
use App\Models\Farmer;
use App\Models\FarmLocation;
use App\Services\SpatialValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FarmLocationController extends Controller
{
    /**
     * Display a listing of farm locations with search, filters, and pagination.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $totalLocationsCount = FarmLocation::count();
        $activeLocationsCount = FarmLocation::where('status', 'active')->count();
        $inactiveLocationsCount = FarmLocation::where('status', 'inactive')->count();

        $locationsQuery = FarmLocation::withCount('farmers');

        if ($search) {
            $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $locationsQuery->where(function ($query) use ($search, $likeOperator) {
                $query->where('name', $likeOperator, "%{$search}%")
                    ->orWhere('location_code', $likeOperator, "%{$search}%")
                    ->orWhere('type', $likeOperator, "%{$search}%")
                    ->orWhere('address', $likeOperator, "%{$search}%");
            });
        }

        if ($status && in_array($status, ['active', 'inactive'])) {
            $locationsQuery->where('status', $status);
        }

        $locations = $locationsQuery->latest('id')->paginate(20)->withQueryString();

        return view('locations.index', [
            'activeTab' => 'locations',
            'title' => 'ফার্ম লোকেশন',
            'locations' => $locations,
            'search' => $search,
            'status' => $status,
            'totalLocationsCount' => $totalLocationsCount,
            'activeLocationsCount' => $activeLocationsCount,
            'inactiveLocationsCount' => $inactiveLocationsCount,
        ]);
    }

    /**
     * Show the form for creating a new farm location.
     */
    public function create(): View
    {
        $farmers = Farmer::where('status', 'active')->orderBy('full_name')->get();

        return view('locations.create', [
            'activeTab' => 'locations',
            'title' => 'নতুন লোকেশন যোগ করুন',
            'farmers' => $farmers,
        ]);
    }

    public function __construct(
        protected SpatialValidationService $spatialService
    ) {}

    /**
     * Store a newly created farm location in storage within a transaction.
     */
    public function store(StoreLocationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $geoJson = null;
        if (! empty($validated['boundary'])) {
            $boundaryData = is_string($validated['boundary']) ? json_decode($validated['boundary'], true) : $validated['boundary'];
            if (isset($boundaryData['type']) && strtolower($boundaryData['type']) === 'feature' && isset($boundaryData['geometry'])) {
                $boundaryData = $boundaryData['geometry'];
            }
            if (is_array($boundaryData) && isset($boundaryData['type']) && strtolower($boundaryData['type']) === 'polygon') {
                $geoJson = $boundaryData;
            }
        }

        $location = DB::transaction(function () use ($validated, $geoJson) {
            $locationCode = FarmLocation::generateNextLocationCode();

            $location = FarmLocation::create([
                'location_code' => $locationCode,
                'farmer_id' => $validated['farmer_id'] ?? null,
                'name' => $validated['name'],
                'type' => $validated['type'],
                'address' => $validated['address'],
                'boundary_geojson' => $geoJson,
                'area' => $validated['area'] ?? null,
                'area_unit' => $validated['area_unit'] ?? 'একর',
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'geofence_radius_meters' => $validated['geofence_radius_meters'] ?? 20,
                'status' => $validated['status'] ?? 'active',
                'created_by' => auth()->id(),
            ]);

            // Sync PostGIS geometry if active
            if ($geoJson && $this->spatialService->isPostGisAvailable()) {
                try {
                    DB::statement(
                        'UPDATE farm_locations SET boundary = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326) WHERE id = ?',
                        [json_encode($geoJson), $location->id]
                    );
                } catch (\Throwable $e) {
                    // Ignore PostGIS statement failures gracefully
                }
            }

            $farmerIds = $validated['farmer_ids'] ?? [];
            if (! empty($validated['farmer_id']) && ! in_array($validated['farmer_id'], $farmerIds)) {
                $farmerIds[] = $validated['farmer_id'];
            }

            if (! empty($farmerIds)) {
                $location->farmers()->sync($farmerIds);
            }

            ActivityLog::record(
                action: 'location.created',
                description: "নতুন লোকেশন তৈরি করা হয়েছে: {$location->name} ({$location->location_code})",
                subject: $location,
                newValues: $location->toArray()
            );

            return $location;
        });

        return redirect()->route('admin.locations.index')
            ->with('success', "লোকেশন {$location->name} ({$location->location_code}) সফলভাবে সংরক্ষিত হয়েছে।");
    }

    /**
     * Display the specified farm location.
     */
    public function show(FarmLocation $location): View
    {
        $location->load([
            'farmers',
            'taskAssignments' => function ($query) {
                $query->with(['farmer', 'taskTemplate'])->latest()->limit(10);
            },
        ]);

        $availableFarmers = Farmer::where('status', 'active')
            ->whereNotIn('id', $location->farmers->pluck('id'))
            ->orderBy('full_name')
            ->get();

        return view('locations.show', [
            'activeTab' => 'locations',
            'title' => "লোকেশন বিবরণ — {$location->name}",
            'location' => $location,
            'availableFarmers' => $availableFarmers,
        ]);
    }

    /**
     * Show the form for editing the specified farm location.
     */
    public function edit(FarmLocation $location): View
    {
        $location->load('farmers');
        $farmers = Farmer::where('status', 'active')->orderBy('full_name')->get();

        return view('locations.edit', [
            'activeTab' => 'locations',
            'title' => "লোকেশন সম্পাদনা — {$location->name}",
            'location' => $location,
            'farmers' => $farmers,
        ]);
    }

    /**
     * Update the specified farm location in storage within a transaction.
     */
    public function update(UpdateLocationRequest $request, FarmLocation $location): RedirectResponse
    {
        $validated = $request->validated();
        $oldValues = $location->toArray();

        $geoJson = null;
        if (isset($validated['boundary'])) {
            $boundaryData = is_string($validated['boundary']) ? json_decode($validated['boundary'], true) : $validated['boundary'];
            if (isset($boundaryData['type']) && strtolower($boundaryData['type']) === 'feature' && isset($boundaryData['geometry'])) {
                $boundaryData = $boundaryData['geometry'];
            }
            if (is_array($boundaryData) && isset($boundaryData['type']) && strtolower($boundaryData['type']) === 'polygon') {
                $geoJson = $boundaryData;
            }
        }

        DB::transaction(function () use ($validated, $location, $oldValues, $geoJson) {
            $updateData = [
                'name' => $validated['name'],
                'type' => $validated['type'],
                'address' => $validated['address'],
                'area' => $validated['area'] ?? null,
                'area_unit' => $validated['area_unit'] ?? 'একর',
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'geofence_radius_meters' => $validated['geofence_radius_meters'],
                'status' => $validated['status'],
            ];

            if (array_key_exists('farmer_id', $validated)) {
                $updateData['farmer_id'] = $validated['farmer_id'];
            }

            if ($geoJson !== null) {
                $updateData['boundary_geojson'] = $geoJson;
            }

            $location->update($updateData);

            // Sync PostGIS geometry if active
            if ($geoJson && $this->spatialService->isPostGisAvailable()) {
                try {
                    DB::statement(
                        'UPDATE farm_locations SET boundary = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326) WHERE id = ?',
                        [json_encode($geoJson), $location->id]
                    );
                } catch (\Throwable $e) {
                    // Ignore PostGIS statement failures gracefully
                }
            }

            if (isset($validated['farmer_ids'])) {
                $farmerIds = $validated['farmer_ids'];
                if (! empty($validated['farmer_id']) && ! in_array($validated['farmer_id'], $farmerIds)) {
                    $farmerIds[] = $validated['farmer_id'];
                }
                $location->farmers()->sync($farmerIds);
            } elseif (! empty($validated['farmer_id'])) {
                $location->farmers()->syncWithoutDetaching([$validated['farmer_id']]);
            }

            ActivityLog::record(
                action: 'location.updated',
                description: "লোকেশন তথ্য হালনাগাদ করা হয়েছে: {$location->name} ({$location->location_code})",
                subject: $location,
                oldValues: $oldValues,
                newValues: $location->toArray()
            );
        });

        return redirect()->route('admin.locations.index')
            ->with('success', "লোকেশন {$location->name}-এর তথ্য সফলভাবে আপডেট করা হয়েছে।");
    }

    /**
     * Remove the specified farm location from storage (Soft Delete).
     */
    public function destroy(FarmLocation $location): RedirectResponse
    {
        $locationName = $location->name;
        $locationCode = $location->location_code;

        DB::transaction(function () use ($location, $locationName, $locationCode) {
            $oldValues = $location->toArray();
            $location->delete();

            ActivityLog::record(
                action: 'location.deleted',
                description: "লোকেশন মুছে ফেলা হয়েছে (Soft Delete): {$locationName} ({$locationCode})",
                subject: $location,
                oldValues: $oldValues
            );
        });

        return redirect()->route('admin.locations.index')
            ->with('success', "লোকেশন {$locationName} ({$locationCode}) সফলভাবে মুছে ফেলা হয়েছে।");
    }

    /**
     * Attach a farmer to the location.
     */
    public function attachFarmer(Request $request, FarmLocation $location): RedirectResponse
    {
        $validated = $request->validate([
            'farmer_id' => ['required', 'exists:farmers,id'],
        ]);

        $farmer = Farmer::findOrFail($validated['farmer_id']);

        $location->farmers()->syncWithoutDetaching([$farmer->id]);

        ActivityLog::record(
            action: 'location.assigned',
            description: "কৃষক {$farmer->full_name} ({$farmer->farmer_code}) লোকেশন {$location->name} এ বরাদ্দ করা হয়েছে",
            subject: $location
        );

        return back()->with('success', "কৃষক {$farmer->full_name} সফলভাবে সংযুক্ত করা হয়েছে।");
    }

    /**
     * Detach a farmer from the location.
     */
    public function detachFarmer(FarmLocation $location, Farmer $farmer): RedirectResponse
    {
        $location->farmers()->detach($farmer->id);

        ActivityLog::record(
            action: 'location.unassigned',
            description: "কৃষক {$farmer->full_name} ({$farmer->farmer_code})-এর লোকেশন {$location->name} থেকে বরাদ্দ প্রত্যাহার করা হয়েছে",
            subject: $location
        );

        return back()->with('success', "কৃষক {$farmer->full_name}-এর বরাদ্দ প্রত্যাহার করা হয়েছে।");
    }
}
