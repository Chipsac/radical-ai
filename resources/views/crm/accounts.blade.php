<x-app-layout>
    <x-slot name="title">Accounts</x-slot>

    <div class="mx-auto max-w-5xl" x-data="{ showNew: false }">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-display text-3xl font-semibold text-gray-900 dark:text-white">Accounts</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Companies you sell to.</p>
            </div>
            <button @click="showNew = true" class="btn-gold">+ Add account</button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($accounts as $account)
                <div class="card p-5 transition hover:border-gold/60">
                    <h2 class="font-display text-lg font-semibold">{{ $account->name }}</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $account->industry }}</p>
                    @if ($account->website)
                        <p class="mt-1 truncate text-xs text-gold">{{ $account->website }}</p>
                    @endif
                    <div class="mt-4 flex gap-4 border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-ink-700 dark:text-gray-400">
                        <span><strong class="text-gray-900 dark:text-white">{{ $account->contacts_count }}</strong> contacts</span>
                        <span><strong class="text-gray-900 dark:text-white">{{ $account->deals_count }}</strong> deals</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div x-show="showNew" x-transition.opacity class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-black/60 p-4 pt-16" style="display: none;">
            <div @click.outside="showNew = false" class="card w-full max-w-md p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-display text-xl font-semibold">Add account</h2>
                    <button @click="showNew = false" class="text-2xl leading-none text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <form method="POST" action="{{ route('crm.accounts.store') }}" class="space-y-4">
                    @csrf
                    <div><label class="label-app">Name *</label><input name="name" required class="input-app" /></div>
                    <div><label class="label-app">Website</label><input name="website" class="input-app" placeholder="https://…" /></div>
                    <div><label class="label-app">Industry</label><input name="industry" class="input-app" /></div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showNew = false" class="btn-ghost">Cancel</button>
                        <button type="submit" class="btn-gold">Add account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
