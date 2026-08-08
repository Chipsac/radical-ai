@php
    $h = 'font-display text-xl font-semibold text-gray-900 dark:text-white mt-8 mb-2';
    $ul = 'list-disc space-y-1.5 pl-5 marker:text-gold';
@endphp

<x-legal-layout title="Privacy Policy" updated="2 August 2026">

    <p class="rounded-xl border border-gold/40 bg-gold-soft p-4 text-sm">
        <strong class="text-gold">Please read this before you rely on it.</strong>
        This is a plain-English starting point, not legal advice. Crewly360 stores
        payslips and employment records, so have a solicitor or data-protection
        adviser review this against your actual processing before you take on
        customers.
    </p>

    <p>
        This policy explains what personal data Crewly360 collects, why, and what
        rights you have. It covers both people who visit our website and people whose
        details are held inside a customer's workspace.
    </p>

    <h2 class="{{ $h }}">1. Who is responsible for your data</h2>
    <p>
        For our own website and account records, Crewly360 is the <strong>data
        controller</strong>. For information a customer puts into their workspace —
        their employees, contacts and deals — that customer is the controller and we
        are their <strong>processor</strong>: we act on their instructions and do not
        decide what happens to it.
    </p>

    <h2 class="{{ $h }}">2. What we collect</h2>
    <ul class="{{ $ul }}">
        <li><strong>Account details</strong> — name, work email, job title, organisation name, hashed password, and two-factor settings if you enable them.</li>
        <li><strong>Usage and security records</strong> — sign-in times, IP address, browser, and an audit log of security-relevant actions such as role changes.</li>
        <li><strong>Workspace content</strong> — whatever your organisation stores: contacts, deals, tasks, leave requests, employment records and uploaded payslips.</li>
        <li><strong>Enquiries</strong> — if you use the contact form: your name, email, company, phone and message.</li>
    </ul>
    <p>We do not use advertising trackers or third-party analytics cookies.</p>

    <h2 class="{{ $h }}">3. Why we process it, and our lawful basis</h2>
    <ul class="{{ $ul }}">
        <li><strong>To provide the Service</strong> — performance of our contract with you.</li>
        <li><strong>To keep accounts secure</strong> — our legitimate interest in preventing unauthorised access. This covers the audit log and sign-in records.</li>
        <li><strong>To answer enquiries</strong> — your consent, given when you tick the box on the contact form.</li>
        <li><strong>To meet legal obligations</strong> — for example, retaining billing records.</li>
    </ul>

    <h2 class="{{ $h }}">4. Payslips and employment records</h2>
    <p>
        Payslips are sensitive, so they get stricter handling. They are stored in
        private cloud storage that is not publicly reachable, and every download is
        checked against the requester's identity — an employee can only ever retrieve
        their own. Administrators in your organisation can upload payslips and see the
        list; nobody at Crewly360 reads them in the course of normal operation.
    </p>

    <h2 class="{{ $h }}">5. How your data is separated from other customers</h2>
    <p>
        Every record carries the identifier of the organisation it belongs to, and every
        query is filtered by it automatically. The filter is designed to fail closed:
        if the system cannot establish which organisation a request belongs to, it
        returns nothing rather than everything. Attempts to write data across
        organisations are rejected outright.
    </p>

    <h2 class="{{ $h }}">6. Where it is stored</h2>
    <p>
        Data is hosted in the European Union on Google Cloud Platform. Where a
        sub-processor operates outside the EEA, transfers rely on Standard Contractual
        Clauses.
    </p>

    <h2 class="{{ $h }}">7. Who else touches it</h2>
    <ul class="{{ $ul }}">
        <li><strong>Google Cloud</strong> — hosting, database and file storage.</li>
        <li><strong>Our email provider</strong> — delivery of verification, invitation and notification messages.</li>
        <li><strong>Our payment provider</strong> — subscription billing, once paid plans are live. Card details go to them directly and never reach our servers.</li>
    </ul>
    <p>We do not sell personal data, and we do not use it to train machine-learning models.</p>

    <h2 class="{{ $h }}">8. How long we keep it</h2>
    <ul class="{{ $ul }}">
        <li><strong>Workspace content</strong> — for as long as the workspace is open, then 30 days after closure before deletion.</li>
        <li><strong>Security and audit logs</strong> — up to 12 months.</li>
        <li><strong>Contact enquiries</strong> — up to 24 months, unless you ask us to delete sooner.</li>
        <li><strong>Billing records</strong> — as long as tax law requires.</li>
    </ul>

    <h2 class="{{ $h }}">9. Your rights</h2>
    <p>Under the GDPR you can ask us to:</p>
    <ul class="{{ $ul }}">
        <li>give you a copy of your data, in a portable format;</li>
        <li>correct anything inaccurate;</li>
        <li>delete your data, where we have no overriding obligation to keep it;</li>
        <li>restrict or object to certain processing;</li>
        <li>withdraw consent, where consent is the basis we relied on.</li>
    </ul>
    <p>
        If your details sit inside an employer's workspace, ask them first — they
        control that data, and we will help them respond. You can also complain to the
        Irish Data Protection Commission at
        <a href="https://www.dataprotection.ie" class="font-medium text-gold hover:underline">dataprotection.ie</a>.
    </p>

    <h2 class="{{ $h }}">10. Cookies</h2>
    <p>
        We set a session cookie to keep you signed in and a security token to protect
        forms against cross-site request forgery. Your light or dark theme choice is
        remembered in your browser's local storage. None of these track you across
        other websites.
    </p>

    <h2 class="{{ $h }}">11. Security incidents</h2>
    <p>
        If a breach affects your personal data and is likely to present a risk, we will
        notify the relevant supervisory authority within 72 hours of becoming aware, and
        tell affected customers without undue delay.
    </p>

    <h2 class="{{ $h }}">12. Changes</h2>
    <p>
        We will post any update here and change the date at the top. For material
        changes we will email workspace administrators.
    </p>

    <h2 class="{{ $h }}">13. How to contact us</h2>
    <p>
        To exercise any of the rights above, ask a question about this policy, or
        report a concern, email
        <a href="mailto:support@crewly360.com" class="font-medium text-gold hover:underline">support@crewly360.com</a>.
        We aim to respond within one month, as required under the GDPR.
    </p>
    <p>
        If you are not satisfied with our response, you can complain to the Irish Data
        Protection Commission at
        <a href="https://www.dataprotection.ie" class="font-medium text-gold hover:underline" target="_blank" rel="noopener">dataprotection.ie</a>.
    </p>

</x-legal-layout>
