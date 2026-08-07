<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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

        // Keep the denormalised depth honest, and refuse any move that would
        // make a task its own ancestor. Nesting is unbounded, so a cycle
        // would send every recursive walk into an infinite loop.
        static::saving(function (Task $task) {
            if ($task->isDirty('parent_id')) {
                $task->guardAgainstCycle();
            }

            $task->depth = $task->parent_id
                ? (static::withoutGlobalScopes()->find($task->parent_id)?->depth ?? 0) + 1
                : 0;
        });

        // Moving a task moves its whole branch, so descendants need new depths.
        static::updated(function (Task $task) {
            if ($task->wasChanged('parent_id')) {
                $task->refreshDescendantDepths();
            }
        });
    }

    // ---- Branching -------------------------------------------------------

    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    /** Children, their children, and so on — eager loaded in one tree. */
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive', 'assignee', 'category');
    }

    public function isSubtask(): bool
    {
        return $this->parent_id !== null;
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /** Every task beneath this one, flattened. */
    public function descendants(): Collection
    {
        $out = collect();

        foreach ($this->children as $child) {
            $out->push($child);
            $out = $out->concat($child->descendants());
        }

        return $out;
    }

    /** Walk up to the root, nearest ancestor first. */
    public function ancestors(): Collection
    {
        $out = collect();
        $node = $this->parent;

        // Defensive bound: a cycle should be impossible, but an infinite
        // loop here would take the request down with it.
        $guard = 0;
        while ($node && $guard++ < 100) {
            $out->push($node);
            $node = $node->parent;
        }

        return $out;
    }

    public function rootTask(): Task
    {
        return $this->ancestors()->last() ?? $this;
    }

    /**
     * Completion across this task's whole branch, as a percentage.
     * A leaf task is simply done or not; a parent reflects its descendants.
     */
    public function branchProgress(): int
    {
        $descendants = $this->descendants();

        if ($descendants->isEmpty()) {
            return $this->status === 'done' ? 100 : 0;
        }

        $all = $descendants->push($this);
        $done = $all->where('status', 'done')->count();

        return (int) round($done / $all->count() * 100);
    }

    /** Refuse a parent that is this task, or anywhere beneath it. */
    public function guardAgainstCycle(): void
    {
        if (! $this->parent_id) {
            return;
        }

        if ($this->exists && (int) $this->parent_id === (int) $this->id) {
            throw new \RuntimeException('A task cannot be its own parent.');
        }

        $ancestorId = $this->parent_id;
        $guard = 0;

        while ($ancestorId && $guard++ < 100) {
            if ($this->exists && (int) $ancestorId === (int) $this->id) {
                throw new \RuntimeException('That would place the task inside its own branch.');
            }

            $ancestorId = static::withoutGlobalScopes()
                ->whereKey($ancestorId)
                ->value('parent_id');
        }
    }

    /** After a move, re-stamp depth down the branch. */
    public function refreshDescendantDepths(): void
    {
        foreach ($this->children as $child) {
            $child->depth = $this->depth + 1;
            $child->saveQuietly();
            $child->refreshDescendantDepths();
        }
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
