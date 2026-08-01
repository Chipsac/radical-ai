<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' — ' : '' }}Radical AI</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|newsreader:500,600,700&display=swap" rel="stylesheet" />

        <script>
            // Apply saved theme before first paint to avoid a flash
            if ((localStorage.getItem('theme') || 'dark') === 'light') {
                document.documentElement.classList.remove('dark');
            }
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900 dark:bg-ink-950 dark:text-gray-100">
        @php
            $me = auth()->user();
            $navCategories = $me?->hasModule(\App\Support\Modules::TASKS)
                ? \App\Models\TaskCategory::withCount('tasks')->orderBy('name')->get()
                : collect();
        @endphp

        <div class="flex min-h-screen">
            {{-- ======================= Sidebar ======================= --}}
            <aside class="hidden w-64 shrink-0 flex-col border-r border-gray-200 bg-white dark:border-ink-700 dark:bg-ink-900 lg:flex">
                <div class="flex h-16 items-center gap-2 border-b border-gray-200 px-6 dark:border-ink-700">
                    <span class="font-display text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Radical <span class="text-gold">AI</span></span>
                </div>

                <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-6 text-sm">
                    <div class="space-y-1">
                        <x-nav-item route="home" icon="home" label="Home" />
                    </div>

                    @if ($me?->hasModule(\App\Support\Modules::CRM))
                        <div>
                            <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">CRM</p>
                            <div class="space-y-1">
                                <x-nav-item route="crm.deals.index" icon="deals" label="Deal Pipeline" />
                                <x-nav-item route="crm.leads.index" icon="leads" label="Leads" />
                                <x-nav-item route="crm.accounts.index" icon="accounts" label="Accounts" />
                            </div>
                        </div>
                    @endif

                    @if ($me?->hasModule(\App\Support\Modules::TASKS))
                        <div>
                            <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">Tasks</p>
                            <div class="space-y-1">
                                <x-nav-item route="tasks.dashboard" icon="dashboard" label="Task Dashboard" />
                                <x-nav-item route="tasks.board" icon="board" label="Board" />
                                <x-nav-item route="tasks.categories.index" icon="tag" label="Categories" />
                            </div>
                        </div>
                    @endif

                    @if ($me?->hasModule(\App\Support\Modules::HR))
                        <div>
                            <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">HR</p>
                            <div class="space-y-1">
                                <x-nav-item route="hr.index" icon="people" label="Directory" />
                                <x-nav-item route="hr.leave.index" icon="leave" label="Leave" />
                                <x-nav-item route="hr.calendar" icon="calendar" label="Calendar" />
                                <x-nav-item route="hr.payslips.index" icon="payslip" label="Payslips" />
                            </div>
                        </div>
                    @endif

                    <div class="space-y-1">
                        @if ($me?->hasModule(\App\Support\Modules::TASKS))
                            <x-nav-item route="reports.index" icon="report" label="Reports" />
                        @endif
                        @if ($me?->isManager())
                            <x-nav-item route="team.index" icon="people" label="Team" />
                        @endif
                        @if ($me?->isAdmin())
                            <x-nav-item route="settings.index" icon="settings" label="Settings" />
                        @endif
                    </div>

                    @if ($me?->hasModule(\App\Support\Modules::TASKS) && $navCategories->isNotEmpty())
                        <div>
                            <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">Categories</p>
                            <div class="space-y-1">
                                @foreach ($navCategories as $cat)
                                    <a href="{{ route('tasks.board', ['category' => $cat->id]) }}"
                                       class="flex items-center justify-between rounded-lg px-3 py-1.5 text-gray-600 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-ink-800">
                                        <span class="flex items-center gap-2 truncate">
                                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $cat->color }}"></span>
                                            <span class="truncate">{{ $cat->name }}</span>
                                        </span>
                                        <span class="ml-2 text-xs text-gray-400">{{ $cat->tasks_count }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </nav>
            </aside>

            {{-- ======================= Main ======================= --}}
            <div class="flex min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-gray-200 bg-white/90 px-4 backdrop-blur dark:border-ink-700 dark:bg-ink-900/90 sm:px-6">
                    <span class="font-display text-xl font-semibold lg:hidden">Radical <span class="text-gold">AI</span></span>

                    <form action="{{ route('tasks.board') }}" method="get" class="hidden max-w-md flex-1 md:block">
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.34-4.34M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" /></svg>
                            <input name="q" type="search" placeholder="Search tasks, deals, people…"
                                   class="w-full rounded-full border-gray-300 bg-gray-50 py-2 pl-9 pr-4 text-sm focus:border-gold focus:ring-gold dark:border-ink-600 dark:bg-ink-800" />
                        </div>
                    </form>

                    <div class="ml-auto flex items-center gap-2">
                        <button type="button" onclick="toggleTheme()" title="Toggle light/dark theme"
                                class="rounded-full p-2 text-gray-500 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-ink-800">
                            <svg class="h-5 w-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" /></svg>
                            <svg class="h-5 w-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" /></svg>
                        </button>

                        <button type="button" class="relative rounded-full p-2 text-gray-500 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-ink-800" title="Notifications (demo)">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                            <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-gold"></span>
                        </button>

                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2 transition hover:bg-gray-100 dark:hover:bg-ink-800">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gold-soft text-sm font-bold text-gold">{{ $me?->initials() }}</span>
                                <span class="hidden text-sm font-medium sm:block">{{ $me?->name }}</span>
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </button>
                            <div x-show="open" @click.outside="open = false" x-transition.opacity
                                 class="absolute right-0 mt-2 w-52 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-lg dark:border-ink-600 dark:bg-ink-800" style="display: none;">
                                <div class="border-b border-gray-100 px-4 py-2 dark:border-ink-700">
                                    <p class="text-sm font-semibold">{{ $me?->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"><span class="capitalize">{{ $me?->role }}</span> · {{ $me?->organization?->name }}</p>
                                </div>
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-ink-700">Profile</a>
                                <a href="{{ route('two-factor.show') }}" class="flex items-center justify-between px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-ink-700">
                                    Two-factor
                                    @if ($me?->hasTwoFactorEnabled())
                                        <span class="rounded-full bg-status-done/15 px-1.5 py-0.5 text-[10px] font-bold text-status-done">ON</span>
                                    @else
                                        <span class="rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] font-bold text-gray-500 dark:bg-ink-700">OFF</span>
                                    @endif
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-ink-700">Log out</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>

                {{-- Mobile nav strip --}}
                <nav class="flex gap-1 overflow-x-auto border-b border-gray-200 bg-white px-3 py-2 text-sm dark:border-ink-700 dark:bg-ink-900 lg:hidden">
                    @php
                        $mobileNav = [['home', 'Home', null]];
                        $mobileNav[] = ['crm.deals.index', 'CRM', \App\Support\Modules::CRM];
                        $mobileNav[] = ['tasks.board', 'Tasks', \App\Support\Modules::TASKS];
                        $mobileNav[] = ['hr.index', 'HR', \App\Support\Modules::HR];
                        $mobileNav[] = ['reports.index', 'Reports', \App\Support\Modules::TASKS];
                    @endphp
                    @foreach ($mobileNav as [$r, $l, $mod])
                        @if ($mod === null || $me?->hasModule($mod))
                            <a href="{{ route($r) }}" class="whitespace-nowrap rounded-full px-3 py-1 {{ request()->routeIs(explode('.', $r)[0].'*') || request()->routeIs($r) ? 'bg-gold-soft text-gold font-semibold' : 'text-gray-600 dark:text-gray-300' }}">{{ $l }}</a>
                        @endif
                    @endforeach
                </nav>

                <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    @if (session('status'))
                        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                             class="mb-4 flex items-center justify-between rounded-xl border border-gold/40 bg-gold-soft px-4 py-3 text-sm font-medium text-gold-dark dark:text-gold">
                            <span>{{ session('status') }}</span>
                            <button @click="show = false" class="text-gold">&times;</button>
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>

        {{-- Contextual help: floating button, searchable drawer, page tours --}}
        <x-help-drawer />

        @stack('scripts')
    </body>
</html>
