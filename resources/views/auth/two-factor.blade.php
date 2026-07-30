<x-app-layout>
    <x-slot name="title">Two-factor authentication</x-slot>

    <div class="mx-auto max-w-2xl">
        <h1 class="font-display text-3xl font-semibold text-gray-900 dark:text-white">Two-factor authentication</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            An extra code from your phone whenever you sign in. Strongly recommended for admin accounts.
        </p>

        {{-- Recovery codes are shown exactly once, right after setup --}}
        @if ($recoveryCodes)
            <div class="card mt-6 border-status-done/40 p-6">
                <h2 class="font-display text-lg font-semibold text-status-done">Save your recovery codes</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Each code works once. They are the only way back into your account if you lose your phone —
                    store them somewhere safe now, because they will not be shown again.
                </p>
                <div class="mt-4 grid grid-cols-2 gap-2 rounded-xl bg-gray-50 p-4 font-mono text-sm dark:bg-ink-800">
                    @foreach ($recoveryCodes as $code)
                        <span>{{ $code }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($enabled)
            <div class="card mt-6 p-6">
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-status-done"></span>
                    <h2 class="font-display text-lg font-semibold">Two-factor is on</h2>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    You'll be asked for a code from your authenticator app each time you sign in.
                </p>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <form method="POST" action="{{ route('two-factor.recovery') }}" class="space-y-2">
                        @csrf
                        <label class="label-app">Regenerate recovery codes</label>
                        <input type="password" name="password" required placeholder="Confirm your password" class="input-app" />
                        <button type="submit" class="btn-ghost w-full justify-center">Generate new codes</button>
                    </form>

                    <form method="POST" action="{{ route('two-factor.destroy') }}" class="space-y-2">
                        @csrf @method('DELETE')
                        <label class="label-app">Turn two-factor off</label>
                        <input type="password" name="password" required placeholder="Confirm your password" class="input-app" />
                        <button type="submit" class="w-full rounded-lg border border-priority-high px-3.5 py-2 text-sm font-semibold text-priority-high transition hover:bg-priority-high/10">
                            Disable
                        </button>
                    </form>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-3" />
            </div>
        @else
            <div class="card mt-6 p-6">
                <h2 class="font-display text-lg font-semibold">Set it up</h2>

                <ol class="mt-4 space-y-5">
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gold text-xs font-bold text-ink-950">1</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium">Scan this with your authenticator app</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Google Authenticator, 1Password, Authy, Microsoft Authenticator — any will do.</p>
                            <div class="mt-3 flex flex-wrap items-center gap-4">
                                <div id="totp-qr" class="rounded-xl bg-white p-3" data-uri="{{ $otpauthUri }}"></div>
                                <div class="text-xs">
                                    <p class="mb-1 text-gray-500 dark:text-gray-400">Can't scan? Enter this key by hand:</p>
                                    <code class="select-all break-all rounded bg-gray-100 px-2 py-1 font-mono text-sm dark:bg-ink-800">{{ $secret }}</code>
                                </div>
                            </div>
                        </div>
                    </li>

                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gold text-xs font-bold text-ink-950">2</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium">Enter the 6-digit code it shows</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">This proves the setup worked before we switch it on.</p>
                            <form method="POST" action="{{ route('two-factor.confirm') }}" class="mt-3 flex gap-2">
                                @csrf
                                <input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6"
                                       placeholder="123456" required
                                       class="input-app !w-40 text-center font-mono text-lg tracking-[0.3em]" />
                                <button type="submit" class="btn-gold">Verify &amp; enable</button>
                            </form>
                            <x-input-error :messages="$errors->get('code')" class="mt-2" />
                        </div>
                    </li>
                </ol>
            </div>
        @endif
    </div>

    {{-- The QR is rendered by the bundled app.js, which picks up #totp-qr. --}}
</x-app-layout>
