@props(['value'])

{{-- Same Breeze light-only default as text-input: gray-700 on the dark theme
     is dark text on a dark panel. --}}
<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-700 dark:text-gray-300']) }}>
    {{ $value ?? $slot }}
</label>
