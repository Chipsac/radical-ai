<x-app-layout>
    <x-slot name="title">Settings</x-slot>

    <div class="mx-auto max-w-3xl" x-data="{ tab: 'organization' }">
        <h1 class="font-display text-3xl font-semibold text-gray-900 dark:text-white">Settings</h1>

        <div class="mt-6 flex gap-1 border-b border-gray-200 dark:border-ink-700">
            @foreach (['organization' => 'Organization', 'billing' => 'Billing', 'integrations' => 'Integrations'] as $key => $label)
                <button @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'border-gold text-gold' : 'border-transparent text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'"
                        class="border-b-2 px-4 py-2.5 text-sm font-semibold transition">{{ $label }}</button>
            @endforeach
        </div>

        <div x-show="tab === 'organization'" class="card mt-6 p-6">
            <h2 class="font-display text-lg font-semibold">Organization</h2>
            <div class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Name</span><span class="font-medium">{{ $organization->name }}</span></p>
                <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Slug</span><span class="font-mono text-xs">{{ $organization->slug }}</span></p>
                <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Plan tier</span><span class="font-medium capitalize">{{ $organization->plan_tier }}</span></p>
                <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Members</span><span class="font-medium">{{ $organization->users()->count() }}</span></p>
            </div>
        </div>

        <div x-show="tab === 'billing'" class="card mt-6 p-6" style="display: none;">
            <h2 class="font-display text-lg font-semibold">Billing</h2>
            <div class="mt-4 rounded-xl border border-gold/40 bg-gold-soft p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-display text-xl font-semibold capitalize">{{ $organization->plan_tier }} plan</p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">All modules · up to 25 seats · community support</p>
                    </div>
                    <button type="button" disabled
                            class="cursor-not-allowed rounded-lg bg-gray-300 px-4 py-2 text-sm font-semibold text-gray-500 dark:bg-ink-600 dark:text-gray-400"
                            title="Billing is stubbed in the prototype">
                        Manage subscription
                    </button>
                </div>
            </div>
            <p class="mt-3 text-xs text-gray-400">Prototype note: billing is intentionally non-functional (no Stripe).</p>
        </div>

        <div x-show="tab === 'integrations'" class="card mt-6 p-6" style="display: none;">
            <h2 class="font-display text-lg font-semibold">Integrations</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Native payroll and Revenue (ROS) integration are planned for Phase 3 and are not part of this prototype. Payslips are uploaded as PDFs by an admin.</p>
        </div>
    </div>
</x-app-layout>
