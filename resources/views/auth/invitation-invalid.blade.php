<x-guest-layout>
    <div class="py-4 text-center">
        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-priority-high/15 text-2xl text-priority-high">!</span>
        <h1 class="mt-3 font-display text-2xl font-semibold text-gray-900 dark:text-white">Invitation not valid</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $reason }}</p>
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
            Ask whoever invited you to send a fresh link, or
            <a href="{{ route('login') }}" class="font-medium text-gold hover:underline">sign in</a>
            if you already have an account.
        </p>
    </div>
</x-guest-layout>
