<x-app-layout>
    <x-slot name="title">Leave</x-slot>

    <div class="mx-auto max-w-5xl">
        <div class="mb-6">
            <h1 class="font-display text-3xl font-semibold text-gray-900 dark:text-white">Leave</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Request time off and track approvals. Approved leave appears on the <a href="{{ route('hr.calendar') }}" class="text-gold hover:underline">calendar</a>.</p>
        </div>

        @if (auth()->user()->isManager() && $pendingApprovals->isNotEmpty())
            <div class="card mb-6">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-ink-700">
                    <h2 class="font-display text-lg font-semibold">Awaiting your approval ({{ $pendingApprovals->count() }})</h2>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-ink-700">
                    @foreach ($pendingApprovals as $req)
                        @php $uncovered = $coverageWarnings[$req->id] ?? collect(); @endphp
                        <div class="px-5 py-3">
                        <div class="flex flex-wrap items-center gap-3">
                            <x-avatar :user="$req->employee->user" />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium">
                                    {{ $req->employee->user->name }}
                                    @if ($req->employee->team)
                                        <span class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold text-gray-500 dark:bg-ink-700 dark:text-gray-300">{{ $req->employee->team->name }}</span>
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $req->leaveType->name }} · {{ $req->start_date->format('j M') }} – {{ $req->end_date->format('j M Y') }} ({{ $req->days }} days)
                                    @if ($req->reason) · “{{ $req->reason }}” @endif
                                </p>
                            </div>
                            <form method="POST" action="{{ route('hr.leave.decide', $req) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="decision" value="approved">
                                <button type="submit" class="rounded-lg bg-status-done px-3 py-1.5 text-xs font-bold text-white transition hover:brightness-110">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('hr.leave.decide', $req) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="decision" value="rejected">
                                <button type="submit" class="rounded-lg border border-priority-high px-3 py-1.5 text-xs font-bold text-priority-high transition hover:bg-priority-high/10">Reject</button>
                            </form>
                        </div>

                        {{-- Advisory only. The manager decides; we just make
                             sure they are not surprised afterwards. --}}
                        @if ($uncovered->isNotEmpty())
                            <div class="mt-2 flex items-start gap-2.5 rounded-lg border border-priority-high/40 bg-priority-high/10 px-3 py-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-priority-high" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                </svg>
                                <div class="text-xs">
                                    <p class="font-semibold text-priority-high">
                                        Approving this leaves {{ $req->employee->team->name }} below minimum cover
                                        on {{ $uncovered->count() }} working day{{ $uncovered->count() === 1 ? '' : 's' }}
                                    </p>
                                    <p class="mt-0.5 text-gray-600 dark:text-gray-300">
                                        @foreach ($uncovered->take(6) as $day)
                                            <span class="mr-2 inline-block">{{ $day['date']->format('D j M') }}
                                                <span class="text-gray-400">({{ $day['present_after'] }} of {{ $day['required'] }})</span>
                                            </span>
                                        @endforeach
                                        @if ($uncovered->count() > 6)
                                            <span class="text-gray-400">+{{ $uncovered->count() - 6 }} more</span>
                                        @endif
                                    </p>
                                    <p class="mt-1 text-gray-500 dark:text-gray-400">
                                        You can still approve — this is a heads-up, not a block.
                                    </p>
                                </div>
                            </div>
                        @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-5">
            {{-- Request form + balances --}}
            <div class="space-y-6 lg:col-span-2">
                <div data-tour="leave-request" class="card p-6">
                    <h2 class="flex items-center gap-1.5 font-display text-lg font-semibold">
                        Request leave
                        <x-help-tip title="How days are counted"
                            text="Only working days are counted — weekends are excluded automatically. Your manager sees the request straight away and you'll see the decision here." />
                    </h2>
                    <form method="POST" action="{{ route('hr.leave.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label class="label-app">Type</label>
                            <select name="leave_type_id" required class="input-app">
                                @foreach ($leaveTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}{{ $type->paid ? '' : ' (unpaid)' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="label-app">From</label>
                                <input type="date" name="start_date" required class="input-app" value="{{ now()->addWeek()->toDateString() }}" />
                            </div>
                            <div>
                                <label class="label-app">To</label>
                                <input type="date" name="end_date" required class="input-app" value="{{ now()->addWeek()->toDateString() }}" />
                            </div>
                        </div>
                        <div>
                            <label class="label-app">Reason</label>
                            <textarea name="reason" rows="2" class="input-app" placeholder="Optional"></textarea>
                        </div>
                        <button type="submit" class="btn-gold w-full justify-center">Submit request</button>
                    </form>
                </div>

                <div data-tour="leave-balances" class="card p-6">
                    <h2 class="font-display text-lg font-semibold">Your balances {{ now()->year }}</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($balances as $balance)
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium">{{ $balance->leaveType?->name }}</span>
                                <span class="text-gray-500 dark:text-gray-400">
                                    <strong class="text-gray-900 dark:text-white">{{ $balance->remaining() }}</strong> of {{ $balance->entitlement_days }} left
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No balances found.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Who else on my team is off --}}
            @if ($team)
                <div class="card p-6 lg:col-span-3">
                    <h2 class="flex items-center gap-1.5 font-display text-lg font-semibold">
                        {{ $team->name }} — who's off
                        <x-help-tip title="Your team's leave"
                            text="Approved leave for everyone in your team, so you can plan around each other without asking a manager to look it up." />
                    </h2>

                    @php $cover = $team->coverageOn(now()); @endphp
                    @if (! $cover['weekend'])
                        <p class="mt-1 text-xs {{ $cover['covered'] ? 'text-gray-500 dark:text-gray-400' : 'font-semibold text-priority-high' }}">
                            Today: {{ $cover['present'] }} of {{ $team->activeHeadcount() }} in
                            @if ($team->minimum_cover > 0)
                                · minimum cover {{ $team->minimum_cover }}
                                @unless ($cover['covered']) — below minimum @endunless
                            @endif
                        </p>
                    @endif

                    <div class="mt-4 divide-y divide-gray-100 dark:divide-ink-700">
                        @forelse ($teamLeave as $leave)
                            <div class="flex items-center gap-3 py-2.5 text-sm">
                                <x-avatar :user="$leave->employee->user" size="sm" />
                                <span class="min-w-0 flex-1 truncate">
                                    {{ $leave->employee->user->name }}
                                    @if ($leave->employee_id === auth()->user()->employee?->id)
                                        <span class="text-xs text-gray-400">(you)</span>
                                    @endif
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $leave->leaveType->name }}</span>
                                <span class="text-xs font-medium">
                                    {{ $leave->start_date->format('j M') }} – {{ $leave->end_date->format('j M') }}
                                </span>
                            </div>
                        @empty
                            <p class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                Nobody in {{ $team->name }} has upcoming leave booked.
                            </p>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- History --}}
            <div class="card lg:col-span-3">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-ink-700">
                    <h2 class="font-display text-lg font-semibold">Your requests</h2>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-ink-700">
                    @forelse ($myRequests as $req)
                        <div class="flex items-center gap-3 px-5 py-3 text-sm">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium">{{ $req->leaveType?->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $req->start_date->format('j M') }} – {{ $req->end_date->format('j M Y') }} · {{ $req->days }} days
                                    @if ($req->approver) · decided by {{ $req->approver->name }} @endif
                                </p>
                            </div>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold
                                {{ $req->status === 'approved' ? 'bg-status-done/15 text-status-done' : ($req->status === 'rejected' ? 'bg-priority-high/15 text-priority-high' : 'bg-gold-soft text-gold') }}">
                                {{ $req->status }}
                            </span>
                        </div>
                    @empty
                        <p class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No leave requests yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
