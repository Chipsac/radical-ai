<x-app-layout>
    <x-slot name="title">Team</x-slot>

    <div class="mx-auto max-w-4xl">
        <h1 class="font-display text-3xl font-semibold text-gray-900 dark:text-white">Team</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Everyone with access to this workspace.</p>

        <x-help-banner id="tip:team"
            title="Roles decide what people see"
            text="Admins manage settings, billing and payslips. Managers see the whole team's work and approve leave. Employees see only their own tasks, leave and payslips. You can change someone's role at any time." />

        <div class="card mt-6 p-6">
            <h2 class="font-display text-lg font-semibold">Invite someone</h2>
            <form method="POST" action="{{ route('invitations.store') }}" class="mt-4 flex flex-wrap items-end gap-3">
                @csrf
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
                <button type="submit" class="btn-gold">Send invite</button>
            </form>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="card mt-6">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-ink-700">
                <h2 class="font-display text-lg font-semibold">Members ({{ $members->count() }})</h2>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-ink-700">
                @foreach ($members as $member)
                    <div class="flex items-center gap-3 px-5 py-3">
                        <x-avatar :user="$member" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $member->name }}</p>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $member->email }}</p>
                        </div>
                        @if ($member->hasTwoFactorEnabled())
                            <span class="rounded-full bg-status-done/15 px-2 py-0.5 text-[11px] font-semibold text-status-done">2FA</span>
                        @endif
                        <span class="rounded-full bg-gold-soft px-2.5 py-0.5 text-xs font-semibold capitalize text-gold">{{ $member->role }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($invitations->isNotEmpty())
            <div class="card mt-6">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-ink-700">
                    <h2 class="font-display text-lg font-semibold">Invitations</h2>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-ink-700">
                    @foreach ($invitations as $inv)
                        <div class="flex items-center gap-3 px-5 py-3 text-sm">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ $inv->email }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Invited by {{ $inv->inviter?->name ?? 'someone' }} ·
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
