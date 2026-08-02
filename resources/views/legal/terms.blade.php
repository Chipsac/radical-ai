@php
    // Headings and body share styling via these small helpers so the two legal
    // pages stay visually consistent without a CSS framework plugin.
    $h = 'font-display text-xl font-semibold text-gray-900 dark:text-white mt-8 mb-2';
    $ul = 'list-disc space-y-1.5 pl-5 marker:text-gold';
@endphp

<x-legal-layout title="Terms of Service" updated="2 August 2026">

    <p class="rounded-xl border border-gold/40 bg-gold-soft p-4 text-sm">
        <strong class="text-gold">Please read this before you rely on it.</strong>
        These terms are a plain-English starting point, not legal advice. Before
        taking paying customers you should have a solicitor review them against
        your actual business and jurisdiction.
    </p>

    <p>
        These terms govern your use of Radical AI ("the Service"), a web application
        combining customer relationship management, task tracking and HR tools.
        By creating a workspace you agree to them.
    </p>

    <h2 class="{{ $h }}">1. Your account and workspace</h2>
    <ul class="{{ $ul }}">
        <li>You must give accurate information when signing up, and keep your login details secure.</li>
        <li>The person who creates a workspace is its administrator and controls who else may join.</li>
        <li>You are responsible for everything done under accounts in your workspace.</li>
        <li>You must be at least 16 years old, or have permission from your employer to use the Service on their behalf.</li>
    </ul>

    <h2 class="{{ $h }}">2. Your data belongs to you</h2>
    <p>
        You keep all rights to the information you put into Radical AI. We store and
        process it only to provide the Service to you. We do not sell it, and we do
        not use it to train machine-learning models.
    </p>
    <p>
        You can request a copy of your workspace data, or ask us to delete it, at any
        time. See the <a href="{{ route('legal.privacy') }}" class="font-medium text-gold hover:underline">Privacy Policy</a>.
    </p>

    <h2 class="{{ $h }}">3. Acceptable use</h2>
    <p>You agree not to:</p>
    <ul class="{{ $ul }}">
        <li>Break the law, or use the Service to help someone else do so.</li>
        <li>Upload malware, or attempt to gain access to another organisation's workspace.</li>
        <li>Probe, scan or load-test the Service without our written permission.</li>
        <li>Resell or white-label the Service without an agreement with us.</li>
    </ul>
    <p>We may suspend a workspace that puts the Service or other customers at risk.</p>

    <h2 class="{{ $h }}">4. Trials, plans and payment</h2>
    <ul class="{{ $ul }}">
        <li>New workspaces get a free trial. No card is required and nothing is charged automatically when it ends.</li>
        <li>Paid plans are billed in advance. Prices exclude VAT, which is added where applicable.</li>
        <li>You can cancel at any time. Cancelling stops future charges; we do not refund part-months unless the law requires it.</li>
        <li>We will give at least 30 days' notice by email before changing a price.</li>
    </ul>

    <h2 class="{{ $h }}">5. Availability</h2>
    <p>
        We work to keep the Service available and to back up your data, but we do not
        promise uninterrupted service. We may take it offline for maintenance, and
        will give notice where we reasonably can.
    </p>

    <h2 class="{{ $h }}">6. Payslips and HR records</h2>
    <p>
        Radical AI distributes payslips that you upload; it does not calculate pay or
        tax. You remain responsible for the accuracy of payroll figures and for meeting
        your employment and tax obligations.
    </p>

    <h2 class="{{ $h }}">7. Ending your use</h2>
    <ul class="{{ $ul }}">
        <li>You may close your workspace at any time from Settings, or by asking us.</li>
        <li>We may end an account that seriously or repeatedly breaches these terms, with notice where practical.</li>
        <li>After closure we keep your data for 30 days so it can be recovered by mistake-correction, then delete it.</li>
    </ul>

    <h2 class="{{ $h }}">8. Liability</h2>
    <p>
        The Service is provided as-is. To the extent the law allows, our total liability
        to you in any 12-month period is limited to what you paid us in that period. We
        are not liable for lost profits or lost data beyond that limit. Nothing here
        limits liability that cannot lawfully be limited.
    </p>

    <h2 class="{{ $h }}">9. Changes to these terms</h2>
    <p>
        We may update these terms. For material changes we will email workspace
        administrators at least 30 days beforehand. Continuing to use the Service after
        a change means you accept it.
    </p>

    <h2 class="{{ $h }}">10. Governing law</h2>
    <p>
        These terms are governed by the laws of Ireland, and the courts of Ireland have
        jurisdiction over any dispute.
    </p>

</x-legal-layout>
