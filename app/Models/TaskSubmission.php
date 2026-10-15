<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_assignment_id',
        'farmer_id',
        'submitted_at',
        'latitude',
        'longitude',
        'accuracy',
        'geofence_passed',
        'distance_meters',
        'is_late',
        'status',
        'verification_status',
        'validation_status',
        'notes',
        'farmer_note',
        'photos',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'accuracy' => 'decimal:2',
            'distance_meters' => 'decimal:2',
            'geofence_passed' => 'boolean',
            'is_late' => 'boolean',
            'photos' => 'array',
        ];
    }

    public function taskAssignment(): BelongsTo
    {
        return $this->belongsTo(TaskAssignment::class);
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function evidencePhotos(): HasMany
    {
        return $this->hasMany(EvidencePhoto::class);
    }
}
