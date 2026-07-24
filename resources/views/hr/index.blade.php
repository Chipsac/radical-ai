<x-app-layout>
    <x-slot name="title">Team Directory</x-slot>

    <div class="mb-6">
        <h1 class="font-display text-3xl font-semibold text-gray-900 dark:text-white">Team Directory</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $employees->count() }} people across {{ $byDepartment->count() }} departments.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($employees as $employee)
            <a href="{{ route('hr.show', $employee) }}" class="card p-5 transition hover:border-gold/60">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gold-soft text-sm font-bold text-gold">
                        {{ $employee->user?->initials() }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $employee->user?->name }}</p>
                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $employee->job_title }}</p>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 text-xs dark:border-ink-700">
                    <span class="text-gray-500 dark:text-gray-400">{{ $employee->department }}</span>
                    @if ($employee->isOnLeave())
                        <span class="rounded-full bg-priority-high/15 px-2 py-0.5 font-semibold text-priority-high">On leave</span>
                    @else
                        <span class="rounded-full bg-status-done/15 px-2 py-0.5 font-semibold text-status-done">Available</span>
                    @endif
                </div>
            </a>
        @endforeach
    </div>
</x-app-layout>
