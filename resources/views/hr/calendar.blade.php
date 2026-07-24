<x-app-layout>
    <x-slot name="title">Leave Calendar</x-slot>

    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-display text-3xl font-semibold text-gray-900 dark:text-white">Leave Calendar</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Approved leave only. People shown here are unavailable for new task assignments.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('hr.calendar', ['month' => $prev]) }}" class="btn-ghost">&larr;</a>
                <span class="min-w-40 text-center font-display text-lg font-semibold">{{ $month->format('F Y') }}</span>
                <a href="{{ route('hr.calendar', ['month' => $next]) }}" class="btn-ghost">&rarr;</a>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-ink-700 dark:bg-ink-900 dark:text-gray-400">
                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dow)
                    <div class="py-2">{{ $dow }}</div>
                @endforeach
            </div>
            <div class="grid grid-cols-7">
                @php $firstDow = $month->dayOfWeekIso; @endphp
                @for ($i = 1; $i < $firstDow; $i++)
                    <div class="min-h-24 border-b border-r border-gray-100 bg-gray-50/50 dark:border-ink-800 dark:bg-ink-900/50"></div>
                @endfor

                @foreach ($days as $date => $leaves)
                    @php $d = \Carbon\Carbon::parse($date); @endphp
                    <div class="min-h-24 border-b border-r border-gray-100 p-1.5 dark:border-ink-800 {{ $d->isWeekend() ? 'bg-gray-50/50 dark:bg-ink-900/50' : '' }}">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold
                            {{ $d->isToday() ? 'bg-gold text-ink-950' : 'text-gray-500 dark:text-gray-400' }}">{{ $d->day }}</span>
                        <div class="mt-1 space-y-1">
                            @foreach ($leaves as $leave)
                                <div class="truncate rounded px-1.5 py-0.5 text-[11px] font-medium"
                                     style="background: {{ $leave->leaveType->name === 'Sick Leave' ? '#EF7C4A22' : '#E0A44E22' }}; color: {{ $leave->leaveType->name === 'Sick Leave' ? '#EF7C4A' : '#E0A44E' }}"
                                     title="{{ $leave->employee->user->name }} — {{ $leave->leaveType->name }}">
                                    {{ $leave->employee->user->initials() }} · {{ $leave->leaveType->name === 'Sick Leave' ? 'Sick' : 'Leave' }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
