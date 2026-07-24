@props(['user', 'size' => 'md'])

@php
    $classes = $size === 'sm' ? 'h-6 w-6 text-[10px]' : 'h-8 w-8 text-xs';
@endphp

<span title="{{ $user?->name }}"
      class="{{ $classes }} inline-flex shrink-0 items-center justify-center rounded-full bg-gold-soft font-bold text-gold">
    {{ $user?->initials() ?? '—' }}
</span>
