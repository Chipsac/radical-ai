<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use BelongsToOrganization;

    public const STATUSES = ['not_yet_started', 'to_do', 'in_progress', 'review', 'done'];

    public const PRIORITIES = ['low', 'medium', 'high'];

    public const STATUS_LABELS = [
        'not_yet_started' => 'Not Yet Started',
        'to_do' => 'To Do List',
        'in_progress' => 'In Progress',
        'review' => 'Review',
        'done' => 'Done',
    ];

    public const STATUS_COLORS = [
        'not_yet_started' => '#8A8A8A',
        'to_do' => '#3B82F6',
        'in_progress' => '#E0A44E',
        'review' => '#A855F7',
        'done' => '#22C55E',
    ];

    public const PRIORITY_COLORS = [
        'low' => '#8A8A8A',
        'medium' => '#E0A44E',
        'high' => '#EF7C4A',
    ];

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'is_continuous' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Task $task) {
            if (! $task->reference) {
                $task->reference = static::generateReference($task);
            }
        });
    }

    public static function generateReference(Task $task): string
    {
        $org = Organization::find($task->organization_id ?? auth()->user()?->organization_id);
        $seq = $org ? $org->nextTaskSeq() : (static::withoutGlobalScopes()->count() + 1);

        $prefix = '';
        if ($task->category_id) {
            $cat = TaskCategory::withoutGlobalScopes()->find($task->category_id);
            if ($cat && $cat->prefix) {
                $prefix = strtoupper($cat->prefix).'-';
            }
        }

        $initials = 'XX';
        if ($task->assignee_id) {
            $assignee = User::find($task->assignee_id);
            if ($assignee) {
                $initials = strtoupper($assignee->initials());
            }
        }

        return sprintf(
            '%s%s-%s-%03d-%s',
            $prefix,
            now()->format('Y'),
            strtoupper(now()->format('M')),
            $seq,
            $initials
        );
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function category()
    {
        return $this->belongsTo(TaskCategory::class, 'category_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function progressUpdates()
    {
        return $this->hasMany(TaskProgressUpdate::class)->latest();
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? '#8A8A8A';
    }

    public function priorityColor(): string
    {
        return self::PRIORITY_COLORS[$this->priority] ?? '#8A8A8A';
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->status !== 'done' && $this->due_date->isPast();
    }
}
