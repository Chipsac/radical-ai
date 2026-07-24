<x-app-layout>
    <x-slot name="title">Task Categories</x-slot>

    <div class="mx-auto max-w-3xl">
        <h1 class="font-display text-3xl font-semibold text-gray-900 dark:text-white">Categories</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Colour-coded groupings used across the task board. The prefix feeds into task reference numbers.</p>

        <div class="card mt-6 divide-y divide-gray-100 dark:divide-ink-700">
            @foreach ($categories as $cat)
                <div class="flex items-center gap-4 px-5 py-3">
                    <span class="h-4 w-4 rounded-full" style="background: {{ $cat->color }}"></span>
                    <span class="flex-1 text-sm font-medium">{{ $cat->name }}</span>
                    @if ($cat->prefix)
                        <span class="rounded bg-gray-100 px-2 py-0.5 font-mono text-xs text-gray-600 dark:bg-ink-700 dark:text-gray-300">{{ $cat->prefix }}</span>
                    @endif
                    <span class="text-xs text-gray-400">{{ $cat->tasks_count }} tasks</span>
                </div>
            @endforeach
        </div>

        @if (auth()->user()->isManager())
            <div class="card mt-6 p-6">
                <h2 class="font-display text-lg font-semibold">Add category</h2>
                <form method="POST" action="{{ route('tasks.categories.store') }}" class="mt-4 flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="min-w-48 flex-1">
                        <label class="label-app">Name *</label>
                        <input name="name" required class="input-app" placeholder="e.g. Partnerships" />
                    </div>
                    <div>
                        <label class="label-app">Colour</label>
                        <input type="color" name="color" value="#E0A44E" class="h-10 w-14 cursor-pointer rounded-lg border-gray-300 dark:border-ink-600" />
                    </div>
                    <div class="w-28">
                        <label class="label-app">Prefix</label>
                        <input name="prefix" maxlength="8" class="input-app" placeholder="PT" />
                    </div>
                    <button type="submit" class="btn-gold">Add</button>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
