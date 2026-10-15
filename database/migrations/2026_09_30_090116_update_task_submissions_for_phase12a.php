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
        // Check if PostGIS extension is available before attempting to enable it
        if (DB::connection()->getDriverName() === 'pgsql') {
            $isAvailable = DB::selectOne("SELECT count(*) as count FROM pg_available_extensions WHERE name = 'postgis'");
            if ($isAvailable && (int) $isAvailable->count > 0) {
                DB::statement('CREATE EXTENSION IF NOT EXISTS postgis;');
            }
        }

        Schema::table('task_submissions', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('farmer_note');
            $table->jsonb('photos')->nullable()->after('notes');
            $table->decimal('accuracy', 8, 2)->nullable()->after('longitude');
            $table->string('status', 30)->default('submitted')->after('accuracy');
            $table->string('verification_status', 30)->default('pending')->after('status');

            $table->index('status', 'task_submissions_status_idx');
            $table->index('verification_status', 'task_submissions_verification_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_submissions', function (Blueprint $table) {
            $table->dropIndex('task_submissions_status_idx');
            $table->dropIndex('task_submissions_verification_status_idx');
            $table->dropColumn(['notes', 'photos', 'accuracy', 'status', 'verification_status']);
        });
    }
};
