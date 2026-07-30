<x-app-layout>
    <x-slot name="title">Deal Pipeline</x-slot>

    <div x-data="{ showNew: false }">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-display text-3xl font-semibold text-gray-900 dark:text-white">Deal Pipeline</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Drag deals between stages — changes persist. Open a deal to mark it won.</p>
            </div>
            <button @click="showNew = true" data-tour="new-deal" class="btn-gold">+ New deal</button>
        </div>

        <x-help-banner id="tip:pipeline"
            title="Your pipeline, end to end"
            text="Drag deals between stages as they progress. When you open a deal and mark it won, Radical AI creates the delivery project and onboarding tasks for you — assigned only to people who aren't on leave." />

        <div data-tour="pipeline-columns" class="grid gap-4 overflow-x-auto pb-4 lg:grid-cols-5">
            @foreach ($columns as $stage => $deals)
                <div class="min-w-[240px] rounded-2xl bg-gray-50 p-3 dark:bg-ink-900">
                    <div class="mb-3 flex items-center justify-between px-1">
                        <h2 class="text-sm font-semibold capitalize {{ $stage === 'won' ? 'text-status-done' : ($stage === 'lost' ? 'text-gray-400' : '') }}">{{ $stage }}</h2>
                        <div class="text-right">
                            <span data-count-for="{{ $stage }}"
                                  class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-ink-700 dark:text-gray-300">{{ $deals->count() }}</span>
                        </div>
                    </div>
                    <p class="mb-2 px-1 text-xs text-gray-400">€{{ number_format($deals->sum('value'), 0) }}</p>
                    <div class="min-h-[120px] space-y-2"
                         data-kanban-column="{{ $stage }}"
                         data-kanban-group="deals"
                         data-field="stage"
                         data-update-url-template="{{ route('crm.deals.stage', ':id') }}">
                        @foreach ($deals as $deal)
                            <a href="{{ route('crm.deals.show', $deal) }}" data-id="{{ $deal->id }}"
                               class="block cursor-grab rounded-xl border border-gray-200 bg-white p-3 shadow-sm transition hover:border-gold/60 dark:border-ink-600 dark:bg-ink-800">
                                <p class="text-sm font-medium leading-snug">{{ $deal->title }}</p>
                                <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">{{ $deal->account?->name ?? 'No account' }}</p>
                                <div class="mt-2.5 flex items-center justify-between">
                                    <span class="font-display text-sm font-semibold text-gold">€{{ number_format($deal->value, 0) }}</span>
                                    <span class="flex items-center gap-2">
                                        @if ($deal->expected_close_date)
                                            <span class="text-[11px] text-gray-400">{{ $deal->expected_close_date->format('j M') }}</span>
                                        @endif
                                        <x-avatar :user="$deal->owner" size="sm" />
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- New deal modal --}}
        <div x-show="showNew" x-transition.opacity class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-black/60 p-4 pt-16" style="display: none;">
            <div @click.outside="showNew = false" class="card w-full max-w-lg p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-display text-xl font-semibold">New deal</h2>
                    <button @click="showNew = false" class="text-2xl leading-none text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <form method="POST" action="{{ route('crm.deals.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="label-app">Title *</label>
                        <input name="title" required class="input-app" placeholder="e.g. Website retainer" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label-app">Account</label>
                            <select name="account_id" class="input-app">
                                <option value="">None</option>
                                @foreach ($accounts as $a)
                                    <option value="{{ $a->id }}">{{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label-app">Contact</label>
                            <select name="contact_id" class="input-app">
                                <option value="">None</option>
                                @foreach ($contacts as $c)
                                    <option value="{{ $c->id }}">{{ $c->fullName() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label-app">Value (€) *</label>
                            <input type="number" name="value" min="0" step="100" required class="input-app" placeholder="10000" />
                        </div>
                        <div>
                            <label class="label-app">Expected close</label>
                            <input type="date" name="expected_close_date" class="input-app" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showNew = false" class="btn-ghost">Cancel</button>
                        <button type="submit" class="btn-gold">Create deal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
