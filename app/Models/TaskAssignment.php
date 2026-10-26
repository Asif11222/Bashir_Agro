<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class TaskAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'recurring_group_id',
        'recurrence_type',
        'recurrence_seq',
        'recurrence_total_count',
        'is_recurring',
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
            'is_recurring' => 'boolean',
            'recurrence_seq' => 'integer',
            'recurrence_total_count' => 'integer',
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

    /**
     * Format a time string (e.g. 08:00 or 22:00) with Bengali period and 12-hour AM/PM format.
     */
    public static function formatTimePeriod(?string $timeStr): ?string
    {
        if (! $timeStr) {
            return null;
        }

        try {
            $carbon = Carbon::parse($timeStr);
            $hour = (int) $carbon->format('G');
            $period = match (true) {
                $hour >= 5 && $hour < 12 => 'সকাল',
                $hour >= 12 && $hour < 15 => 'দুপুর',
                $hour >= 15 && $hour < 18 => 'বিকাল',
                $hour >= 18 && $hour < 20 => 'সন্ধ্যা',
                default => 'রাত',
            };

            return "{$period} ".$carbon->format('h:i A');
        } catch (\Throwable) {
            return substr($timeStr, 0, 5);
        }
    }

    /**
     * Human-readable Bengali time range with 12-hour AM/PM format.
     * e.g. "সকাল 08:00 AM থেকে রাত 10:00 PM"
     */
    public function getFormattedTimeRangeAttribute(): string
    {
        $start = static::formatTimePeriod($this->start_time);
        $end = static::formatTimePeriod($this->end_time);

        if ($start && $end) {
            return "{$start} থেকে {$end}";
        }

        return $start ?? 'সারাদিন';
    }

    /**
     * Absolute deadline Carbon instance combining scheduled date and end time.
     */
    public function getDeadlineDateTimeAttribute(): ?Carbon
    {
        if (! $this->scheduled_date) {
            return null;
        }

        $endTime = $this->end_time ?: '23:59:59';

        return Carbon::parse($this->scheduled_date->format('Y-m-d').' '.$endTime);
    }

    /**
     * Determine if current time has passed the scheduled assignment deadline.
     */
    public function getIsPastDeadlineAttribute(): bool
    {
        $deadline = $this->deadline_date_time;

        return $deadline ? now()->isAfter($deadline) : false;
    }

    /**
     * Total work duration in human-readable Bengali format.
     */
    public function getWorkDurationAttribute(): ?string
    {
        if (! $this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();
        $diffMinutes = (int) $this->started_at->diffInMinutes($endTime);

        if ($diffMinutes < 1) {
            return '১ মিনিটের কম';
        }

        $hours = intdiv($diffMinutes, 60);
        $minutes = $diffMinutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours} ঘণ্টা {$minutes} মিনিট";
        } elseif ($hours > 0) {
            return "{$hours} ঘণ্টা";
        }

        return "{$minutes} মিনিট";
    }

    /**
     * Scope a query to only include recurring assignments.
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Scope a query to a specific recurring group.
     */
    public function scopeRecurringGroup($query, string $groupId)
    {
        return $query->where('recurring_group_id', $groupId);
    }

    /**
     * Relationship to load all sibling assignments belonging to the same recurring series.
     */
    public function recurringChildren(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TaskAssignment::class, 'recurring_group_id', 'recurring_group_id')
            ->orderBy('scheduled_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->orderBy('recurrence_seq', 'asc');
    }

    /**
     * Bengali label for recurrence type.
     */
    public function getRecurrenceTypeLabelAttribute(): string
    {
        return match ($this->recurrence_type) {
            'daily' => 'দৈনিক',
            'weekly' => 'সাপ্তাহিক',
            'monthly' => 'মাসিক',
            default => 'এককালীন',
        };
    }
}
