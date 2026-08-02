@props(['tasks', 'depth' => 0])

{{--
    Renders a branch of the task tree. Recurses into itself, so nesting is
    unbounded; indentation is capped so a deep branch stays on the page
    instead of marching off the right edge.
--}}
@foreach ($tasks as $child)
    <div style="margin-left: {{ min($depth, 6) * 18 }}px">
        <div class="flex items-center gap-3 border-l-2 py-2 pl-3 transition hover:bg-gray-50 dark:hover:bg-ink-800"
             style="border-color: {{ $child->status === 'done' ? '#22C55E' : ($child->category?->color ?? '#8A8A8A') }}">

            <a href="{{ route('tasks.show', $child) }}" class="min-w-0 flex-1">
                <span class="block truncate text-sm font-medium {{ $child->status === 'done' ? 'text-gray-400 line-through dark:text-gray-500' : '' }}">
                    {{ $child->title }}
                </span>
                <span class="font-mono text-[11px] text-gray-400">{{ $child->reference }}</span>
            </a>

            @if ($child->children->isNotEmpty())
                <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-500 dark:bg-ink-700 dark:text-gray-300"
                      title="{{ $child->children->count() }} subtask(s), {{ $child->branchProgress() }}% complete">
                    {{ $child->branchProgress() }}%
                </span>
            @endif

            @if ($child->due_date)
                <span class="shrink-0 text-[11px] {{ $child->isOverdue() ? 'font-semibold text-priority-high' : 'text-gray-400' }}">
                    {{ $child->due_date->format('j M') }}
                </span>
            @endif

            <x-status-badge :status="$child->status" class="shrink-0" />
            <x-avatar :user="$child->assignee" size="sm" />
        </div>

        @if ($child->children->isNotEmpty())
            <x-subtask-tree :tasks="$child->children" :depth="$depth + 1" />
        @endif
    </div>
@endforeach
