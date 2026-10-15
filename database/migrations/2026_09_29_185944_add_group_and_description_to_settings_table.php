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
        Schema::table('settings', function (Blueprint $table) {
            $table->string('group', 50)->default('general')->after('type');
            $table->text('description')->nullable()->after('group');
            $table->boolean('is_editable')->default(true)->after('description');
            $table->foreignId('created_by')->nullable()->after('is_editable')->constrained('users')->nullOnDelete();

            $table->index('group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex(['group']);
            $table->dropColumn(['group', 'description', 'is_editable', 'created_by']);
        });
    }
};
