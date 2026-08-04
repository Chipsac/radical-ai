<!DOCTYPE html>
<html lang="en" class="scroll-smooth dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Radical AI — CRM, tasks and HR in one place</title>
    <meta name="description" content="Radical AI brings your sales pipeline, task tracking and HR into a single workspace, so your team stops re-entering the same people into three different systems.">

    <meta property="og:title" content="Radical AI — CRM, tasks and HR in one place">
    <meta property="og:description" content="One workspace for small teams. Win a deal and the project creates itself. Approve leave and the calendar updates everywhere.">
    <meta property="og:type" content="website">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|newsreader:500,600,700&display=swap" rel="stylesheet" />

    <script>
        if ((localStorage.getItem('theme') || 'dark') === 'light') {
            document.documentElement.classList.remove('dark');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-gray-900 antialiased dark:bg-ink-950 dark:text-gray-100">

{{-- ==================== Navigation ==================== --}}
<header x-data="{ open: false, scrolled: false }"
        @scroll.window="scrolled = window.scrollY > 20"
        class="fixed inset-x-0 top-0 z-50 transition"
        :class="scrolled ? 'border-b border-gray-200 bg-white/90 backdrop-blur dark:border-ink-700 dark:bg-ink-950/90' : ''">
    <nav class="mx-auto flex max-w-6xl items-center justify-between px-5 py-4">
        <a href="#top" class="font-display text-2xl font-semibold tracking-tight">
            Radical <span class="text-gold">AI</span>
        </a>

        <div class="hidden items-center gap-7 md:flex">
            <a href="#features" class="text-sm font-medium text-gray-600 transition hover:text-gold dark:text-gray-300">Features</a>
            <a href="#how" class="text-sm font-medium text-gray-600 transition hover:text-gold dark:text-gray-300">How it works</a>
            <a href="#pricing" class="text-sm font-medium text-gray-600 transition hover:text-gold dark:text-gray-300">Pricing</a>
            <a href="#faq" class="text-sm font-medium text-gray-600 transition hover:text-gold dark:text-gray-300">FAQ</a>
            <a href="#contact" class="text-sm font-medium text-gray-600 transition hover:text-gold dark:text-gray-300">Contact</a>
        </div>

        <div class="hidden items-center gap-3 md:flex">
            <button type="button" onclick="toggleTheme()" aria-label="Toggle light or dark theme"
                    class="rounded-full p-2 text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-ink-800">
                <svg class="hidden h-5 w-5 dark:block" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" /></svg>
                <svg class="h-5 w-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" /></svg>
            </button>
            <a href="{{ route('login') }}" class="text-sm font-semibold text-gray-700 transition hover:text-gold dark:text-gray-200">Log in</a>
            <a href="{{ route('register') }}" class="btn-gold">Start free trial</a>
        </div>

        {{-- Mobile toggle --}}
        <button @click="open = !open" class="rounded-lg p-2 text-gray-600 md:hidden dark:text-gray-300" aria-label="Open menu">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
        </button>
    </nav>

    {{-- Mobile menu --}}
    {{-- No x-transition here on purpose: a transition on this element can wedge
         at display:none if it is interrupted mid-toggle, leaving the menu
         permanently invisible even though its state says it is open. --}}
    <div x-show="open" x-cloak
         class="border-t border-gray-200 bg-white px-5 py-4 md:hidden dark:border-ink-700 dark:bg-ink-950">
        <div class="flex flex-col gap-3">
            @foreach (['features' => 'Features', 'how' => 'How it works', 'pricing' => 'Pricing', 'faq' => 'FAQ', 'contact' => 'Contact'] as $anchor => $label)
                <a href="#{{ $anchor }}" @click="open = false" class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ $label }}</a>
            @endforeach
            <div class="mt-2 flex gap-3">
                <a href="{{ route('login') }}" class="btn-ghost flex-1 justify-center">Log in</a>
                <a href="{{ route('register') }}" class="btn-gold flex-1 justify-center">Start free trial</a>
            </div>
        </div>
    </div>
