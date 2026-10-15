<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        $hasPostGis = false;

        if ($driver === 'pgsql') {
            try {
                $isAvailable = DB::selectOne("SELECT count(*) as count FROM pg_available_extensions WHERE name = 'postgis'");
                if ($isAvailable && (int) $isAvailable->count > 0) {
                    DB::statement('CREATE EXTENSION IF NOT EXISTS postgis;');
                    $installed = DB::selectOne("SELECT count(*) as count FROM pg_extension WHERE extname = 'postgis'");
                    $hasPostGis = $installed && (int) $installed->count > 0;
                }
            } catch (Throwable $e) {
                $hasPostGis = false;
            }
        }

        Schema::table('farm_locations', function (Blueprint $table) use ($driver, $hasPostGis) {
            if (! Schema::hasColumn('farm_locations', 'farmer_id')) {
                $table->foreignId('farmer_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('farmers')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('farm_locations', 'boundary_geojson')) {
                if ($driver === 'pgsql') {
                    $table->jsonb('boundary_geojson')->nullable()->after('address');
                } else {
                    $table->json('boundary_geojson')->nullable()->after('address');
                }
            }

            if (! $hasPostGis && ! Schema::hasColumn('farm_locations', 'boundary')) {
                $table->text('boundary')->nullable()->after('boundary_geojson');
            }
        });

        if ($hasPostGis) {
            try {
                DB::statement('ALTER TABLE farm_locations ADD COLUMN IF NOT EXISTS boundary geometry(Polygon, 4326);');
                DB::statement('CREATE INDEX IF NOT EXISTS farm_locations_boundary_gist_idx ON farm_locations USING GIST (boundary);');
            } catch (Throwable $e) {
                // If PostGIS statement fails, ensure fallback column exists
                if (! Schema::hasColumn('farm_locations', 'boundary')) {
                    Schema::table('farm_locations', function (Blueprint $table) {
                        $table->text('boundary')->nullable()->after('boundary_geojson');
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('farm_locations', function (Blueprint $table) {
            if (Schema::hasColumn('farm_locations', 'farmer_id')) {
                $table->dropConstrainedForeignId('farmer_id');
            }

            $columnsToDrop = [];
            if (Schema::hasColumn('farm_locations', 'boundary_geojson')) {
                $columnsToDrop[] = 'boundary_geojson';
            }
            if (Schema::hasColumn('farm_locations', 'boundary')) {
                $columnsToDrop[] = 'boundary';
            }

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
