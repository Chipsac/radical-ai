@props(['status'])

@php
    $label = \App\Models\Task::STATUS_LABELS[$status] ?? ucfirst($status);
    $color = \App\Models\Task::STATUS_COLORS[$status] ?? '#8A8A8A';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold']) }}
      style="color: {{ $color }}; background: {{ $color }}22;">
    <span class="h-1.5 w-1.5 rounded-full" style="background: {{ $color }}"></span>
    {{ $label }}
</span>