</header>

{{-- ==================== Hero ==================== --}}
<section id="top" class="relative overflow-hidden pt-32 pb-20 sm:pt-40">
    {{-- Soft glow behind the headline --}}
    <div class="pointer-events-none absolute left-1/2 top-0 h-[400px] w-[700px] -translate-x-1/2 rounded-full opacity-20 blur-3xl"
         style="background: radial-gradient(circle, #E0A44E 0%, transparent 70%);"></div>

    <div class="relative mx-auto max-w-3xl px-5 text-center">
        <span class="inline-flex items-center gap-2 rounded-full border border-gold/40 bg-gold-soft px-3 py-1 text-xs font-semibold text-gold">
            <span class="h-1.5 w-1.5 rounded-full bg-gold"></span>
            Built for small teams
        </span>

        <h1 class="mt-6 font-display text-4xl font-semibold leading-tight tracking-tight sm:text-6xl">
            Your CRM, tasks and HR.<br>
            <span class="text-gold">One workspace.</span>
        </h1>

        <p class="mx-auto mt-5 max-w-xl text-lg leading-relaxed text-gray-600 dark:text-gray-300">
            Most small teams pay for three tools that never talk to each other. Radical AI puts sales,
            delivery and people in one place — so the person who wins the deal is the same person the
            project and the holiday calendar already know about.
        </p>

        <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <a href="{{ route('register') }}" class="btn-gold w-full justify-center px-6 py-3 text-base sm:w-auto">
                Start your free trial
            </a>
            <a href="#features" class="btn-ghost w-full justify-center px-6 py-3 text-base sm:w-auto">
                See what's inside
            </a>
        </div>

        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
            {{ \App\Models\Organization::TRIAL_DAYS }} days free · no card required · set up in minutes
        </p>
    </div>

    {{-- Product preview --}}
    <div class="relative mx-auto mt-14 max-w-5xl px-5">
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-ink-700 dark:bg-ink-900">
            {{-- Fake browser chrome --}}
            <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50 px-4 py-2.5 dark:border-ink-700 dark:bg-ink-850">
                <span class="h-2.5 w-2.5 rounded-full bg-gray-300 dark:bg-ink-600"></span>
                <span class="h-2.5 w-2.5 rounded-full bg-gray-300 dark:bg-ink-600"></span>
                <span class="h-2.5 w-2.5 rounded-full bg-gray-300 dark:bg-ink-600"></span>
                <span class="ml-3 rounded bg-white px-3 py-0.5 text-[11px] text-gray-400 dark:bg-ink-800">app.radical-ai.com/dashboard</span>
            </div>

            {{-- A simplified, honest impression of the dashboard --}}
            <div class="grid gap-4 p-5 sm:grid-cols-4">
                @foreach ([
                    ['Open deals', '12', '€145,200 in pipeline'],
                    ['Tasks due today', '4', 'across the team'],
                    ['On leave today', '1', 'Ella Nolan'],
                    ['Next payday', '28 Jul', '3 days away'],
                ] as [$label, $value, $sub])
                    <div class="rounded-xl border border-gray-200 p-4 dark:border-ink-700">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</p>
                        <p class="mt-1 font-display text-2xl font-semibold">{{ $value }}</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ $sub }}</p>
                    </div>
                @endforeach
            </div>
            <div class="grid gap-4 px-5 pb-5 sm:grid-cols-5">
                @foreach ([
                    ['Not Yet Started', '#8A8A8A', 3],
                    ['To Do List', '#3B82F6', 5],
                    ['In Progress', '#E0A44E', 4],
                    ['Review', '#A855F7', 2],
                    ['Done', '#22C55E', 6],
                ] as [$name, $color, $count])
                    <div class="rounded-xl bg-gray-50 p-3 dark:bg-ink-850">
                        <div class="mb-2 flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full" style="background: {{ $color }}"></span>
                            <span class="truncate text-[10px] font-semibold">{{ $name }}</span>
                        </div>
                        @for ($i = 0; $i < min($count, 3); $i++)
                            <div class="mb-1.5 rounded-lg border border-gray-200 bg-white p-2 dark:border-ink-600 dark:bg-ink-800">
                                <div class="h-1.5 w-3/4 rounded bg-gray-200 dark:bg-ink-600"></div>
                                <div class="mt-1.5 h-1.5 w-1/2 rounded bg-gray-100 dark:bg-ink-700"></div>
                            </div>
                        @endfor
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ==================== The problem ==================== --}}
<section class="border-y border-gray-200 bg-gray-50 py-16 dark:border-ink-800 dark:bg-ink-900">
    <div class="mx-auto max-w-4xl px-5 text-center">
        <h2 class="font-display text-2xl font-semibold sm:text-3xl">Three tools. One team. None of them talking.</h2>
        <p class="mx-auto mt-3 max-w-2xl text-gray-600 dark:text-gray-300">
            You close a deal in one system, set up the project in another, and find out in a third that
            half the team is on holiday that week. Every tool sees a slice. Nobody sees the whole picture.
        </p>

        <div class="mt-10 grid gap-4 text-left sm:grid-cols-3">
            @foreach ([
                ['Duplicate data entry', 'The same colleague exists three times, spelled three different ways.'],
                ['Nothing joins up', 'Winning work does not create the work. Someone has to remember to do that.'],
                ['Three bills, three logins', 'And three places to look when you just want a straight answer.'],
            ] as [$title, $body])
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-ink-700 dark:bg-ink-850">
                    <p class="font-semibold">{{ $title }}</p>
                    <p class="mt-1.5 text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $body }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ==================== Features ==================== --}}
