<x-app-layout>
    <x-slot name="title">Home</x-slot>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-3xl font-semibold text-gray-900 dark:text-white">
                Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name)[0] }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ now()->format('l, j F Y') }} · {{ auth()->user()->organization?->name }}
                @if (auth()->user()->organization?->isOnTrial())
                    <span class="ml-1 rounded-full bg-gold-soft px-2 py-0.5 text-xs font-semibold text-gold">
                        Trial · {{ auth()->user()->organization->trialDaysLeft() }} days left
                    </span>
                @endif
            </p>
        </div>
        @if ($canTasks)
            <a href="{{ route('tasks.board') }}" class="btn-gold">Open task board</a>
        @elseif ($canCrm)
            <a href="{{ route('crm.deals.index') }}" class="btn-gold">Open pipeline</a>
        @endif
    </div>

    <x-help-banner id="tip:home"
        title="This is your home base"
        text="Everything here is live from your workspace. The numbers update as your team works, and each card links through to the module behind it. Use the ? icons anywhere for a quick explanation." />

    {{-- Nobody has granted this person anything yet --}}
    @if (empty($allowedModules))
        <div class="card p-10 text-center">
            <h2 class="font-display text-xl font-semibold">No modules yet</h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                Your account is set up, but nobody has granted you access to a module yet.
                Ask an admin or your manager to give you CRM, the daily tracker or HR from the Team page.
            </p>
        </div>
    @else
        {{-- Metric cards — only the ones this user can actually see --}}
        <div data-tour="metrics" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @if ($canCrm)
                <x-stat-card label="Open deals" :value="$openDealCount" :sub="'€'.number_format($openDealValue, 0).' in pipeline'" />
            @endif
            @if ($canTasks)
                <x-stat-card label="Tasks due today" :value="$tasksDueToday" sub="across the whole team" />
            @endif
            @if ($canHr)
                <x-stat-card label="On leave today" :value="$onLeaveToday->count()" :sub="$onLeaveToday->isNotEmpty() ? $onLeaveToday->map(fn($l) => $l->employee->user->name)->join(', ') : 'full house today'" />
                <x-stat-card label="Next payday" :value="$nextPayday->format('j M')" :sub="$nextPayday->diffForHumans()" />
            @endif
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- My tasks --}}
        @if ($canTasks)
        <div data-tour="my-tasks" class="card lg:col-span-2">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-ink-700">
                <h2 class="font-display text-lg font-semibold">My tasks</h2>
                <a href="{{ route('tasks.board', ['mine' => 1]) }}" class="text-sm font-medium text-gold hover:underline">View all</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-ink-700">
                @forelse ($myTasks as $task)
                    <a href="{{ route('tasks.show', $task) }}" class="flex items-center gap-4 px-5 py-3 transition hover:bg-gray-50 dark:hover:bg-ink-800">
                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $task->category?->color ?? '#8A8A8A' }}"></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $task->title }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $task->reference }}</p>
                        </div>
                        @if ($task->due_date)
                            <span class="text-xs {{ $task->isOverdue() ? 'font-semibold text-priority-high' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $task->isOverdue() ? 'Overdue · ' : '' }}{{ $task->due_date->format('j M') }}
                            </span>
                        @endif
                        <x-status-badge :status="$task->status" />
                    </a>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Nothing on your plate — enjoy it while it lasts.</p>
                @endforelse
            </div>
        </div>
        @endif

        <div class="space-y-6 {{ $canTasks ? '' : 'lg:col-span-3' }}">
            {{-- Pipeline snapshot --}}
            @if ($canCrm)
            <div data-tour="pipeline" class="card">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-ink-700">
                    <h2 class="flex items-center gap-1.5 font-display text-lg font-semibold">
                        Pipeline snapshot
                        <x-help-tip title="Pipeline snapshot"
                            text="Total deal value in each stage. Bars are relative to your biggest stage, so you can spot where work is piling up." />
                    </h2>
                </div>
                <div class="space-y-3 p-5">
                    @foreach (\App\Models\Deal::STAGES as $stage)
                        @php
                            $count = $pipelineCounts[$stage] ?? 0;
                            $total = $pipeline[$stage] ?? 0;
                            $max = max($pipeline->max() ?: 1, 1);
                        @endphp
                        <a href="{{ route('crm.deals.index') }}" class="block">
                            <div class="mb-1 flex justify-between text-xs">
                                <span class="font-medium capitalize">{{ $stage }} <span class="text-gray-400">({{ $count }})</span></span>
                                <span class="text-gray-500 dark:text-gray-400">€{{ number_format($total, 0) }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-ink-700">
                                <div class="h-full rounded-full bg-gold" style="width: {{ $max ? round($total / $max * 100) : 0 }}%"></div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Who's off / latest payslip --}}
            @if ($canHr)
            <div class="card p-5">
                <h2 class="font-display text-lg font-semibold">Who's off today</h2>
                <div class="mt-3 space-y-2">
                    @forelse ($onLeaveToday as $leave)
                        <div class="flex items-center gap-3 text-sm">
                            <x-avatar :user="$leave->employee->user" size="sm" />
                            <span class="flex-1">{{ $leave->employee->user->name }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $leave->leaveType->name }} · back {{ $leave->end_date->addDay()->format('j M') }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Everyone's in today.</p>
                    @endforelse
                </div>

                @if ($latestPayslip)
                    <div class="mt-4 border-t border-gray-100 pt-4 dark:border-ink-700">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Latest payslip</h3>
                        <div class="mt-2 flex items-center justify-between text-sm">
                            <span>{{ $latestPayslip->period_label }} · net €{{ number_format($latestPayslip->net, 2) }}</span>
                            <a class="font-medium text-gold hover:underline" href="{{ route('hr.payslips.download', $latestPayslip) }}">Download</a>
                        </div>
                    </div>
                @endif
            </div>
            @endif

            {{-- Manager: pending approvals --}}
            @if ($pendingLeave->isNotEmpty())
                <div class="card p-5">
                    <h2 class="font-display text-lg font-semibold">Leave awaiting approval</h2>
                    <div class="mt-3 space-y-2">
                        @foreach ($pendingLeave as $req)
                            <a href="{{ route('hr.leave.index') }}" class="flex items-center gap-3 text-sm hover:text-gold">
                                <x-avatar :user="$req->employee->user" size="sm" />
                                <span class="flex-1">{{ $req->employee->user->name }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $req->start_date->format('j M') }} – {{ $req->end_date->format('j M') }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
