<x-app-layout>
    <x-slot name="title">Leave overview</x-slot>

    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="font-display text-3xl font-semibold text-gray-900 dark:text-white">Leave overview</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Everyone's allowance and what they have left, for {{ $year }}.
                </p>
            </div>

            <form method="get" class="flex items-center gap-2">
                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Year</label>
                <select name="year" onchange="this.form.submit()" class="input-app !w-28">
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <x-help-banner id="tip:leave-overview"
            title="One place for the whole team's allowance"
            text="Each person sees their own balance on the Leave page. This view is for managers and admins — the same numbers for everyone at once, so you can answer an allowance question without opening profiles one by one." />

        <div class="card overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500 dark:border-ink-700 dark:text-gray-400">
                        <th class="px-5 py-3">Person</th>
                        @foreach ($leaveTypes as $type)
                            <th class="px-4 py-3 text-center">
                                {{ $type->name }}
                                @if (! $type->paid)
                                    <span class="block text-[10px] font-normal normal-case text-gray-400">unpaid</span>
                                @endif
                            </th>
                        @endforeach
                        <th class="px-5 py-3 text-right">Today</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-ink-700">
                    @forelse ($employees as $employee)
                        @php $row = $balances[$employee->id] ?? collect(); @endphp
                        <tr class="transition hover:bg-gray-50 dark:hover:bg-ink-800">
                            <td class="px-5 py-3">
                                <a href="{{ route('hr.show', $employee) }}" class="flex items-center gap-3 hover:text-gold">
                                    <x-avatar :user="$employee->user" size="sm" />
                                    <span>
                                        <span class="block font-medium">{{ $employee->user->name }}</span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $employee->job_title }}</span>
                                    </span>
                                </a>
                            </td>

                            @foreach ($leaveTypes as $type)
                                @php
                                    $balance = $row[$type->id] ?? null;
                                    $remaining = $balance?->remaining();
                                    // Flag anyone with very little left, so it
                                    // stands out without reading every number.
                                    $low = $balance && $balance->entitlement_days > 0
                                        && $remaining <= $balance->entitlement_days * 0.15;
                                @endphp
                                <td class="px-4 py-3 text-center">
                                    @if ($balance)
                                        <span class="font-semibold {{ $low ? 'text-priority-high' : '' }}">
                                            {{ rtrim(rtrim(number_format($remaining, 1), '0'), '.') }}
                                        </span>
                                        <span class="text-xs text-gray-400"> / {{ (int) $balance->entitlement_days }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                            @endforeach

                            <td class="px-5 py-3 text-right">
                                @if ($onLeaveToday->has($employee->id))
                                    <span class="rounded-full bg-priority-high/15 px-2 py-0.5 text-xs font-semibold text-priority-high">Off</span>
                                @else
                                    <span class="rounded-full bg-status-done/15 px-2 py-0.5 text-xs font-semibold text-status-done">In</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $leaveTypes->count() + 2 }}" class="px-5 py-10 text-center text-gray-500">
                                No employees yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Figures show days remaining out of the annual entitlement. Orange means 15% or less left.
        </p>

        @if ($upcoming->isNotEmpty())
            <div class="card mt-6">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-ink-700">
                    <h2 class="font-display text-lg font-semibold">Coming up</h2>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-ink-700">
                    @foreach ($upcoming as $leave)
                        <div class="flex items-center gap-3 px-5 py-3 text-sm">
                            <x-avatar :user="$leave->employee->user" size="sm" />
                            <span class="min-w-0 flex-1 truncate font-medium">{{ $leave->employee->user->name }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $leave->leaveType->name }}</span>
                            <span class="text-xs font-medium">
                                {{ $leave->start_date->format('j M') }} – {{ $leave->end_date->format('j M') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