<section id="features" class="py-20">
    <div class="mx-auto max-w-6xl px-5">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold uppercase tracking-widest text-gold">What's inside</p>
            <h2 class="mt-2 font-display text-3xl font-semibold sm:text-4xl">Three modules, one set of people</h2>
            <p class="mt-3 text-gray-600 dark:text-gray-300">
                Give each person access to only what they need — CRM for sales, the tracker for delivery,
                HR for everyone, or all three.
            </p>
        </div>

        <div class="mt-12 grid gap-6 lg:grid-cols-3">
            @foreach ([
                [
                    'CRM',
                    'Know where every deal stands',
                    '#E0A44E',
                    'M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941',
                    ['Drag deals through a five-stage pipeline', 'Leads, accounts and contacts in one view', 'Live forecast by stage and value', 'Mark a deal won and the project appears'],
                ],
                [
                    'Daily tracker',
                    'See what the team is actually doing',
                    '#3B82F6',
                    'M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125Z',
                    ['Kanban board that saves as you drag', 'Progress threads on every task', 'Automatic reference numbers', 'Weekly status reports, generated'],
                ],
                [
                    'HR & payslips',
                    'Leave, people and pay, handled',
                    '#22C55E',
                    'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
                    ['Request and approve leave in a click', 'Shared availability calendar', 'Team directory with live status', 'Payslips only their owner can open'],
                ],
            ] as [$tag, $title, $color, $icon, $points])
                <div class="group rounded-2xl border border-gray-200 bg-white p-7 transition hover:border-gold/60 hover:shadow-lg dark:border-ink-700 dark:bg-ink-900">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl" style="background: {{ $color }}1f">
                        <svg class="h-6 w-6" style="color: {{ $color }}" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                        </svg>
                    </span>
                    <p class="mt-4 text-xs font-bold uppercase tracking-widest" style="color: {{ $color }}">{{ $tag }}</p>
                    <h3 class="mt-1 font-display text-xl font-semibold">{{ $title }}</h3>
                    <ul class="mt-4 space-y-2.5">
                        @foreach ($points as $point)
                            <li class="flex items-start gap-2.5 text-sm text-gray-600 dark:text-gray-300">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-gold" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                <span>{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ==================== The glue ==================== --}}
