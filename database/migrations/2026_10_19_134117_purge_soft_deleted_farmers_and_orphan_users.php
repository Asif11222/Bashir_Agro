<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Find all soft-deleted farmers
        $trashedFarmers = DB::table('farmers')->whereNotNull('deleted_at')->get();

        foreach ($trashedFarmers as $tf) {
            // Delete linked user and their tokens/notifications
            if ($tf->user_id) {
                DB::table('personal_access_tokens')
                    ->where('tokenable_type', 'App\\Models\\User')
                    ->where('tokenable_id', $tf->user_id)
                    ->delete();

                DB::table('notifications')
                    ->where('notifiable_type', 'App\\Models\\User')
                    ->where('notifiable_id', $tf->user_id)
                    ->delete();

                DB::table('users')
                    ->where('id', $tf->user_id)
                    ->where('role', 'farmer')
                    ->delete();
            }

            // Also delete by phone if user exists with role 'farmer'
            if ($tf->phone) {
                DB::table('users')
                    ->where('phone', $tf->phone)
                    ->where('role', 'farmer')
                    ->delete();
            }
        }

        $trashedFarmerIds = $trashedFarmers->pluck('id')->toArray();
        if (! empty($trashedFarmerIds)) {
            DB::table('farmer_emergency_contacts')->whereIn('farmer_id', $trashedFarmerIds)->delete();
            DB::table('farmer_location')->whereIn('farmer_id', $trashedFarmerIds)->delete();
            DB::table('task_assignments')->whereIn('farmer_id', $trashedFarmerIds)->delete();
            DB::table('task_submissions')->whereIn('farmer_id', $trashedFarmerIds)->delete();
            DB::table('farm_locations')->whereIn('farmer_id', $trashedFarmerIds)->update(['farmer_id' => null]);
            DB::table('farmers')->whereIn('id', $trashedFarmerIds)->delete();
        }

        // 2. Clean up any remaining orphan users with role 'farmer' without an active farmer profile
        $activeUserIds = DB::table('farmers')->whereNull('deleted_at')->whereNotNull('user_id')->pluck('user_id')->toArray();
        DB::table('users')
            ->where('role', 'farmer')
            ->whereNotIn('id', $activeUserIds)
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Purged records cannot be restored
    }
};
