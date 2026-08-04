@php use App\Support\Modules; @endphp

<x-guest-layout>
    <x-slot name="wide">true</x-slot>

    @php
        $labels = [
            'personal' => 'About you',
            'organisation' => 'Your organisation',
            'team' => 'Your team',
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

    {{-- ============ Step 3: personal information ============ --}}
    @if ($step === 'personal')
        <div class="text-center">
            <h1 class="font-display text-2xl font-semibold text-gray-900 dark:text-white">Welcome 👋</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                First, a little about you. This is what your team will see.
            </p>
        </div>

        <form method="POST" action="{{ route('onboarding.personal') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="label-app">Your full name *</label>
                <input name="name" required class="input-app" value="{{ old('name', auth()->user()->name) }}" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label-app">Job title</label>
                    <input name="job_title" class="input-app" placeholder="Founder"
                           value="{{ old('job_title', auth()->user()->employee?->job_title) }}" />
                </div>
                <div>
                    <label class="label-app">Phone</label>
                    <input name="phone" class="input-app" placeholder="Optional"
                           value="{{ old('phone', auth()->user()->employee?->phone) }}" />
                </div>
            </div>

            <button type="submit" class="btn-gold w-full justify-center py-2.5">Continue</button>
        </form>

    {{-- ============ Step 4: organisation setup ============ --}}
    @elseif ($step === 'organisation')
        <div class="text-center">
            <h1 class="font-display text-2xl font-semibold text-gray-900 dark:text-white">Set up your organisation</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Everything here has a sensible default — change what matters and move on.
            </p>
        </div>

        <form method="POST" action="{{ route('onboarding.organisation') }}" class="mt-6 space-y-6"
              x-data="{ leaveRows: {{ max($leaveTypes->count(), 3) }}, catRows: {{ max($categories->count(), 4) }} }">
            @csrf

            {{-- Name, industry, size --}}
            <div class="space-y-4">
                <div>
                    <label class="label-app">Organisation name *</label>
                    <input name="organization_name" required class="input-app"
                           value="{{ old('organization_name', $organization->name) }}" />
                    <x-input-error :messages="$errors->get('organization_name')" class="mt-2" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label-app">Industry *</label>
                        <select name="industry" required class="input-app">
                            <option value="">Choose…</option>
                            @foreach (['Professional services', 'Software & technology', 'Non-profit & charity', 'Retail & e-commerce', 'Manufacturing', 'Healthcare', 'Education', 'Other'] as $ind)
                                <option value="{{ $ind }}" @selected(old('industry', $organization->industry) === $ind)>{{ $ind }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('industry')" class="mt-2" />
                    </div>
                    <div>
                        <label class="label-app">Team size *</label>
                        <select name="team_size" required class="input-app">
                            @foreach (['1-5', '6-25', '26-100', '100+'] as $size)
                                <option value="{{ $size }}" @selected(old('team_size', $organization->team_size) === $size)>{{ $size }} people</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Plan --}}
            <div>
                <label class="label-app">Plan</label>
                <div class="grid gap-2 sm:grid-cols-3">
                    @foreach ([
                        'free' => ['Free', 'Up to 5 people'],
                        'starter' => ['Starter', 'Up to 25 people'],
                        'pro' => ['Pro', 'Unlimited'],
                    ] as $value => [$name, $detail])
                        <label class="cursor-pointer">
                            <input type="radio" name="plan_tier" value="{{ $value }}" class="peer sr-only"
                                   @checked(old('plan_tier', $organization->plan_tier === 'trial' ? 'starter' : $organization->plan_tier) === $value)>
                            <span class="block rounded-xl border-2 border-gray-200 p-3 text-center transition peer-checked:border-gold peer-checked:bg-gold-soft dark:border-ink-600">
                                <span class="block text-sm font-semibold">{{ $name }}</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $detail }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">You're on a {{ \App\Models\Organization::TRIAL_DAYS }}-day trial — no card needed, and you can change plan later.</p>
            </div>

            {{-- Pipeline (fixed for now, shown so the user knows what they get) --}}
            <div>
                <label class="label-app">Sales pipeline</label>
                <div class="flex flex-wrap gap-1.5">
                    @foreach (\App\Models\Deal::STAGES as $stage)
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium capitalize text-gray-600 dark:bg-ink-800 dark:text-gray-300">{{ $stage }}</span>
                    @endforeach
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">The standard five-stage pipeline. Deals move left to right on the board.</p>
            </div>

            {{-- Leave types --}}
            <div>
                <label class="label-app">Leave types &amp; annual allowance</label>
                <div class="space-y-2">
                    @for ($i = 0; $i < 6; $i++)
                        @php $existing = $leaveTypes[$i] ?? null; @endphp
                        <div class="flex gap-2" x-show="{{ $i }} < leaveRows" @if ($i >= 3 && ! $existing) style="display:none" @endif>
                            <input name="leave_types[{{ $i }}][name]" class="input-app flex-1"
                                   placeholder="e.g. Annual Leave" value="{{ $existing?->name }}" />
                            <input name="leave_types[{{ $i }}][days]" type="number" min="0" max="365"
                                   class="input-app !w-28" placeholder="days" value="{{ $existing?->default_allowance_days }}" />
                        </div>
                    @endfor
                </div>
                <button type="button" @click="leaveRows = Math.min(leaveRows + 1, 6)"
                        class="mt-2 text-xs font-semibold text-gold hover:underline">+ Add leave type</button>
            </div>

            {{-- Task categories --}}
            <div>
                <label class="label-app">Task categories</label>
                <div class="space-y-2">
                    @for ($i = 0; $i < 8; $i++)
                        @php $existing = $categories[$i] ?? null; @endphp
                        <div class="flex gap-2" x-show="{{ $i }} < catRows" @if ($i >= 4 && ! $existing) style="display:none" @endif>
                            <input name="categories[{{ $i }}][name]" class="input-app flex-1"
                                   placeholder="e.g. Delivery" value="{{ $existing?->name }}" />
                            <input name="categories[{{ $i }}][color]" type="color"
                                   class="h-10 w-14 cursor-pointer rounded-lg border-gray-300 dark:border-ink-600"
                                   value="{{ $existing?->color ?? '#E0A44E' }}" />
                        </div>
                    @endfor
                </div>
                <button type="button" @click="catRows = Math.min(catRows + 1, 8)"
                        class="mt-2 text-xs font-semibold text-gold hover:underline">+ Add category</button>
            </div>

            {{-- Starting point --}}
            <div>
                <label class="label-app">Starting point</label>
                <div class="space-y-2">
                    <label class="block cursor-pointer">
                        <input type="radio" name="starting_point" value="sample" class="peer sr-only" checked>
                        <div class="rounded-xl border-2 border-gray-200 p-3 transition peer-checked:border-gold peer-checked:bg-gold-soft dark:border-ink-600">
                            <p class="text-sm font-semibold">Explore with sample data <span class="ml-1 rounded-full bg-gold px-2 py-0.5 text-[10px] font-bold uppercase text-ink-950">Recommended</span></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">A few example deals, tasks and contacts. Ordinary data you can delete.</p>
                        </div>
                    </label>
                    <label class="block cursor-pointer">
                        <input type="radio" name="starting_point" value="blank" class="peer sr-only">
                        <div class="rounded-xl border-2 border-gray-200 p-3 transition peer-checked:border-gold peer-checked:bg-gold-soft dark:border-ink-600">
                            <p class="text-sm font-semibold">Start empty</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Nothing but your own data.</p>
                        </div>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn-gold w-full justify-center py-2.5">Continue</button>
        </form>

    {{-- ============ Step 5/6: people, roles and access ============ --}}
    @elseif ($step === 'team')
        <div class="text-center">
            <h1 class="font-display text-2xl font-semibold text-gray-900 dark:text-white">Add your team</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Choose what each person can do and see. They'll get an email invitation to set their own password.
            </p>
        </div>

        <form method="POST" action="{{ route('invitations.store') }}" class="mt-6 space-y-3">
            @csrf
            <div class="flex flex-wrap gap-2">
                <input name="email" type="email" required placeholder="colleague@company.com" class="input-app min-w-48 flex-1" />
                <select name="role" class="input-app !w-36">
                    <option value="employee">Employee</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div>
                <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Modules they can open</p>
                <div class="flex flex-wrap gap-2">
                    @foreach (Modules::catalogue() as $key => $meta)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="modules[]" value="{{ $key }}" class="peer sr-only" checked>
                            <span class="block rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium transition peer-checked:border-gold peer-checked:bg-gold-soft peer-checked:text-gold dark:border-ink-600">
                                {{ $meta['name'] }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="btn-ghost w-full justify-center">Send invitation</button>
            <x-input-error :messages="$errors->get('email')" />
        </form>

        @if ($invitations->isNotEmpty())
            <div class="mt-4 space-y-1.5">
                @foreach ($invitations as $inv)
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm dark:bg-ink-800">
                        <span class="truncate">{{ $inv->email }}</span>
                        <span class="ml-2 shrink-0 text-xs capitalize text-gray-500 dark:text-gray-400">
                            {{ $inv->role }} · {{ $inv->modules ? count($inv->modules).' modules' : 'all modules' }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-6 rounded-xl bg-gold-soft p-3 text-xs leading-relaxed text-gray-600 dark:text-gray-300">
            <strong class="text-gold">Roles</strong> decide how much someone can do — employees see their own work, managers see the whole team, admins manage settings and payslips.
            <strong class="text-gold">Modules</strong> decide which parts of the product they can open. You can change both later from the Team page.
        </div>

        <form method="POST" action="{{ route('onboarding.team') }}" class="mt-4">
            @csrf
            <button type="submit" class="btn-gold w-full justify-center py-2.5">
                {{ $invitations->isNotEmpty() ? 'Finish setup' : 'Skip for now and finish' }}
            </button>
        </form>
    @endif
</x-guest-layout>
