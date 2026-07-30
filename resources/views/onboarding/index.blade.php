<x-app-layout>
    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <!-- Progress Bar -->
            <div class="mb-8 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-gray-500 mb-3">
                    <span :class="{{ $step }} >= 1 ? 'text-indigo-600 font-bold' : ''">1. Workspace Details</span>
                    <span :class="{{ $step }} >= 2 ? 'text-indigo-600 font-bold' : ''">2. Invite Team Members</span>
                    <span :class="{{ $step }} >= 3 ? 'text-indigo-600 font-bold' : ''">3. Launch Platform</span>
                </div>
                <div class="w-full bg-gray-200 h-2 rounded-full overflow-hidden">
                    <div class="bg-indigo-600 h-2 transition-all duration-500" style="width: {{ $step == 1 ? '33%' : ($step == 2 ? '66%' : '100%') }}"></div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
                @if ($step == 1)
                    <!-- Step 1: Workspace Profile -->
                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Welcome to Radical AI, {{ explode(' ', $user->name)[0] }}!</h2>
                        <p class="text-sm text-gray-600 mt-1">Let’s configure {{ $organization->name }}’s primary workspace preferences.</p>
                    </div>

                    <form method="POST" action="{{ route('onboarding.process') }}" class="space-y-6">
                        @csrf
                        <input type="hidden" name="step" value="1">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Industry / Domain</label>
                            <select name="industry" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="Technology" {{ $organization->industry === 'Technology' ? 'selected' : '' }}>Technology & Software</option>
                                <option value="Finance" {{ $organization->industry === 'Finance' ? 'selected' : '' }}>Financial Services</option>
                                <option value="Healthcare" {{ $organization->industry === 'Healthcare' ? 'selected' : '' }}>Healthcare & Life Sciences</option>
                                <option value="Consulting" {{ $organization->industry === 'Consulting' ? 'selected' : '' }}>Management Consulting</option>
                                <option value="E-Commerce" {{ $organization->industry === 'E-Commerce' ? 'selected' : '' }}>E-Commerce & Retail</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Team Size</label>
                            <select name="size_range" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="1-10" {{ $organization->size_range === '1-10' ? 'selected' : '' }}>1 - 10 employees</option>
                                <option value="11-50" {{ $organization->size_range === '11-50' ? 'selected' : '' }}>11 - 50 employees</option>
                                <option value="51-200" {{ $organization->size_range === '51-200' ? 'selected' : '' }}>51 - 200 employees</option>
                                <option value="200+" {{ $organization->size_range === '200+' ? 'selected' : '' }}>200+ employees</option>
                            </select>
                        </div>

                        <div class="flex justify-end pt-4 border-t">
                            <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                                Next: Invite Team →
                            </button>
                        </div>
                    </form>

                @elseif ($step == 2)
                    <!-- Step 2: Team Invites -->
                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Invite Your Colleagues</h2>
                        <p class="text-sm text-gray-600 mt-1">Generate secure invitation links for your department managers and team members.</p>
                    </div>

                    <form method="POST" action="{{ route('onboarding.process') }}" class="space-y-6" x-data="{ rows: [{ email: '', role: 'employee' }] }">
                        @csrf
                        <input type="hidden" name="step" value="2">

                        <template x-for="(row, index) in rows" :key="index">
                            <div class="flex items-center gap-3">
                                <input type="email" :name="`invites[${index}][email]`" x-model="row.email" placeholder="colleague@company.com" class="flex-1 border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <select :name="`invites[${index}][role]`" x-model="row.role" class="w-40 border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="employee">Employee</option>
                                    <option value="manager">Manager</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </template>

                        <button type="button" @click="rows.push({ email: '', role: 'employee' })" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                            + Add another colleague
                        </button>

                        <div class="flex justify-between pt-4 border-t">
                            <form method="POST" action="{{ route('onboarding.process') }}">
                                @csrf
                                <input type="hidden" name="step" value="3">
                                <button type="submit" class="px-4 py-2 text-gray-600 hover:text-gray-900 text-sm">Skip for now</button>
                            </form>

                            <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                                Next: Finish Setup →
                            </button>
                        </div>
                    </form>

                @else
                    <!-- Step 3: Complete -->
                    <div class="text-center py-6 space-y-4">
                        <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-2xl font-bold">
                            ✓
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900">Your Workspace is Live!</h2>
                        <p class="text-sm text-gray-600 max-w-md mx-auto">
                            {{ $organization->name }}’s enterprise workspace has been configured with CRM pipelines, Task boards, and HR leave workflows.
                        </p>

                        <form method="POST" action="{{ route('onboarding.process') }}" class="pt-4">
                            @csrf
                            <input type="hidden" name="step" value="3">
                            <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-xl shadow-lg hover:bg-indigo-700 transition">
                                Launch Main Dashboard →
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
