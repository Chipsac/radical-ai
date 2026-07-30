<x-guest-layout>
    <div class="text-center">
        <h1 class="font-display text-2xl font-semibold text-gray-900 dark:text-white">Two-step verification</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Enter the 6-digit code from your authenticator app.
        </p>
    </div>

    <form method="POST" action="{{ route('two-factor.challenge') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <input name="code" inputmode="numeric" autocomplete="one-time-code" autofocus required
                   placeholder="000000"
                   class="input-app text-center font-mono text-2xl tracking-[0.4em]" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <button type="submit" class="btn-gold w-full justify-center py-2.5">Verify</button>

        <p class="text-center text-xs text-gray-500 dark:text-gray-400">
            Lost your phone? Enter one of your recovery codes above instead — each works once.
        </p>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm text-gray-400 hover:text-gold">Cancel and sign out</button>
    </form>
</x-guest-layout>