<section class="border-y border-gray-200 bg-gray-50 py-20 dark:border-ink-800 dark:bg-ink-900">
    <div class="mx-auto max-w-6xl px-5">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold uppercase tracking-widest text-gold">Why one workspace matters</p>
            <h2 class="mt-2 font-display text-3xl font-semibold sm:text-4xl">The modules do each other's homework</h2>
            <p class="mt-3 text-gray-600 dark:text-gray-300">
                This is the part you cannot get by buying three separate tools.
            </p>
        </div>

        <div class="mt-12 space-y-4">
            @foreach ([
                ['Win a deal', 'the project builds itself', 'Mark a deal won and Radical AI creates the delivery project with its onboarding tasks — assigned only to people who are not on approved leave that week.'],
                ['Approve leave', 'everyone knows immediately', 'One approval updates the shared calendar, flips that person to unavailable in the directory, and takes them out of the pool for new work.'],
                ['Log your hours', 'utilisation appears', 'Time logged against tasks rolls straight up into each person\'s HR profile, so you can see who is stretched without asking.'],
            ] as $i => [$trigger, $result, $detail])
                <div class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-6 sm:flex-row sm:items-center dark:border-ink-700 dark:bg-ink-850">
                    <div class="flex shrink-0 items-center gap-3 sm:w-80">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gold text-sm font-bold text-ink-950">{{ $i + 1 }}</span>
                        <p class="font-display text-lg font-semibold">
                            {{ $trigger }} <span class="text-gold">→</span> {{ $result }}
                        </p>
                    </div>
                    <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $detail }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ==================== How it works ==================== --}}
<section id="how" class="py-20">
    <div class="mx-auto max-w-6xl px-5">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold uppercase tracking-widest text-gold">Getting started</p>
            <h2 class="mt-2 font-display text-3xl font-semibold sm:text-4xl">Up and running this afternoon</h2>
        </div>

        <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Sign up', 'Create your workspace with an email address. No card, no sales call.'],
                ['Set it up', 'A guided wizard walks you through your organisation, leave types and categories.'],
                ['Invite the team', 'Send invitations and choose what each person can see — role and modules.'],
                ['Get to work', 'Start with sample data to explore, or a clean workspace if you are ready.'],
            ] as $i => [$title, $body])
                <div class="relative">
                    <span class="font-display text-5xl font-semibold text-gold/25">0{{ $i + 1 }}</span>
                    <h3 class="mt-1 font-display text-lg font-semibold">{{ $title }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $body }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-12 text-center">
            <a href="{{ route('register') }}" class="btn-gold px-6 py-3 text-base">Create your workspace</a>
        </div>
    </div>
</section>

{{-- ==================== Security ==================== --}}
<section class="border-y border-gray-200 bg-gray-50 py-16 dark:border-ink-800 dark:bg-ink-900">
    <div class="mx-auto max-w-6xl px-5">
        <div class="grid items-center gap-10 lg:grid-cols-2">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-gold">Built to be trusted</p>
                <h2 class="mt-2 font-display text-3xl font-semibold">Your data stays yours</h2>
                <p class="mt-3 leading-relaxed text-gray-600 dark:text-gray-300">
                    Payslips and personal details are the most sensitive things a small business holds.
                    We treat access control as a feature, not an afterthought — every rule is enforced on
                    the server, not just hidden in the interface.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    ['Separated by organisation', 'Every record is scoped to your workspace, and the check fails closed.'],
                    ['Two-factor authentication', 'Standard authenticator apps, with single-use recovery codes.'],
                    ['Payslips stay private', 'Only their owner can open one — checked on every download.'],
                    ['Full audit trail', 'Logins, role changes and access grants are all recorded.'],
                ] as [$title, $body])
                    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-ink-700 dark:bg-ink-850">
                        <p class="text-sm font-semibold">{{ $title }}</p>
                        <p class="mt-1 text-xs leading-relaxed text-gray-600 dark:text-gray-400">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ==================== Pricing ==================== --}}
