<x-guest-layout>
    <x-slot name="wide">true</x-slot>

    @php
        $labels = [
            'profile' => 'About you',
            'team' => 'Invite your team',
            'data' => 'Starting point',
        ];
        $stepIndex = array_search($step, \App\Models\Organization::ONBOARDING_STEPS, true) ?: 0;
    @endphp

    {{-- Progress rail --}}
    <div class="mb-8">
        <div class="mb-3 flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            <span>Step {{ $stepIndex + 1 }} of 3</span>
            <a href="{{ route('onboarding.skip') }}" class="font-medium normal-case tracking-normal text-gray-400 hover:text-gold">Skip setup</a>
        </div>
        <div class="h-1.5 overflow-hidden rounded-full bg-gray-200 dark:bg-ink-700">
            <div class="h-full rounded-full bg-gold transition-all duration-500" style="width: {{ max($progress, 8) }}%"></div>
        </div>
        <div class="mt-3 flex justify-between">
            @foreach ($labels as $key => $label)
                @php $i = array_search($key, \App\Models\Organization::ONBOARDING_STEPS, true); @endphp
                <span class="text-xs {{ $i <= $stepIndex ? 'font-semibold text-gold' : 'text-gray-400 dark:text-gray-500' }}">{{ $label }}</span>
            @endforeach
        </div>
    </div>

    {{-- ---------------- Step 1: profile ---------------- --}}
    @if ($step === 'profile')
        <div class="text-center">
            <h1 class="font-display text-2xl font-semibold text-gray-900 dark:text-white">Welcome, {{ explode(' ', auth()->user()->name)[0] }} 👋</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Two quick questions so we can tailor {{ $organization->name }} to how you work.
            </p>
        </div>

        <form method="POST" action="{{ route('onboarding.profile') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="label-app">What does your organisation do?</label>
                <select name="industry" required class="input-app">
                    <option value="">Choose an industry…</option>
                    @foreach (['Professional services', 'Software & technology', 'Non-profit & charity', 'Retail & e-commerce', 'Manufacturing', 'Healthcare', 'Education', 'Other'] as $ind)
                        <option value="{{ $ind }}" @selected(old('industry') === $ind)>{{ $ind }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('industry')" class="mt-2" />
            </div>

            <div>
                <label class="label-app">How many people will use it?</label>
                <div class="grid grid-cols-4 gap-2">
                    @foreach (['1-5', '6-25', '26-100', '100+'] as $size)
                        <label class="cursor-pointer">
                            <input type="radio" name="team_size" value="{{ $size }}" class="peer sr-only" required @checked(old('team_size') === $size)>
                            <span class="block rounded-xl border border-gray-300 py-2.5 text-center text-sm font-medium transition peer-checked:border-gold peer-checked:bg-gold-soft peer-checked:text-gold dark:border-ink-600">{{ $size }}</span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('team_size')" class="mt-2" />
            </div>

            <button type="submit" class="btn-gold w-full justify-center py-2.5">Continue</button>
        </form>

    {{-- ---------------- Step 2: team ---------------- --}}
    @elseif ($step === 'team')
        <div class="text-center">
            <h1 class="font-display text-2xl font-semibold text-gray-900 dark:text-white">Invite your team</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Radical AI works best with your whole team in it. You can always do this later.
            </p>
        </div>

        <form method="POST" action="{{ route('invitations.store') }}" class="mt-6 space-y-3">
            @csrf
            <div class="flex gap-2">
                <input name="email" type="email" required placeholder="colleague@company.com" class="input-app flex-1" />
                <select name="role" class="input-app !w-36">
                    <option value="employee">Employee</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit" class="btn-ghost whitespace-nowrap">Invite</button>
            </div>
            <x-input-error :messages="$errors->get('email')" />
        </form>

        @if ($invitations->isNotEmpty())
            <div class="mt-4 space-y-1.5">
                @foreach ($invitations as $inv)
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm dark:bg-ink-800">
                        <span>{{ $inv->email }}</span>
                        <span class="text-xs capitalize text-gray-500 dark:text-gray-400">{{ $inv->role }} · {{ $inv->status() }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-6 rounded-xl bg-gold-soft p-3 text-xs text-gray-600 dark:text-gray-300">
            <strong class="text-gold">Roles:</strong> Admins manage settings and payslips · Managers approve leave and see the whole team · Employees see their own work.
        </div>

        <form method="POST" action="{{ route('onboarding.team') }}" class="mt-4">
            @csrf
            <button type="submit" class="btn-gold w-full justify-center py-2.5">
                {{ $invitations->isNotEmpty() ? 'Continue' : 'Skip for now' }}
            </button>
        </form>

    {{-- ---------------- Step 3: data ---------------- --}}
    @elseif ($step === 'data')
        <div class="text-center">
            <h1 class="font-display text-2xl font-semibold text-gray-900 dark:text-white">How would you like to start?</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                You can change this at any time — sample data is ordinary data you can delete.
            </p>
        </div>

        <form method="POST" action="{{ route('onboarding.data') }}" class="mt-6 space-y-3">
            @csrf
            <label class="block cursor-pointer">
                <input type="radio" name="starting_point" value="sample" class="peer sr-only" checked>
                <div class="rounded-xl border-2 border-gray-200 p-4 transition peer-checked:border-gold peer-checked:bg-gold-soft dark:border-ink-600">
                    <p class="font-semibold">Explore with sample data <span class="ml-1 rounded-full bg-gold px-2 py-0.5 text-[10px] font-bold uppercase text-ink-950">Recommended</span></p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">A few example deals, tasks and contacts so you can see how everything fits together.</p>
                </div>
            </label>

            <label class="block cursor-pointer">
                <input type="radio" name="starting_point" value="blank" class="peer sr-only">
                <div class="rounded-xl border-2 border-gray-200 p-4 transition peer-checked:border-gold peer-checked:bg-gold-soft dark:border-ink-600">
                    <p class="font-semibold">Start with an empty workspace</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Nothing but your own data. Best if you're rolling out to the team straight away.</p>
                </div>
            </label>

            <button type="submit" class="btn-gold w-full justify-center py-2.5">Finish setup</button>
        </form>
    @endif
</x-guest-layout>
