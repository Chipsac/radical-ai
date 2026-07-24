@props(['task'])

<a href="{{ route('tasks.show', $task) }}" data-id="{{ $task->id }}"
   class="block cursor-grab rounded-xl border border-gray-200 bg-white p-3 shadow-sm transition hover:border-gold/60 dark:border-ink-600 dark:bg-ink-800">
    <div class="mb-1.5 flex items-center justify-between gap-2">
        <span class="truncate font-mono text-[11px] text-gray-400 dark:text-gray-500">{{ $task->reference }}</span>
        <x-priority-badge :priority="$task->priority" />
    </div>
    <p class="text-sm font-medium leading-snug">{{ $task->title }}</p>
    <div class="mt-2.5 flex items-center justify-between gap-2">
        <span class="inline-flex items-center gap-1.5 truncate text-xs text-gray-500 dark:text-gray-400">
            @if ($task->category)
                <span class="h-2 w-2 shrink-0 rounded-full" style="background: {{ $task->category->color }}"></span>
                <span class="truncate">{{ $task->category->name }}</span>
            @endif
        </span>
        <span class="flex items-center gap-2">
            @if ($task->due_date)
                <span class="text-[11px] {{ $task->isOverdue() ? 'font-bold text-priority-high' : 'text-gray-400 dark:text-gray-500' }}">{{ $task->due_date->format('j M') }}</span>
            @endif
            <x-avatar :user="$task->assignee" size="sm" />
        </span>
    </div>
</a>