<section id="pricing" class="py-20">
    <div class="mx-auto max-w-6xl px-5">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold uppercase tracking-widest text-gold">Pricing</p>
            <h2 class="mt-2 font-display text-3xl font-semibold sm:text-4xl">Simple, per workspace</h2>
            <p class="mt-3 text-gray-600 dark:text-gray-300">
                Every plan includes all three modules. Start free for {{ \App\Models\Organization::TRIAL_DAYS }} days — no card needed.
                Pricing is a starting point, not a wall: talk to us about what fits your team.
            </p>
        </div>

        <div class="mt-12 grid gap-6 lg:grid-cols-3">
            @foreach ([
                ['Free', '€0', 'forever', 'For trying it properly', ['Up to 5 people', 'All three modules', 'Community support'], false],
                ['Starter', '€49', 'per month', 'For a growing team', ['Up to 25 people', 'All three modules', 'Email support', 'Reports and exports'], true],
                ['Pro', '€99', 'per month', 'For established teams', ['Unlimited people', 'All three modules', 'Priority support', 'Audit log and 2FA policy'], false],
            ] as [$name, $price, $period, $tagline, $features, $featured])
                <div class="relative rounded-2xl border-2 p-7 {{ $featured
                        ? 'border-gold bg-gold-soft shadow-xl'
                        : 'border-gray-200 bg-white dark:border-ink-700 dark:bg-ink-900' }}">
                    @if ($featured)
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-gold px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-ink-950">
                            Most popular
                        </span>
                    @endif

                    <h3 class="font-display text-xl font-semibold">{{ $name }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $tagline }}</p>

                    <p class="mt-5">
                        <span class="font-display text-4xl font-semibold">{{ $price }}</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $period }}</span>
                    </p>

                    <ul class="mt-6 space-y-2.5">
                        @foreach ($features as $feature)
                            <li class="flex items-start gap-2.5 text-sm">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-gold" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                <span class="text-gray-600 dark:text-gray-300">{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <a href="{{ route('register') }}"
                       class="mt-7 block rounded-lg py-2.5 text-center text-sm font-semibold transition
                              {{ $featured
                                 ? 'bg-gold text-ink-950 hover:bg-gold-dark'
                                 : 'border border-gray-300 text-gray-700 hover:border-gold hover:text-gold dark:border-ink-600 dark:text-gray-200' }}">
                        Start free trial
                    </a>
                </div>
            @endforeach
        </div>

        <p class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
            Prices exclude VAT. Not sure which fits? <a href="#contact" class="font-medium text-gold hover:underline">Ask us</a>.
        </p>
    </div>
</section>

