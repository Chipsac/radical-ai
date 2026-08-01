<x-guest-layout>
    <div class="text-center">
        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gold-soft">
            <svg class="h-6 w-6 text-gold" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
            </svg>
        </span>
        <h1 class="mt-3 font-display text-2xl font-semibold text-gray-900 dark:text-white">Check your email</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            We've sent a verification link to
            <strong class="text-gray-700 dark:text-gray-200">{{ auth()->user()->email }}</strong>.
            Click it to activate your workspace.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-4 rounded-xl border border-status-done/40 bg-status-done/10 px-4 py-3 text-sm font-medium text-status-done">
            A new verification link is on its way.
        </div>
    @endif

    {{-- Local development only: mail goes to the log file, so surface the link
         directly rather than making the developer dig through storage/logs. --}}
    @if (app()->environment('local'))
        @php
            $devLink = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => auth()->id(), 'hash' => sha1(auth()->user()->getEmailForVerification())]
            );
        @endphp
        <div class="mt-5 rounded-xl border border-dashed border-gold/50 bg-gold-soft p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gold">Local development</p>
            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                Mail is written to the log rather than sent, so here is the link directly:
            </p>
            <a href="{{ $devLink }}" class="mt-2 inline-block rounded-lg bg-gold px-3 py-1.5 text-xs font-bold text-ink-950 hover:bg-gold-dark">
                Verify my email now
            </a>
        </div>
    @endif

    <div class="mt-6 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-ghost">Resend email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-gray-500 underline hover:text-gold dark:text-gray-400">
                Log out
            </button>
        </form>
    </div>

    <p class="mt-6 text-center text-xs text-gray-400">
        Wrong address? Log out and sign up again — nothing has been created yet beyond your workspace shell.
    </p>
</x-guest-layout>
