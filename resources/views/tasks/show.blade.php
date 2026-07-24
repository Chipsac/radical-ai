<x-app-layout>
    <x-slot name="title">{{ $task->reference }}</x-slot>

    <div class="mx-auto max-w-4xl">
        <a href="{{ route('tasks.board') }}" class="text-sm text-gray-500 hover:text-gold dark:text-gray-400">&larr; Back to board</a>

        <div class="card mt-3 p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="font-mono text-xs text-gray-400 dark:text-gray-500">{{ $task->reference }}</p>
                    <h1 class="mt-1 font-display text-2xl font-semibold text-gray-900 dark:text-white">{{ $task->title }}</h1>
                </div>
                <div class="flex items-center gap-2">
                    <x-priority-badge :priority="$task->priority" />
                    <x-status-badge :status="$task->status" />
                </div>
            </div>

            {{-- Inline status buttons --}}
            <div class="mt-5 flex flex-wrap gap-1.5" x-data>
                @foreach (\App\Models\Task::STATUSES as $s)
                    <button
                        @click="axios.patch('{{ route('tasks.status', $task) }}', { status: '{{ $s }}' }).then(() => window.location.reload())"
                        class="rounded-full border px-3 py-1 text-xs font-semibold transition
                               {{ $task->status === $s
                                   ? 'border-transparent text-ink-950'
                                   : 'border-gray-300 text-gray-600 hover:border-gold dark:border-ink-600 dark:text-gray-300' }}"
                        @if ($task->status === $s) style="background: {{ \App\Models\Task::STATUS_COLORS[$s] }}" @endif>
                        {{ \App\Models\Task::STATUS_LABELS[$s] }}
                    </button>
                @endforeach
            </div>

            <div class="mt-6 grid gap-6 sm:grid-cols-2">
                <div class="space-y-3 text-sm">
                    <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Assignee</span>
                        <span class="flex items-center gap-2 font-medium"><x-avatar :user="$task->assignee" size="sm" /> {{ $task->assignee?->name ?? 'Unassigned' }}</span></p>
                    <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Category</span>
                        <span class="flex items-center gap-2 font-medium">
                            @if ($task->category)<span class="h-2 w-2 rounded-full" style="background: {{ $task->category->color }}"></span>@endif
                            {{ $task->category?->name ?? '—' }}</span></p>
                    <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Project</span>
                        <span class="font-medium">{{ $task->project?->name ?? '—' }}</span></p>
                    <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Created by</span>
                        <span class="font-medium">{{ $task->creator?->name ?? '—' }}</span></p>
                </div>
                <div class="space-y-3 text-sm">
                    <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Start date</span>
                        <span class="font-medium">{{ $task->start_date?->format('j M Y') ?? '—' }}</span></p>
                    <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Due date</span>
                        <span class="font-medium {{ $task->isOverdue() ? 'text-priority-high' : '' }}">
                            {{ $task->due_date?->format('j M Y') ?? '—' }}{{ $task->isOverdue() ? ' · overdue' : '' }}</span></p>
                    <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Continuous</span>
                        <span class="font-medium">{{ $task->is_continuous ? 'Yes' : 'No' }}</span></p>
                    <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Time logged</span>
                        <span class="font-medium">{{ round($task->timeEntries->sum('minutes') / 60, 1) }} h</span></p>
                </div>
            </div>

            @if ($task->description)
                <div class="mt-6 rounded-xl bg-gray-50 p-4 text-sm leading-relaxed text-gray-700 dark:bg-ink-800 dark:text-gray-300">
                    {{ $task->description }}
                </div>
            @endif

            {{-- Log time --}}
            <form method="POST" action="{{ route('tasks.time.store', $task) }}" class="mt-6 flex items-end gap-3">
                @csrf
                <div>
                    <label class="label-app">Log time (minutes)</label>
                    <input type="number" name="minutes" min="1" max="1440" value="30" class="input-app !w-36" />
                </div>
                <button type="submit" class="btn-ghost">Log time</button>
            </form>
        </div>

        {{-- Progress updates --}}
        <div class="card mt-6 p-6">
            <h2 class="font-display text-xl font-semibold">Progress updates</h2>

            <form method="POST" action="{{ route('tasks.updates.store', $task) }}" class="mt-4">
                @csrf
                <textarea name="body" rows="2" required class="input-app" placeholder="Add an update for the team…"></textarea>
                <div class="mt-2 flex justify-end">
                    <button type="submit" class="btn-gold">Post update</button>
                </div>
            </form>

            <div class="mt-6 space-y-4">
                @forelse ($task->progressUpdates as $update)
                    <div class="flex gap-3">
                        <x-avatar :user="$update->user" />
                        <div class="flex-1 rounded-xl bg-gray-50 p-3 dark:bg-ink-800">
                            <div class="mb-1 flex items-center justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $update->user?->name ?? 'Unknown' }}</span>
                                <span>{{ $update->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm leading-relaxed">{{ $update->body }}</p>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">No updates yet — be the first.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