{{-- ==================== FAQ ==================== --}}
<section id="faq" class="border-y border-gray-200 bg-gray-50 py-20 dark:border-ink-800 dark:bg-ink-900">
    <div class="mx-auto max-w-3xl px-5">
        <div class="text-center">
            <p class="text-sm font-semibold uppercase tracking-widest text-gold">Questions</p>
            <h2 class="mt-2 font-display text-3xl font-semibold sm:text-4xl">Things people ask us</h2>
        </div>

        <div class="mt-10 space-y-3">
            @foreach ([
                ['Do we have to use all three modules?', 'No. You choose per person what they can open — sales people can have CRM only, and everyone else need never see it. You can change any of it later in a click.'],
                ['Can we move our existing data across?', 'Yes. Get in touch and tell us what you are moving from; we will talk you through the import rather than leaving you to it.'],
                ['Does it calculate payroll?', 'Not yet. Radical AI distributes payslips securely — an admin uploads the PDF from your payroll provider and each person sees only their own. Native payroll is on the roadmap.'],
                ['What happens when the trial ends?', 'Nothing disappears. We will ask you to pick a plan, and your data waits for you. We do not need a card to start, so nothing is charged automatically.'],
                ['Where is our data stored?', 'In the EU. Every record is scoped to your organisation and separated from every other customer, and payslips are held in private storage that the app checks ownership against on every download.'],
                ['Can I get a demo?', 'Of course — use the form below and say you would like a demo. We will show you the whole thing end to end, no obligation.'],
            ] as $faq)
                <details class="group rounded-xl border border-gray-200 bg-white dark:border-ink-700 dark:bg-ink-850">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-medium">
                        <span>{{ $faq[0] }}</span>
                        <svg class="h-5 w-5 shrink-0 text-gold transition group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </summary>
                    <p class="border-t border-gray-100 px-5 py-4 text-sm leading-relaxed text-gray-600 dark:border-ink-700 dark:text-gray-300">
                        {{ $faq[1] }}
                    </p>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- ==================== Contact ==================== --}}
<section id="contact" class="py-20">
    <div class="mx-auto max-w-5xl px-5">
        <div class="grid gap-10 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <p class="text-sm font-semibold uppercase tracking-widest text-gold">Get in touch</p>
                <h2 class="mt-2 font-display text-3xl font-semibold">Talk to a human</h2>
                <p class="mt-3 leading-relaxed text-gray-600 dark:text-gray-300">
                    Questions about whether this fits how you work? Want a walkthrough before committing?
                    Send us a note and we will come back to you — usually the same working day.
                </p>

                <div class="mt-6 space-y-3 text-sm">
                    <p class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-gold" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                        <a href="mailto:hello@radical-ai.example" class="text-gray-600 hover:text-gold dark:text-gray-300">hello@radical-ai.example</a>
                    </p>
                    <p class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-gold" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                        <span class="text-gray-600 dark:text-gray-300">Galway, Ireland</span>
                    </p>
                </div>
            </div>

            <div class="lg:col-span-3">
                @if (session('contact_sent'))
                    <div class="rounded-2xl border border-status-done/40 bg-status-done/10 p-8 text-center">
                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-status-done/20">
                            <svg class="h-6 w-6 text-status-done" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        </span>
                        <h3 class="mt-3 font-display text-xl font-semibold">Thanks — message received</h3>
                        <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-300">
                            We'll get back to you shortly. In the meantime you can
                            <a href="{{ route('register') }}" class="font-medium text-gold hover:underline">start a free trial</a>
                            without waiting.
                        </p>
                    </div>
                @else
                    <form method="POST" action="{{ route('contact.store') }}"
                          class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-ink-700 dark:bg-ink-900">
                        @csrf

                        {{-- Honeypot: hidden from people, tempting to bots --}}
                        <div class="hidden" aria-hidden="true">
                            <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="c-name" class="label-app">Your name *</label>
                                <input id="c-name" name="name" required class="input-app" value="{{ old('name') }}">
                                @error('name') <p class="mt-1 text-xs text-priority-high">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="c-email" class="label-app">Email *</label>
                                <input id="c-email" name="email" type="email" required class="input-app" value="{{ old('email') }}">
                                @error('email') <p class="mt-1 text-xs text-priority-high">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="c-company" class="label-app">Company</label>
                                <input id="c-company" name="company" class="input-app" value="{{ old('company') }}">
                            </div>
                            <div>
                                <label for="c-phone" class="label-app">Phone</label>
                                <input id="c-phone" name="phone" class="input-app" value="{{ old('phone') }}">
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="c-topic" class="label-app">What's it about?</label>
                            <select id="c-topic" name="topic" class="input-app">
                                @foreach ($topics as $value => $label)
                                    <option value="{{ $value }}" @selected(old('topic') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mt-4">
                            <label for="c-message" class="label-app">Message *</label>
                            <textarea id="c-message" name="message" rows="4" required class="input-app"
                                      placeholder="Tell us a little about your team and what you're trying to solve.">{{ old('message') }}</textarea>
                            @error('message') <p class="mt-1 text-xs text-priority-high">{{ $message }}</p> @enderror
                        </div>

                        <label class="mt-4 flex items-start gap-2.5 text-sm">
                            <input type="checkbox" name="consent" value="1" @checked(old('consent'))
                                   class="mt-0.5 rounded border-gray-300 text-gold focus:ring-gold">
                            <span class="text-gray-600 dark:text-gray-300">
                                I'm happy for Radical AI to use these details to reply to my enquiry.
                            </span>
                        </label>
                        @error('consent') <p class="mt-1 text-xs text-priority-high">{{ $message }}</p> @enderror

                        <button type="submit" class="btn-gold mt-5 w-full justify-center py-2.5">Send message</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- ==================== Final CTA ==================== --}}
