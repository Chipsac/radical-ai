<x-app-layout>
    <x-slot name="title">{{ $employee->user?->name }}</x-slot>

    <div class="mx-auto max-w-4xl">
        <a href="{{ route('hr.index') }}" class="text-sm text-gray-500 hover:text-gold dark:text-gray-400">&larr; Back to directory</a>

        <div class="card mt-3 p-6">
            <div class="flex flex-wrap items-center gap-4">
                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-gold-soft text-xl font-bold text-gold">
                    {{ $employee->user?->initials() }}
                </span>
                <div class="flex-1">
                    <h1 class="font-display text-2xl font-semibold text-gray-900 dark:text-white">{{ $employee->user?->name }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $employee->job_title }} · {{ $employee->department }}</p>
                </div>
                @if ($employee->isOnLeave())
                    <span class="rounded-full bg-priority-high/15 px-3 py-1 text-sm font-semibold text-priority-high">On leave today</span>
                @else
                    <span class="rounded-full bg-status-done/15 px-3 py-1 text-sm font-semibold text-status-done">Available</span>
                @endif
            </div>

            <div class="mt-6 grid gap-3 text-sm sm:grid-cols-2">
                <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Email</span><span class="font-medium">{{ $employee->user?->email }}</span></p>
                <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Manager</span><span class="font-medium">{{ $employee->manager?->name ?? '—' }}</span></p>
                <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Started</span><span class="font-medium">{{ $employee->start_date?->format('j M Y') }}</span></p>
                <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Utilisation (30 days)</span><span class="font-medium">{{ $utilisationHours }} h logged</span></p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="card p-6">
                <h2 class="font-display text-lg font-semibold">Leave balances {{ now()->year }}</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($employee->leaveBalances->where('year', now()->year) as $balance)
                        <div>
                            <div class="mb-1 flex justify-between text-sm">
                                <span class="font-medium">{{ $balance->leaveType?->name }}</span>
                                <span class="text-gray-500 dark:text-gray-400">{{ $balance->taken_days }} / {{ $balance->entitlement_days }} days</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-ink-700">
                                <div class="h-full rounded-full bg-gold"
                                     style="width: {{ $balance->entitlement_days > 0 ? min(100, round($balance->taken_days / $balance->entitlement_days * 100)) : 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <h2 class="mt-6 font-display text-lg font-semibold">Recent leave</h2>
                <div class="mt-2 divide-y divide-gray-100 text-sm dark:divide-ink-700">
                    @forelse ($employee->leaveRequests->sortByDesc('start_date')->take(5) as $req)
                        <div class="flex items-center justify-between py-2">
                            <span>{{ $req->leaveType?->name }} · {{ $req->start_date->format('j M') }} – {{ $req->end_date->format('j M') }}</span>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold
                                {{ $req->status === 'approved' ? 'bg-status-done/15 text-status-done' : ($req->status === 'rejected' ? 'bg-priority-high/15 text-priority-high' : 'bg-gold-soft text-gold') }}">
                                {{ $req->status }}
                            </span>
                        </div>
                    @empty
                        <p class="py-2 text-gray-500 dark:text-gray-400">No leave requests.</p>
                    @endforelse
                </div>
            </div>

            <div class="card p-6">
                <h2 class="font-display text-lg font-semibold">Open tasks ({{ $openTasks->count() }})</h2>
                <div class="mt-2 divide-y divide-gray-100 dark:divide-ink-700">
                    @forelse ($openTasks as $task)
                        <a href="{{ route('tasks.show', $task) }}" class="flex items-center gap-3 py-2.5 text-sm transition hover:text-gold">
                            <span class="h-2 w-2 shrink-0 rounded-full" style="background: {{ $task->category?->color ?? '#8A8A8A' }}"></span>
                            <span class="min-w-0 flex-1 truncate font-medium">{{ $task->title }}</span>
                            <x-status-badge :status="$task->status" />
                        </a>
                    @empty
                        <p class="py-2 text-sm text-gray-500 dark:text-gray-400">No open tasks.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
