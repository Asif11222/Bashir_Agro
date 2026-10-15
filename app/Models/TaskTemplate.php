<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'task_code',
        'name',
        'category',
        'description',
        'instructions',
        'required_photo_count',
        'require_realtime_photo',
        'allow_gallery_upload',
        'require_geofence',
        'recurrence_type',
        'status',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'required_photo_count' => 'integer',
            'require_realtime_photo' => 'boolean',
            'allow_gallery_upload' => 'boolean',
            'require_geofence' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function taskAssignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate the next sequential authoritative task template code.
     */
    public static function generateNextTaskCode(): string
    {
        $maxNum = 0;
        $allCodes = static::withTrashed()->pluck('task_code');
        foreach ($allCodes as $code) {
            if (preg_match('/(?:TASK|TK)-(\d+)/i', $code, $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        $nextNum = $maxNum + 1;
        $newCode = sprintf('TASK-%06d', $nextNum);

        while (static::withTrashed()->where('task_code', $newCode)->exists()) {
            $nextNum++;
            $newCode = sprintf('TASK-%06d', $nextNum);
        }

        return $newCode;
    }
}
