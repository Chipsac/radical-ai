<x-app-layout>
    <x-slot name="title">Leads</x-slot>

    <div class="mx-auto max-w-5xl" x-data="{ showNew: false }">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-display text-3xl font-semibold text-gray-900 dark:text-white">Leads</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Early-stage prospects that haven't become deals yet.</p>
            </div>
            <button @click="showNew = true" class="btn-gold">+ Add lead</button>
        </div>

        <div class="card overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500 dark:border-ink-700 dark:text-gray-400">
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3">Company</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Source</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Owner</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-ink-700">
                    @forelse ($leads as $lead)
                        <tr class="transition hover:bg-gray-50 dark:hover:bg-ink-800">
                            <td class="px-5 py-3 font-medium">{{ $lead->name }}</td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $lead->company_name }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $lead->email }}</td>
                            <td class="px-5 py-3 capitalize text-gray-500 dark:text-gray-400">{{ $lead->source }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold
                                    {{ $lead->status === 'qualified' ? 'bg-status-done/20 text-status-done' : ($lead->status === 'contacted' ? 'bg-status-todo/20 text-status-todo' : 'bg-gray-100 text-gray-500 dark:bg-ink-700 dark:text-gray-300') }}">
                                    {{ $lead->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3"><x-avatar :user="$lead->owner" size="sm" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">No leads yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div x-show="showNew" x-transition.opacity class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-black/60 p-4 pt-16" style="display: none;">
            <div @click.outside="showNew = false" class="card w-full max-w-md p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-display text-xl font-semibold">Add lead</h2>
                    <button @click="showNew = false" class="text-2xl leading-none text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <form method="POST" action="{{ route('crm.leads.store') }}" class="space-y-4">
                    @csrf
                    <div><label class="label-app">Name *</label><input name="name" required class="input-app" /></div>
                    <div><label class="label-app">Company</label><input name="company_name" class="input-app" /></div>
                    <div><label class="label-app">Email</label><input type="email" name="email" class="input-app" /></div>
                    <div>
                        <label class="label-app">Source</label>
                        <select name="source" class="input-app">
                            @foreach (['website', 'referral', 'conference', 'linkedin', 'other'] as $s)
                                <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showNew = false" class="btn-ghost">Cancel</button>
                        <button type="submit" class="btn-gold">Add lead</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
