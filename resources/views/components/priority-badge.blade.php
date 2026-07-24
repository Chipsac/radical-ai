@props(['priority'])

@php
    $color = \App\Models\Task::PRIORITY_COLORS[$priority] ?? '#8A8A8A';
@endphp

<span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide"
      style="color: {{ $color }}; background: {{ $color }}1e;">
    {{ $priority }}
</span>
