<x-app-layout>
    <x-slot name="title">Task Dashboard</x-slot>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-3xl font-semibold text-gray-900 dark:text-white">Task Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ auth()->user()->isManager() ? 'Team-wide view' : 'Your tasks' }}
            </p>
        </div>
        <a href="{{ route('tasks.board') }}" class="btn-gold">Open board</a>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        @foreach (\App\Models\Task::STATUSES as $s)
            <a href="{{ route('tasks.board') }}" class="card p-4 transition hover:border-gold/60">
                <span class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <span class="h-2 w-2 rounded-full" style="background: {{ \App\Models\Task::STATUS_COLORS[$s] }}"></span>
                    {{ \App\Models\Task::STATUS_LABELS[$s] }}
                </span>
                <p class="mt-2 font-display text-3xl font-semibold">{{ $byStatus[$s] ?? 0 }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="card">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-ink-700">
                <h2 class="font-display text-lg font-semibold text-priority-high">Overdue ({{ $overdue->count() }})</h2>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-ink-700">
                @forelse ($overdue as $task)
                    <a href="{{ route('tasks.show', $task) }}" class="flex items-center gap-3 px-5 py-3 transition hover:bg-gray-50 dark:hover:bg-ink-800">
                        <span class="h-2 w-2 shrink-0 rounded-full" style="background: {{ $task->category?->color ?? '#8A8A8A' }}"></span>
                        <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $task->title }}</span>
                        <span class="text-xs font-semibold text-priority-high">{{ $task->due_date->format('j M') }}</span>
                        <x-avatar :user="$task->assignee" size="sm" />
                    </a>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Nothing overdue. 🎉</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-ink-700">
                <h2 class="font-display text-lg font-semibold">Due in the next 7 days ({{ $dueSoon->count() }})</h2>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-ink-700">
                @forelse ($dueSoon as $task)
                    <a href="{{ route('tasks.show', $task) }}" class="flex items-center gap-3 px-5 py-3 transition hover:bg-gray-50 dark:hover:bg-ink-800">
                        <span class="h-2 w-2 shrink-0 rounded-full" style="background: {{ $task->category?->color ?? '#8A8A8A' }}"></span>
                        <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $task->title }}</span>
                        <x-status-badge :status="$task->status" />
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $task->due_date->format('j M') }}</span>
                    </a>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">A quiet week ahead.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card mt-6">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-ink-700">
            <h2 class="font-display text-lg font-semibold">Categories</h2>
        </div>
        <div class="grid gap-1 p-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($categories as $cat)
                <a href="{{ route('tasks.board', ['category' => $cat->id]) }}"
                   class="flex items-center justify-between rounded-lg px-3 py-2 transition hover:bg-gray-50 dark:hover:bg-ink-800">
                    <span class="flex items-center gap-2 text-sm font-medium">
                        <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $cat->color }}"></span>
                        {{ $cat->name }}
                    </span>
                    <span class="text-xs text-gray-400">{{ $cat->tasks_count }}</span>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
