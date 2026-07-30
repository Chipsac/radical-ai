<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-gray-900">Join {{ $organization->name }}</h2>
        <p class="text-sm text-gray-600 mt-1">You’ve been invited to join the enterprise workspace as a <strong>{{ ucfirst($invitation->role) }}</strong>.</p>
    </div>

    <form method="POST" action="{{ route('invitations.accept', $invitation->token) }}">
        @csrf

        <div class="space-y-4">
            <div>
                <x-input-label for="email" :value="__('Email Address')" />
                <x-text-input id="email" class="block mt-1 w-full bg-gray-100" type="email" name="email" :value="$invitation->email" disabled />
            </div>

            <div>
                <x-input-label for="name" :value="__('Your Full Name')" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus placeholder="John Smith" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" :value="__('Choose Password')" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required />
            </div>

            <div class="pt-4">
                <x-primary-button class="w-full justify-center bg-indigo-600 hover:bg-indigo-700 py-3">
                    {{ __('Accept Invitation & Join Workspace') }}
                </x-primary-button>
            </div>
        </div>
    </form>
</x-guest-layout>
