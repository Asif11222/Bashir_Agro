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
        Schema::create('task_templates', function (Blueprint $table) {
            $table->id();
            $table->string('task_code', 30)->unique();
            $table->string('name', 200);
            $table->string('category', 100);
            $table->text('description')->nullable();
            $table->text('instructions');
            $table->integer('required_photo_count')->default(0);
            $table->boolean('require_realtime_photo')->default(true);
            $table->boolean('allow_gallery_upload')->default(false);
            $table->boolean('require_geofence')->default(true);
            $table->string('recurrence_type', 30)->default('once');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_templates');
    }
};
