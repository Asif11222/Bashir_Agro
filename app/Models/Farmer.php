<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Farmer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'farmer_code',
        'full_name',
        'phone',
        'nid',
        'date_of_birth',
        'gender',
        'address',
        'primary_activity',
        'experience_years',
        'profile_photo_path',
        'app_access_enabled',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'app_access_enabled' => 'boolean',
            'experience_years' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function emergencyContact(): HasOne
    {
        return $this->hasOne(FarmerEmergencyContact::class);
    }

    public function farmLocations(): BelongsToMany
    {
        return $this->belongsToMany(FarmLocation::class, 'farmer_location')->withTimestamps();
    }

    public function farms(): HasMany
    {
        return $this->hasMany(FarmLocation::class, 'farmer_id');
    }

    public function taskAssignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    /**
     * Consistent task-progress counters across mobile and web admin.
     * Cancelled assignments do not count as work due.
     */
    public function scopeWithTaskProgress(Builder $query, ?string $date = null, ?int $locationId = null): Builder
    {
        $scope = static function (Builder $assignments) use ($date, $locationId): void {
            $assignments->where('status', '!=', 'cancelled');
            if ($date !== null) {
                $assignments->where('scheduled_date', $date);
            }
            if ($locationId !== null) {
                $assignments->where('farm_location_id', $locationId);
            }
        };

        return $query->withCount([
            'taskAssignments as total_tasks' => $scope,
            'taskAssignments as completed_tasks' => static function (Builder $assignments) use ($scope): void {
                $scope($assignments);
                $assignments->where('status', 'completed');
            },
        ]);
    }

    public function taskProgressPercent(): int
    {
        $total = (int) ($this->total_tasks ?? 0);

        return $total > 0 ? (int) round(100 * (int) ($this->completed_tasks ?? 0) / $total) : 0;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate the next sequential authoritative farmer code.
     */
    public static function generateNextFarmerCode(): string
    {
        $maxNum = 0;
        $allCodes = static::withTrashed()->pluck('farmer_code');
        foreach ($allCodes as $code) {
            if (preg_match('/F-(\d+)/i', $code, $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        $nextNum = $maxNum + 1;
        $newCode = sprintf('F-%06d', $nextNum);

        while (static::withTrashed()->where('farmer_code', $newCode)->exists()) {
            $nextNum++;
            $newCode = sprintf('F-%06d', $nextNum);
        }

        return $newCode;
    }
}