<section class="border-t border-gray-200 bg-gray-50 py-16 dark:border-ink-800 dark:bg-ink-900">
    <div class="mx-auto max-w-3xl px-5 text-center">
        <h2 class="font-display text-3xl font-semibold sm:text-4xl">Ready to stop juggling three tools?</h2>
        <p class="mx-auto mt-3 max-w-lg text-gray-600 dark:text-gray-300">
            Set up your workspace in a few minutes and see the difference on your own data.
        </p>
        <div class="mt-7 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <a href="{{ route('register') }}" class="btn-gold w-full justify-center px-6 py-3 text-base sm:w-auto">Start free trial</a>
            <a href="{{ route('login') }}" class="btn-ghost w-full justify-center px-6 py-3 text-base sm:w-auto">Log in</a>
        </div>
    </div>
</section>

{{-- ==================== Footer ==================== --}}
<footer class="border-t border-gray-200 py-12 dark:border-ink-800">
    <div class="mx-auto max-w-6xl px-5">
        <div class="flex flex-col gap-8 sm:flex-row sm:justify-between">
            <div class="max-w-xs">
                <p class="font-display text-xl font-semibold">Radical <span class="text-gold">AI</span></p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    CRM, task tracking and HR in a single workspace, built for small teams.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-10 sm:grid-cols-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Product</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li><a href="#features" class="text-gray-600 hover:text-gold dark:text-gray-300">Features</a></li>
                        <li><a href="#pricing" class="text-gray-600 hover:text-gold dark:text-gray-300">Pricing</a></li>
                        <li><a href="#how" class="text-gray-600 hover:text-gold dark:text-gray-300">How it works</a></li>
                    </ul>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Company</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li><a href="#faq" class="text-gray-600 hover:text-gold dark:text-gray-300">FAQ</a></li>
                        <li><a href="#contact" class="text-gray-600 hover:text-gold dark:text-gray-300">Contact</a></li>
                        <li><a href="{{ route('legal.terms') }}" class="text-gray-600 hover:text-gold dark:text-gray-300">Terms</a></li>
                        <li><a href="{{ route('legal.privacy') }}" class="text-gray-600 hover:text-gold dark:text-gray-300">Privacy</a></li>
                    </ul>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Account</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li><a href="{{ route('login') }}" class="text-gray-600 hover:text-gold dark:text-gray-300">Log in</a></li>
                        <li><a href="{{ route('register') }}" class="text-gray-600 hover:text-gold dark:text-gray-300">Start free trial</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-10 flex flex-col gap-3 border-t border-gray-200 pt-6 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between dark:border-ink-800 dark:text-gray-400">
            <p>&copy; {{ date('Y') }} Radical AI. All rights reserved.</p>
            <p>Made in Galway, Ireland 🇮🇪</p>
        </div>
    </div>
</footer>

</body>
</html>
