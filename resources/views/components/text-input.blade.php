@props(['disabled' => false])

{{--
    Breeze ships this component with light-mode styling only. This app defaults
    to dark, so an unstyled input kept a white background while inheriting the
    dark theme's near-white text: users could not see what they were typing on
    the sign-up and login forms. Both themes are set explicitly here rather than
    left to inherit.
--}}
<input @disabled($disabled) {{ $attributes->merge(['class' =>
    'rounded-md shadow-sm mt-1 block w-full '
    .'border-gray-300 bg-white text-gray-900 placeholder-gray-400 '
    .'focus:border-gold focus:ring-gold '
    .'dark:border-ink-700 dark:bg-ink-900 dark:text-gray-100 dark:placeholder-gray-500 '
    .'dark:focus:border-gold dark:focus:ring-gold '
    .'disabled:opacity-60 disabled:cursor-not-allowed'
]) }}>
