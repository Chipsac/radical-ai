@props(['id', 'title', 'text'])

{{--
    A dismissible explainer shown at the top of a page the first time a user
    lands on it. Dismissal is stored server-side against the user, so it stays
    dismissed across devices rather than only in this browser.
--}}
@if (! auth()->user()?->hasSeen($id))
    <div x-data="{ show: true }" x-show="show" x-transition
         class="mb-5 flex items-start gap-3 rounded-xl border border-gold/30 bg-gold-soft px-4 py-3">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-gold" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
        </svg>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $title }}</p>
            <p class="mt-0.5 text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $text }}</p>
        </div>
        <button type="button"
                @click="show = false; fetch('{{ route('help.seen') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({ key: '{{ $id }}' })
                })"
                class="shrink-0 rounded-lg px-2 py-1 text-xs font-semibold text-gold transition hover:bg-gold/20"
                aria-label="Dismiss this tip">
            Got it
        </button>
    </div>
@endif
