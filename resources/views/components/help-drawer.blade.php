@php
    $tour = \App\Support\HelpLibrary::tourFor(request()->route()?->getName() ?? '');
    $tourKey = $tour ? 'tour:'.request()->route()->getName() : null;
    $autoStart = $tour && ! auth()->user()?->hasSeen($tourKey);
@endphp

<div x-data="helpCentre({
        autoStart: {{ $autoStart ? 'true' : 'false' }},
        tour: {{ $tour ? Js::from($tour) : 'null' }},
        tourKey: {{ $tourKey ? Js::from($tourKey) : 'null' }},
        seenUrl: {{ Js::from(route('help.seen')) }},
        articlesUrl: {{ Js::from(route('help.articles')) }},
     })"
     x-init="init()">

    {{-- ---------- Floating help button ---------- --}}
    <button type="button" @click="openDrawer()"
            class="fixed bottom-5 right-5 z-40 flex h-12 w-12 items-center justify-center rounded-full bg-gold text-ink-950 shadow-lg transition hover:scale-105 hover:bg-gold-dark focus:outline-none focus:ring-4 focus:ring-gold/40"
            aria-label="Open help">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
        </svg>
    </button>

    {{-- ---------- Slide-over drawer ---------- --}}
    <div x-show="drawer" x-transition.opacity class="fixed inset-0 z-50 bg-black/50" style="display: none;" @click="drawer = false"></div>

    <aside x-show="drawer" x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-150"
           x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
           class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-white shadow-2xl dark:bg-ink-850"
           style="display: none;" role="dialog" aria-label="Help centre">

        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-ink-700">
            <h2 class="font-display text-lg font-semibold">Help centre</h2>
            <button @click="drawer = false" class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-ink-700" aria-label="Close help">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="border-b border-gray-200 p-4 dark:border-ink-700">
            <input x-model="query" @input.debounce.250ms="search()" type="search"
                   placeholder="Search help…" class="input-app" />

            <template x-if="tour">
                <button @click="startTour()" class="btn-ghost mt-3 w-full justify-center">
                    ▶ Replay the tour for this page
                </button>
            </template>
        </div>

        <div class="flex-1 space-y-5 overflow-y-auto p-5">
            <template x-if="loading">
                <p class="text-center text-sm text-gray-400">Searching…</p>
            </template>

            <template x-if="!loading && count === 0">
                <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    Nothing matched “<span x-text="query"></span>”. Try a different word.
                </p>
            </template>

            <template x-for="(items, section) in articles" :key="section">
                <div>
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-widest text-gray-400" x-text="section"></h3>
                    <div class="space-y-2">
                        <template x-for="article in items" :key="article.title">
                            <details class="group rounded-xl border border-gray-200 dark:border-ink-600">
                                <summary class="cursor-pointer list-none px-4 py-3 text-sm font-medium transition hover:text-gold">
                                    <span x-text="article.title"></span>
                                </summary>
                                <p class="border-t border-gray-100 px-4 py-3 text-sm leading-relaxed text-gray-600 dark:border-ink-700 dark:text-gray-300"
                                   x-text="article.body"></p>
                            </details>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <div class="border-t border-gray-200 px-5 py-3 text-xs text-gray-500 dark:border-ink-700 dark:text-gray-400">
            Still stuck? Email <a href="mailto:support@crewly360.com" class="font-medium text-gold hover:underline">support@crewly360.com</a>
        </div>
    </aside>

    {{-- ---------- Guided tour spotlight ---------- --}}
    <template x-if="tourActive">
        <div class="fixed inset-0 z-[60]" role="dialog" aria-label="Guided tour">
            <div class="absolute inset-0 bg-black/60" @click="endTour()"></div>

            {{-- Highlight ring around the current target --}}
            <div class="pointer-events-none absolute rounded-xl ring-4 ring-gold transition-all duration-300"
                 :style="spotlightStyle"></div>

            <div class="absolute w-80 rounded-2xl bg-white p-5 shadow-2xl dark:bg-ink-850" :style="popoverStyle">
                <p class="text-xs font-semibold uppercase tracking-widest text-gold"
                   x-text="`Step ${stepIndex + 1} of ${tour.steps.length}`"></p>
                <h3 class="mt-1 font-display text-lg font-semibold" x-text="currentStep.title"></h3>
                <p class="mt-1.5 text-sm leading-relaxed text-gray-600 dark:text-gray-300" x-text="currentStep.body"></p>

                <div class="mt-4 flex items-center justify-between">
                    <button @click="endTour()" class="text-sm text-gray-400 transition hover:text-gray-600">Skip</button>
                    <div class="flex gap-2">
                        <button x-show="stepIndex > 0" @click="prev()" class="btn-ghost !py-1.5">Back</button>
                        <button @click="next()" class="btn-gold !py-1.5"
                                x-text="stepIndex === tour.steps.length - 1 ? 'Done' : 'Next'"></button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
