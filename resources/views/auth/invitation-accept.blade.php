<x-guest-layout>
    <div class="text-center">
        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gold-soft text-lg font-bold text-gold">
            {{ mb_substr($organization->name, 0, 1) }}
        </span>
        <h1 class="mt-3 font-display text-2xl font-semibold text-gray-900 dark:text-white">
            Join {{ $organization->name }}
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $invitation->inviter?->name ?? 'An admin' }} invited
            <strong>{{ $invitation->email }}</strong> to join as
            <span class="capitalize">{{ $invitation->role }}</span>.
        </p>
    </div>

    <form method="POST" action="{{ route('invitations.complete', $invitation->token) }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="name" value="Your name" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                          :value="old('name')" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Choose a password" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                          required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirm password" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                          class="mt-1 block w-full" required autocomplete="new-password" />
        </div>

        <button type="submit" class="btn-gold w-full justify-center py-2.5">Accept invitation</button>

        <p class="text-center text-xs text-gray-400">
            This invitation expires on {{ $invitation->expires_at->format('j M Y') }}.
        </p>
    </form>
</x-guest-layout>
