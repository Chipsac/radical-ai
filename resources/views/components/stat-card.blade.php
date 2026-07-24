@props(['label', 'value', 'sub' => null])

<div class="card p-5">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</p>
    <p class="mt-2 font-display text-3xl font-semibold text-gray-900 dark:text-white">{{ $value }}</p>
    @if ($sub)
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $sub }}</p>
    @endif
</div>
