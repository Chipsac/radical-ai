<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-gray-900">Start Your Enterprise SaaS Workspace</h2>
        <p class="text-sm text-gray-600 mt-1">Combine CRM, HR, & Task Management for your organization</p>
    </div>

    <form method="POST" action="{{ route('register') }}" x-data="{ plan: 'pro' }">
        @csrf

        <!-- Company & Workspace Details -->
        <div class="space-y-4 pb-6 border-b border-gray-200">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-indigo-600">1. Company Profile</h3>

            <div>
                <x-input-label for="company_name" :value="__('Company / Organization Name')" />
                <x-text-input id="company_name" class="block mt-1 w-full" type="text" name="company_name" :value="old('company_name')" required autofocus placeholder="e.g. Acme Corp" />
                <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="industry" :value="__('Industry')" />
                    <select id="industry" name="industry" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="Technology">Technology & Software</option>
                        <option value="Finance">Financial Services</option>
                        <option value="Healthcare">Healthcare & Biotech</option>
                        <option value="Consulting">Professional Services</option>
                        <option value="Retail">E-commerce & Retail</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="size_range" :value="__('Company Size')" />
                    <select id="size_range" name="size_range" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="1-10">1 - 10 employees</option>
                        <option value="11-50">11 - 50 employees</option>
                        <option value="51-200">51 - 200 employees</option>
                        <option value="201-500">201 - 500 employees</option>
                        <option value="500+">500+ employees</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Subscription Tier Selection -->
        <div class="py-6 border-b border-gray-200">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-indigo-600 mb-3">2. Select Plan Tier (14-Day Free Trial)</h3>
            <input type="hidden" name="plan_tier" :value="plan">

            <div class="grid grid-cols-3 gap-3">
                <div @click="plan = 'starter'" :class="plan === 'starter' ? 'border-indigo-600 bg-indigo-50 ring-2 ring-indigo-500' : 'border-gray-200 bg-white'" class="p-3 border rounded-lg cursor-pointer transition text-center">
                    <div class="font-bold text-gray-900 text-sm">Starter</div>
                    <div class="text-xs text-gray-500 mt-1">Up to 10 Seats</div>
                    <div class="text-sm font-semibold text-indigo-600 mt-2">$29/mo</div>
                </div>

                <div @click="plan = 'pro'" :class="plan === 'pro' ? 'border-indigo-600 bg-indigo-50 ring-2 ring-indigo-500' : 'border-gray-200 bg-white'" class="p-3 border rounded-lg cursor-pointer transition text-center relative">
                    <span class="absolute -top-2 right-2 bg-indigo-600 text-white text-[10px] uppercase font-bold px-1.5 py-0.5 rounded">Popular</span>
                    <div class="font-bold text-gray-900 text-sm">Professional</div>
                    <div class="text-xs text-gray-500 mt-1">Up to 50 Seats</div>
                    <div class="text-sm font-semibold text-indigo-600 mt-2">$79/mo</div>
                </div>

                <div @click="plan = 'enterprise'" :class="plan === 'enterprise' ? 'border-indigo-600 bg-indigo-50 ring-2 ring-indigo-500' : 'border-gray-200 bg-white'" class="p-3 border rounded-lg cursor-pointer transition text-center">
                    <div class="font-bold text-gray-900 text-sm">Enterprise</div>
                    <div class="text-xs text-gray-500 mt-1">Unlimited Seats</div>
                    <div class="text-sm font-semibold text-indigo-600 mt-2">$199/mo</div>
                </div>
            </div>
        </div>

        <!-- Administrator Credentials -->
        <div class="pt-6 space-y-4">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-indigo-600">3. Administrator Account</h3>

            <div>
                <x-input-label for="name" :value="__('Full Name')" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required placeholder="Jane Doe" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email" :value="__('Work Email')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required placeholder="jane@company.com" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                    <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required />
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200">
            <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4 bg-indigo-600 hover:bg-indigo-700">
                {{ __('Create Workspace & Start Trial') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
