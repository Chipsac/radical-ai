<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    {{-- One-click demo logins. The demo.login route only exists in local/testing,
         so this whole block must be gated the same way or the page 500s in production. --}}
    @if (Route::has('demo.login'))
        <div class="mb-6">
            <p class="mb-3 text-center text-sm font-medium text-gray-600 dark:text-gray-300">Try the demo as…</p>
            <div class="grid grid-cols-3 gap-2">
                @foreach ([
                    'admin' => ['Admin', 'Aoife · CEO'],
                    'manager' => ['Manager', 'Marcus · PM'],
                    'employee' => ['Employee', 'Sinead · BL'],
                ] as $role => [$label, $who])
                    <form method="POST" action="{{ route('demo.login', $role) }}">
                        @csrf
                        <button type="submit"
                                class="w-full rounded-xl border border-gold/50 bg-gold/10 px-2 py-3 text-center transition hover:bg-gold/25">
                            <span class="block text-sm font-bold text-gold">{{ $label }}</span>
                            <span class="block text-[11px] text-gray-500 dark:text-gray-400">{{ $who }}</span>
                        </button>
                    </form>
                @endforeach
            </div>
            <div class="my-6 flex items-center gap-3 text-xs uppercase tracking-widest text-gray-400">
                <span class="h-px flex-1 bg-gray-200 dark:bg-ink-600"></span>
                or sign in
                <span class="h-px flex-1 bg-gray-200 dark:bg-ink-600"></span>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-gold shadow-sm focus:ring-gold" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-300">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gold" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
