<x-app-layout>
    <x-slot name="title">Teams</x-slot>

    <div class="mx-auto max-w-5xl" x-data="{ creating: false }">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="font-display text-3xl font-semibold text-gray-900 dark:text-white">Teams</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Group people so they can see each other's leave, and set how many need to be in.
                </p>
            </div>
            <button @click="creating = !creating" class="btn-gold">+ New team</button>
        </div>

        <x-help-banner id="tip:teams"
            title="Minimum cover is a warning, not a rule"
            text="Set how many people a team needs at work on a working day. When someone requests leave that would drop the team below that, the approver sees a warning — but can still approve. Sick leave is unplanned and small teams sometimes have no choice, so blocking outright would cause more problems than it solves." />

        {{-- Create --}}
        <form x-show="creating" x-cloak method="POST" action="{{ route('hr.teams.store') }}" class="card mt-6 space-y-4 p-6">
            @csrf
            <h2 class="font-display text-lg font-semibold">New team</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label-app">Name *</label>
                    <input name="name" required class="input-app" placeholder="e.g. Delivery" />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <label class="label-app">Team lead</label>
                    <select name="lead_id" class="input-app">
                        <option value="">Nobody yet</option>
                        @foreach ($candidates as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="label-app">Description</label>
                    <input name="description" class="input-app" placeholder="Optional" />
                </div>
                <div>
                    <label class="label-app">Minimum people in on a working day</label>
                    <input type="number" name="minimum_cover" min="0" max="50" value="1" class="input-app !w-32" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">0 turns the warning off for this team.</p>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" @click="creating = false" class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-gold">Create team</button>
            </div>
        </form>

        {{-- Teams --}}
        <div class="mt-6 space-y-4">
            @forelse ($teams as $team)
                @php $cover = $coverage[$team->id]; @endphp
                <div class="card p-6" x-data="{ editing: false }">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-display text-lg font-semibold">{{ $team->name }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $team->employees->count() }} {{ Str::plural('member', $team->employees->count()) }}
                                @if ($team->lead) · led by {{ $team->lead->name }} @endif
                                @if ($team->description) · {{ $team->description }} @endif
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            @if ($cover['weekend'])
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-500 dark:bg-ink-700 dark:text-gray-300">Weekend</span>
                            @elseif ($team->minimum_cover === 0)
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-500 dark:bg-ink-700 dark:text-gray-300">No cover rule</span>
                            @elseif ($cover['covered'])
                                <span class="rounded-full bg-status-done/15 px-2.5 py-1 text-xs font-semibold text-status-done">
                                    {{ $cover['present'] }} in today
                                </span>
                            @else
                                <span class="rounded-full bg-priority-high/15 px-2.5 py-1 text-xs font-semibold text-priority-high">
                                    Below cover — {{ $cover['present'] }} of {{ $cover['required'] }}
                                </span>
                            @endif
                            <button @click="editing = !editing" class="text-xs font-medium text-gray-400 hover:text-gold">Edit</button>
                        </div>
                    </div>

                    {{-- Edit --}}
                    <form x-show="editing" x-cloak method="POST" action="{{ route('hr.teams.update', $team) }}"
                          class="mt-4 grid gap-3 rounded-xl bg-gray-50 p-4 sm:grid-cols-4 dark:bg-ink-800">
                        @csrf @method('PATCH')
                        <input name="name" value="{{ $team->name }}" required class="input-app sm:col-span-1" />
                        <input name="description" value="{{ $team->description }}" placeholder="Description" class="input-app sm:col-span-1" />
                        <select name="lead_id" class="input-app">
                            <option value="">No lead</option>
                            @foreach ($candidates as $c)
                                <option value="{{ $c->id }}" @selected($team->lead_id === $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <div class="flex gap-2">
                            <input type="number" name="minimum_cover" min="0" max="50" value="{{ $team->minimum_cover }}"
                                   title="Minimum people in on a working day" class="input-app !w-20" />
                            <button type="submit" class="btn-gold !py-1.5 text-sm">Save</button>
                        </div>
                    </form>

                    {{-- Members --}}
                    <div class="mt-4 divide-y divide-gray-100 dark:divide-ink-700">
                        @forelse ($team->employees as $employee)
                            <div class="flex items-center gap-3 py-2.5">
                                <x-avatar :user="$employee->user" size="sm" />
                                <span class="min-w-0 flex-1 truncate text-sm">
                                    {{ $employee->user?->name }}
                                    <span class="text-xs text-gray-500 dark:text-gray-400">· {{ $employee->job_title }}</span>
                                </span>
                                @if ($employee->isOnLeave())
                                    <span class="rounded-full bg-priority-high/15 px-2 py-0.5 text-[11px] font-semibold text-priority-high">Off today</span>
                                @endif
                                <form method="POST" action="{{ route('hr.teams.assign', $employee) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="team_id" value="">
                                    <button type="submit" class="text-xs text-gray-400 hover:text-priority-high">Remove</button>
                                </form>
                            </div>
                        @empty
                            <p class="py-3 text-sm text-gray-500 dark:text-gray-400">No members yet.</p>
                        @endforelse
                    </div>
                </div>
            @empty
                <div class="card p-10 text-center">
                    <h2 class="font-display text-lg font-semibold">No teams yet</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                        Create a team to group people together. Team members can then see each other's
                        leave, and you get a warning before approving leave that would leave nobody in.
                    </p>
                </div>
            @endforelse
        </div>

        {{-- Unassigned --}}
        @if ($unassigned->isNotEmpty())
            <div class="card mt-6 p-6">
                <h2 class="font-display text-lg font-semibold">Not in a team ({{ $unassigned->count() }})</h2>
                <div class="mt-3 divide-y divide-gray-100 dark:divide-ink-700">
                    @foreach ($unassigned as $employee)
                        <div class="flex flex-wrap items-center gap-3 py-2.5">
                            <x-avatar :user="$employee->user" size="sm" />
                            <span class="min-w-0 flex-1 truncate text-sm">{{ $employee->user?->name }}</span>
                            <form method="POST" action="{{ route('hr.teams.assign', $employee) }}" class="flex gap-2">
                                @csrf @method('PATCH')
                                <select name="team_id" class="input-app !w-40 !py-1.5 text-xs">
                                    <option value="">Choose a team…</option>
                                    @foreach ($teams as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn-ghost !py-1.5 text-xs">Assign</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
