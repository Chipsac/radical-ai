<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — Radical AI</title>
    <meta name="robots" content="index, follow">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|newsreader:500,600,700&display=swap" rel="stylesheet" />

    <script>
        if ((localStorage.getItem('theme') || 'dark') === 'light') {
            document.documentElement.classList.remove('dark');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-gray-900 antialiased dark:bg-ink-950 dark:text-gray-100">

<header class="border-b border-gray-200 dark:border-ink-800">
    <div class="mx-auto flex max-w-3xl items-center justify-between px-5 py-4">
        <a href="{{ route('landing') }}" class="font-display text-xl font-semibold">
            Radical <span class="text-gold">AI</span>
        </a>
        <a href="{{ route('landing') }}" class="text-sm font-medium text-gray-600 hover:text-gold dark:text-gray-300">&larr; Back to site</a>
    </div>
</header>

<main class="mx-auto max-w-3xl px-5 py-12">
    <h1 class="font-display text-3xl font-semibold sm:text-4xl">{{ $title }}</h1>
    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Last updated {{ $updated }}</p>

    <div class="legal-body mt-8 space-y-6 text-[15px] leading-relaxed text-gray-700 dark:text-gray-300">
        {{ $slot }}
    </div>

    <div class="mt-12 border-t border-gray-200 pt-6 text-sm dark:border-ink-800">
        <p class="text-gray-500 dark:text-gray-400">
            Questions about this?
            <a href="{{ route('landing') }}#contact" class="font-medium text-gold hover:underline">Get in touch</a>.
        </p>
    </div>
</main>

<footer class="border-t border-gray-200 py-8 dark:border-ink-800">
    <div class="mx-auto flex max-w-3xl flex-col gap-2 px-5 text-xs text-gray-500 sm:flex-row sm:justify-between dark:text-gray-400">
        <p>&copy; {{ date('Y') }} Radical AI</p>
        <p class="flex gap-4">
            <a href="{{ route('legal.terms') }}" class="hover:text-gold">Terms</a>
            <a href="{{ route('legal.privacy') }}" class="hover:text-gold">Privacy</a>
        </p>
    </div>
</footer>

</body>
</html>
