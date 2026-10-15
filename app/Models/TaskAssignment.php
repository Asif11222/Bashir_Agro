<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'task_template_id',
        'farmer_id',
        'farm_location_id',
        'scheduled_date',
        'start_time',
        'end_time',
        'priority',
        'notes',
        'status',
        'assigned_by',
        'assigned_at',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'assigned_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function taskTemplate(): BelongsTo
    {
        return $this->belongsTo(TaskTemplate::class);
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    /**
     * Primary location relationship specified in Phase 5.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(FarmLocation::class, 'farm_location_id');
    }

    /**
     * Alias for backward compatibility.
     */
    public function farmLocation(): BelongsTo
    {
        return $this->belongsTo(FarmLocation::class, 'farm_location_id');
    }

    /**
     * Primary assigner relationship specified in Phase 5.
     */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Alias for backward compatibility.
     */
    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function submission(): HasOne
    {
        return $this->hasOne(TaskSubmission::class);
    }

    /**
     * Priority label in Bengali.
     */
    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'জরুরি',
            'important' => 'গুরুত্বপূর্ণ',
            default => 'স্বাভাবিক',
        };
    }

    /**
     * Status label in Bengali.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'assigned', 'pending' => 'বরাদ্দকৃত',
            'in_progress' => 'চলমান',
            'completed' => 'সম্পন্ন',
            'cancelled' => 'বাতিল',
            'late' => 'দেরি',
            default => $this->status,
        };
    }

    /**
     * Priority badge CSS class.
     */
    public function getPriorityBadgeClassAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'red',
            'important' => 'blue',
            default => '',
        };
    }

    /**
     * Status badge CSS class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'completed' => '',
            'in_progress' => 'blue',
            'cancelled', 'late' => 'red',
            'assigned', 'pending' => 'blue',
            default => '',
        };
    }
}
