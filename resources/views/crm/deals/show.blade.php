<x-app-layout>
    <x-slot name="title">{{ $deal->title }}</x-slot>

    <div class="mx-auto max-w-4xl">
        <a href="{{ route('crm.deals.index') }}" class="text-sm text-gray-500 hover:text-gold dark:text-gray-400">&larr; Back to pipeline</a>

        <div class="card mt-3 p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="font-display text-2xl font-semibold text-gray-900 dark:text-white">{{ $deal->title }}</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $deal->account?->name ?? 'No account' }}
                        @if ($deal->contact) · {{ $deal->contact->fullName() }} ({{ $deal->contact->title }}) @endif
                    </p>
                </div>
                <div class="text-right">
                    <p class="font-display text-3xl font-semibold text-gold">€{{ number_format($deal->value, 0) }}</p>
                    <span class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide
                        {{ $deal->stage === 'won' ? 'bg-status-done/20 text-status-done' : ($deal->stage === 'lost' ? 'bg-gray-200 text-gray-500 dark:bg-ink-700' : 'bg-gold-soft text-gold') }}">
                        {{ $deal->stage }}
                    </span>
                </div>
            </div>

            <div class="mt-6 grid gap-3 text-sm sm:grid-cols-2">
                <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Owner</span>
                    <span class="flex items-center gap-2 font-medium"><x-avatar :user="$deal->owner" size="sm" /> {{ $deal->owner?->name ?? '—' }}</span></p>
                <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Expected close</span>
                    <span class="font-medium">{{ $deal->expected_close_date?->format('j M Y') ?? '—' }}</span></p>
                <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Contact email</span>
                    <span class="font-medium">{{ $deal->contact?->email ?? '—' }}</span></p>
                <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Industry</span>
                    <span class="font-medium">{{ $deal->account?->industry ?? '—' }}</span></p>
            </div>

            @if ($deal->stage !== 'won' && ! $deal->project_id)
                <form method="POST" action="{{ route('crm.deals.win', $deal) }}" class="mt-6 rounded-xl border border-status-done/40 bg-status-done/10 p-4">
                    @csrf
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-status-done">Close this deal as won</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Creates a delivery project and onboarding tasks, assigned to team members who aren't on leave.</p>
                        </div>
                        <button type="submit" class="rounded-lg bg-status-done px-4 py-2 text-sm font-bold text-white transition hover:brightness-110">Mark won</button>
                    </div>
                </form>
            @endif

            @if ($deal->project)
                <div class="mt-6 rounded-xl border border-gray-200 p-4 dark:border-ink-600">
                    <div class="flex items-center justify-between">
                        <h2 class="font-display text-lg font-semibold">Project: {{ $deal->project->name }}</h2>
                        <span class="rounded-full bg-gold-soft px-2.5 py-0.5 text-xs font-bold uppercase text-gold">{{ $deal->project->status }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $deal->project->description }}</p>
                    <div class="mt-3 divide-y divide-gray-100 dark:divide-ink-700">
                        @foreach ($deal->project->tasks as $task)
                            <a href="{{ route('tasks.show', $task) }}" class="flex items-center gap-3 py-2 text-sm transition hover:text-gold">
                                <span class="font-mono text-xs text-gray-400">{{ $task->reference }}</span>
                                <span class="flex-1 truncate font-medium">{{ $task->title }}</span>
                                <x-status-badge :status="$task->status" />
                                <x-avatar :user="$task->assignee" size="sm" />
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
