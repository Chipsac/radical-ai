@php use App\Support\Modules; @endphp

<x-app-layout>
    <x-slot name="title">Team</x-slot>

    <div class="mx-auto max-w-5xl">
        <h1 class="font-display text-3xl font-semibold text-gray-900 dark:text-white">Team</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Who is in this workspace, what they can do, and what they can see.</p>

        <x-help-banner id="tip:team"
            title="Roles and modules are separate controls"
            text="A role decides how much someone can do — an employee sees their own work, a manager sees the whole team. Modules decide which parts of the product they can open at all. So you can give someone CRM only, or the tracker and HR but not CRM. Admins always have everything." />

        {{-- ---------------- Invite ---------------- --}}
        <div class="card mt-6 p-6">
            <h2 class="font-display text-lg font-semibold">Invite someone</h2>
            <form method="POST" action="{{ route('invitations.store') }}" class="mt-4 space-y-4">
                @csrf
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-56 flex-1">
                        <label class="label-app">Email</label>
                        <input name="email" type="email" required class="input-app" placeholder="colleague@company.com" />
                    </div>
                    <div class="w-40">
                        <label class="label-app">Role</label>
                        <select name="role" class="input-app">
                            <option value="employee">Employee</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="w-44">
                        <label class="label-app">Job title</label>
                        <input name="job_title" class="input-app" placeholder="Optional" />
                    </div>
                </div>

                <div>
                    <label class="label-app">Modules they can open</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach (Modules::catalogue() as $key => $meta)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="modules[]" value="{{ $key }}" class="peer sr-only" checked>
                                <span class="block rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium transition peer-checked:border-gold peer-checked:bg-gold-soft peer-checked:text-gold dark:border-ink-600">
                                    {{ $meta['name'] }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">You can change this any time after they join.</p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn-gold">Send invite</button>
                </div>
            </form>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- ---------------- Members ---------------- --}}
        <div class="card mt-6">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-ink-700">
                <h2 class="font-display text-lg font-semibold">Members ({{ $members->count() }})</h2>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-ink-700">
                @foreach ($members as $member)
                    <div class="px-5 py-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <x-avatar :user="$member" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">
                                    {{ $member->name }}
                                    @if ($member->id === auth()->id())
                                        <span class="ml-1 text-xs text-gray-400">(you)</span>
                                    @endif
                                </p>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $member->email }}</p>
                            </div>

                            @if ($member->hasTwoFactorEnabled())
                                <span class="rounded-full bg-status-done/15 px-2 py-0.5 text-[11px] font-semibold text-status-done" title="Two-factor authentication is on">2FA</span>
                            @endif

                            {{-- Role --}}
                            <form method="POST" action="{{ route('team.role', $member) }}">
                                @csrf @method('PATCH')
                                <select name="role" onchange="this.form.submit()"
                                        class="input-app !w-32 !py-1.5 text-xs"
                                        @disabled($member->id === auth()->id())>
                                    @foreach (['employee' => 'Employee', 'manager' => 'Manager', 'admin' => 'Admin'] as $value => $label)
                                        <option value="{{ $value }}" @selected($member->role === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>

                        {{-- Module grants --}}
                        <div class="mt-3 pl-11">
                            @if ($member->isAdmin())
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <span class="font-semibold text-gold">All modules</span> — admins always have full access.
                                </p>
                            @else
                                <form method="POST" action="{{ route('team.modules', $member) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf @method('PATCH')
                                    @foreach (Modules::catalogue() as $key => $meta)
                                        <label class="cursor-pointer">
                                            <input type="checkbox" name="modules[]" value="{{ $key }}"
                                                   class="peer sr-only" @checked($member->hasModule($key))>
                                            <span class="block rounded-lg border border-gray-300 px-2.5 py-1 text-xs font-medium transition peer-checked:border-gold peer-checked:bg-gold-soft peer-checked:text-gold dark:border-ink-600"
                                                  title="{{ $meta['description'] }}">
                                                {{ $meta['name'] }}
                                            </span>
                                        </label>
                                    @endforeach
                                    <button type="submit" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-gold transition hover:bg-gold/10">
                                        Save access
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @error('modules') <p class="mt-2 text-sm text-priority-high">{{ $message }}</p> @enderror
        @error('role') <p class="mt-2 text-sm text-priority-high">{{ $message }}</p> @enderror

        {{-- ---------------- Invitations ---------------- --}}
        @if ($invitations->isNotEmpty())
            <div class="card mt-6">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-ink-700">
                    <h2 class="font-display text-lg font-semibold">Invitations</h2>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-ink-700">
                    @foreach ($invitations as $inv)
                        <div class="flex flex-wrap items-center gap-3 px-5 py-3 text-sm">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ $inv->email }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ ucfirst($inv->role) }} ·
                                    {{ $inv->modules ? collect($inv->modules)->map(fn ($m) => Modules::label($m))->join(', ') : 'all modules' }} ·
                                    {{ $inv->status() === 'pending' ? 'expires '.$inv->expires_at->diffForHumans() : $inv->status() }}
                                </p>
                            </div>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold
                                {{ $inv->status() === 'accepted' ? 'bg-status-done/15 text-status-done'
                                   : ($inv->status() === 'expired' ? 'bg-gray-100 text-gray-500 dark:bg-ink-700' : 'bg-gold-soft text-gold') }}">
                                {{ $inv->status() }}
                            </span>
                            @if ($inv->isPending())
                                <form method="POST" action="{{ route('invitations.destroy', $inv) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-gray-400 hover:text-priority-high">Revoke</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
