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
                        <div class="flex flex-wrap items-center gap-3 px-5 py-3">
                            <x-avatar :user="$req->employee->user" />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium">{{ $req->employee->user->name }}</p>
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
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-5">
            {{-- Request form + balances --}}
            <div class="space-y-6 lg:col-span-2">
                <div class="card p-6">
                    <h2 class="font-display text-lg font-semibold">Request leave</h2>
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

                <div class="card p-6">
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
