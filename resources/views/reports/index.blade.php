<x-app-layout>
    <x-slot name="title">Reports</x-slot>

    <div class="mx-auto max-w-3xl">
        <h1 class="font-display text-3xl font-semibold text-gray-900 dark:text-white">Report generator</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Build a task progress report for selected team members. Open it in the browser or download it as an HTML file.</p>

        <form method="POST" action="{{ route('reports.generate') }}" target="_blank" class="card mt-6 p-6" x-data="{ download: false }">
            @csrf
            <h2 class="font-display text-lg font-semibold">Team members</h2>
            <div class="mt-3 grid gap-1 sm:grid-cols-2">
                @foreach ($members as $member)
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 transition hover:bg-gray-50 dark:hover:bg-ink-800">
                        <input type="checkbox" name="members[]" value="{{ $member->id }}"
                               class="rounded border-gray-300 text-gold focus:ring-gold"
                               @checked($member->id === auth()->id())>
                        <x-avatar :user="$member" size="sm" />
                        <span class="text-sm">{{ $member->name }}</span>
                    </label>
                @endforeach
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label-app">Progress updates per task</label>
                    <select name="updates_per_task" class="input-app">
                        @foreach ([1, 2, 3, 5] as $n)
                            <option value="{{ $n }}" @selected($n === 3)>Latest {{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="flex items-center gap-2 self-end pb-2 text-sm">
                    <input type="checkbox" name="include_completed" value="1" class="rounded border-gray-300 text-gold focus:ring-gold">
                    Include completed tasks
                </label>
            </div>

            <input type="hidden" name="download" :value="download ? 1 : 0">
            <div class="mt-6 flex justify-end gap-2">
                <button type="submit" @click="download = true" class="btn-ghost">Download HTML</button>
                <button type="submit" @click="download = false" class="btn-gold">Generate report</button>
            </div>
        </form>
    </div>
</x-app-layout>
