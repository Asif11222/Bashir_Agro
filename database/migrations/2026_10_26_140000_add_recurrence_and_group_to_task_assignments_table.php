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
        Schema::table('task_assignments', function (Blueprint $table) {
            $table->string('recurring_group_id', 64)->nullable()->after('id')->index();
            $table->string('recurrence_type', 30)->default('once')->after('recurring_group_id');
            $table->unsignedInteger('recurrence_seq')->nullable()->after('recurrence_type');
            $table->unsignedInteger('recurrence_total_count')->nullable()->after('recurrence_seq');
            $table->boolean('is_recurring')->default(false)->after('recurrence_total_count')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_assignments', function (Blueprint $table) {
            $table->dropIndex(['recurring_group_id']);
            $table->dropIndex(['is_recurring']);
            $table->dropColumn([
                'recurring_group_id',
                'recurrence_type',
                'recurrence_seq',
                'recurrence_total_count',
                'is_recurring',
            ]);
        });
    }
};
