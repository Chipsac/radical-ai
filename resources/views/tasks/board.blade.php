<x-app-layout>
    <x-slot name="title">Task Board</x-slot>

    <div x-data="{ showNew: false }">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-display text-3xl font-semibold text-gray-900 dark:text-white">Task Board</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Drag a card between columns — the change is saved instantly.</p>
            </div>
            <div class="flex items-center gap-2">
                <form method="get" data-tour="board-filters" class="flex items-center gap-2">
                    <select name="category" onchange="this.form.submit()" class="input-app !w-44">
                        <option value="">All categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <select name="assignee" onchange="this.form.submit()" class="input-app !w-44">
                        <option value="">Everyone</option>
                        @foreach ($assignees as $a)
                            <option value="{{ $a->id }}" @selected(request('assignee') == $a->id)>{{ $a->name }}</option>
                        @endforeach
                    </select>
                </form>
                <button @click="showNew = true" data-tour="new-task" class="btn-gold">+ New task</button>
            </div>
        </div>

        <x-help-banner id="tip:board"
            title="Drag cards to move work along"
            text="Pick up any card and drop it in another column — the status saves straight away, with no save button. Click a card to open it, post a progress update or log time against it." />

        <div data-tour="board-columns" class="grid gap-4 overflow-x-auto pb-4 lg:grid-cols-5">
            @foreach ($columns as $status => $tasks)
                <div class="min-w-[240px] rounded-2xl bg-gray-50 p-3 dark:bg-ink-900">
                    <div class="mb-3 flex items-center justify-between px-1">
                        <h2 class="flex items-center gap-2 text-sm font-semibold">
                            <span class="h-2.5 w-2.5 rounded-full" style="background: {{ \App\Models\Task::STATUS_COLORS[$status] }}"></span>
                            {{ \App\Models\Task::STATUS_LABELS[$status] }}
                        </h2>
                        <span data-count-for="{{ $status }}"
                              class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-ink-700 dark:text-gray-300">{{ $tasks->count() }}</span>
                    </div>
                    <div class="min-h-[120px] space-y-2"
                         data-kanban-column="{{ $status }}"
                         data-kanban-group="tasks"
                         data-field="status"
                         data-update-url-template="{{ route('tasks.status', ':id') }}">
                        @foreach ($tasks as $task)
                            <x-task-card :task="$task" />
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ---------------- New task modal ---------------- --}}
        <div x-show="showNew" x-transition.opacity class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-black/60 p-4 pt-16" style="display: none;">
            <div @click.outside="showNew = false" class="card w-full max-w-xl p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-display text-xl font-semibold">New task</h2>
                    <button @click="showNew = false" class="text-2xl leading-none text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <form method="POST" action="{{ route('tasks.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="label-app">Title *</label>
                        <input name="title" required class="input-app" placeholder="What needs doing?" />
                    </div>
                    <div>
                        <label class="label-app">Description</label>
                        <textarea name="description" rows="3" class="input-app" placeholder="Optional context…"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label-app">Status</label>
                            <select name="status" class="input-app">
                                @foreach (\App\Models\Task::STATUSES as $s)
                                    <option value="{{ $s }}" @selected($s === 'to_do')>{{ \App\Models\Task::STATUS_LABELS[$s] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label-app">Priority</label>
                            <select name="priority" class="input-app">
                                @foreach (\App\Models\Task::PRIORITIES as $p)
                                    <option value="{{ $p }}" @selected($p === 'medium')>{{ ucfirst($p) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label-app">Assignee</label>
                            <select name="assignee_id" class="input-app">
                                <option value="">Unassigned</option>
                                @foreach ($assignees as $a)
                                    <option value="{{ $a->id }}" @selected($a->id === auth()->id())>{{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label-app">Category</label>
                            <select name="category_id" class="input-app">
                                <option value="">None</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label-app">Start date</label>
                            <input type="date" name="start_date" class="input-app" value="{{ now()->toDateString() }}" />
                        </div>
                        <div>
                            <label class="label-app">Due date</label>
                            <input type="date" name="due_date" class="input-app" />
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_continuous" value="1" class="rounded border-gray-300 text-gold focus:ring-gold" />
                        Continuous task (no fixed end)
                    </label>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showNew = false" class="btn-ghost">Cancel</button>
                        <button type="submit" class="btn-gold">Create task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
