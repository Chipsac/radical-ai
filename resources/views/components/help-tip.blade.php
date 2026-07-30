@props(['text', 'title' => null, 'placement' => 'top'])

{{--
    Inline contextual help. Renders a small "?" the user can hover or focus;
    keyboard accessible and screen-reader friendly via role="tooltip".
--}}
@php
    $position = match ($placement) {
        'bottom' => 'top-full mt-2',
        'left' => 'right-full mr-2 top-1/2 -translate-y-1/2',
        'right' => 'left-full ml-2 top-1/2 -translate-y-1/2',
        default => 'bottom-full mb-2',
    };
@endphp

<span x-data="{ open: false }" class="relative inline-flex align-middle">
    <button type="button"
            @mouseenter="open = true" @mouseleave="open = false"
            @focus="open = true" @blur="open = false"
            @click.prevent="open = !open"
            aria-label="{{ $title ?? 'More information' }}"
            {{ $attributes->merge(['class' => 'inline-flex h-4 w-4 items-center justify-center rounded-full border border-gray-400 text-[10px] font-bold leading-none text-gray-500 transition hover:border-gold hover:text-gold focus:outline-none focus:ring-2 focus:ring-gold/50 dark:border-ink-600 dark:text-gray-400']) }}>
        ?
    </button>

    <span x-show="open" x-transition.opacity role="tooltip" style="display: none;"
          class="absolute {{ $position }} left-1/2 z-50 w-60 -translate-x-1/2 rounded-lg bg-ink-900 px-3 py-2 text-left text-xs font-normal leading-relaxed text-gray-100 shadow-xl ring-1 ring-black/10">
        @if ($title)
            <span class="mb-0.5 block font-semibold text-gold">{{ $title }}</span>
        @endif
        {{ $text }}
    </span>
</span>
