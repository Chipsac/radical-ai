<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="font-display text-2xl font-semibold text-gray-900 dark:text-white">Create your workspace</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ \App\Models\Organization::TRIAL_DAYS }}-day free trial · no card required · your data stays private to your organisation
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="organization_name" value="Organisation name" />
            <x-text-input id="organization_name" name="organization_name" type="text"
                          class="mt-1 block w-full" :value="old('organization_name')"
                          required autofocus placeholder="Acme Startup Ltd" />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">This becomes your private workspace — only people you invite can see it.</p>
            <x-input-error :messages="$errors->get('organization_name')" class="mt-2" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="name" value="Your name" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                              :value="old('name')" required autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="job_title" value="Your role" />
                <x-text-input id="job_title" name="job_title" type="text" class="mt-1 block w-full"
                              :value="old('job_title')" placeholder="Founder" />
                <x-input-error :messages="$errors->get('job_title')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="email" value="Work email" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                          :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                          required autocomplete="new-password" />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                @if (app()->isProduction())
                    At least 12 characters with upper and lower case, a number and a symbol. Checked against known breached passwords.
                @else
                    At least 8 characters.
                @endif
            </p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirm password" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                          class="mt-1 block w-full" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="terms" value="1" @checked(old('terms'))
                   class="mt-0.5 rounded border-gray-300 bg-white text-gold focus:ring-gold dark:border-ink-600 dark:bg-ink-900">
            <span class="text-gray-600 dark:text-gray-300">
                I agree to the
                <a href="{{ route('legal.terms') }}" target="_blank" class="font-medium text-gold hover:underline">terms of service</a>
                and
                <a href="{{ route('legal.privacy') }}" target="_blank" class="font-medium text-gold hover:underline">privacy policy</a>.
            </span>
        </label>
        <x-input-error :messages="$errors->get('terms')" />

        <button type="submit" class="btn-gold w-full justify-center py-2.5">Create workspace</button>

        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
            Already have an account?
            <a href="{{ route('login') }}" class="font-medium text-gold hover:underline">Sign in</a>
        </p>
    </form>
</x-guest-layout>
