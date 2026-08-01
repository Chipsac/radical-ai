@component('mail::message')
# You've been invited

**{{ $inviterName }}** has invited you to join **{{ $organizationName }}** on Radical AI
as a **{{ $role }}**.

Radical AI brings your CRM, task tracking and HR into one place, so your team
works from a single set of people and projects.

@component('mail::button', ['url' => $acceptUrl, 'color' => 'primary'])
Accept invitation
@endcomponent

You'll be asked to choose a password, and then you're in.

@component('mail::panel')
This invitation expires on **{{ $expiresAt->format('j F Y') }}**.
If it lapses, ask {{ $inviterName }} to send a fresh one.
@endcomponent

If you weren't expecting this, you can safely ignore this email — no account is
created until you accept.

Thanks,<br>
The Radical AI team

<small style="color:#888">
If the button doesn't work, copy and paste this link into your browser:<br>
{{ $acceptUrl }}
</small>
@endcomponent
