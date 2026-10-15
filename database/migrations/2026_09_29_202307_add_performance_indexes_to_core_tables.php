<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            $table->index('status', 'farmers_status_idx');
        });

        Schema::table('farm_locations', function (Blueprint $table) {
            $table->index('status', 'farm_locations_status_idx');
        });

        Schema::table('task_templates', function (Blueprint $table) {
            $table->index('status', 'task_templates_status_idx');
        });

        Schema::table('task_assignments', function (Blueprint $table) {
            $table->index('scheduled_date', 'task_assignments_scheduled_date_idx');
            $table->index('status', 'task_assignments_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            $table->dropIndex('farmers_status_idx');
        });

        Schema::table('farm_locations', function (Blueprint $table) {
            $table->dropIndex('farm_locations_status_idx');
        });

        Schema::table('task_templates', function (Blueprint $table) {
            $table->dropIndex('task_templates_status_idx');
        });

        Schema::table('task_assignments', function (Blueprint $table) {
            $table->dropIndex('task_assignments_scheduled_date_idx');
            $table->dropIndex('task_assignments_status_idx');
        });
    }
};
