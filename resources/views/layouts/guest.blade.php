<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Crewly360</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|newsreader:500,600,700&display=swap" rel="stylesheet" />

        <script>
            if ((localStorage.getItem('theme') || 'dark') === 'light') {
                document.documentElement.classList.remove('dark');
            }
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 dark:text-gray-100">
        <div class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 dark:bg-ink-950 sm:justify-center sm:pt-0">
            <div class="text-center">
                <a href="/" class="font-display text-4xl font-semibold tracking-tight text-gray-900 dark:text-white">
                    Crewly<span class="text-gold">360</span>
                </a>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">CRM · Tasks · HR — one workspace for small teams</p>
            </div>

            <div class="mt-8 w-full overflow-hidden bg-white px-6 py-6 shadow-md dark:bg-ink-850 {{ isset($wide) ? 'sm:max-w-xl' : 'sm:max-w-md' }} sm:rounded-2xl sm:border sm:border-gray-200 dark:sm:border-ink-700">
                {{ $slot }}
            </div>

            <p class="mt-6 text-xs text-gray-400 dark:text-gray-500">Demo password for all accounts: <code class="font-mono">password</code></p>
        </div>
    </body>
</html>
