# Radical AI — Running Prototype

An all-in-one SaaS prototype combining **CRM**, **HR + payslips**, and a **Task Tracker** in a single Laravel app. Data persists in a local SQLite database — drag a deal or a task and it stays where you dropped it.

Built with Laravel 12, Breeze (Blade + Tailwind + Alpine), spatie/laravel-permission, and SortableJS.

## Setup

Requires PHP 8.2+ (with `pdo_sqlite`), Composer, and Node 18+.

```bash
composer install
cp .env.example .env          # already configured for SQLite + log mailer
php artisan key:generate
touch database/database.sqlite # if the file doesn't exist yet
php artisan migrate --seed     # builds schema + demo data (incl. sample payslip PDFs)

# Run (two terminals)
npm install
npm run dev
php artisan serve
```

Then open http://localhost:8000.

> Re-seed from scratch at any time with `php artisan migrate:fresh --seed`.

## Demo logins

The login page has one-click **"Try the demo as…"** buttons for all three roles. Password for every seeded account is `password`.

| Role | Email | Can do |
|---|---|---|
| Admin | `admin@acme.test` | Everything, incl. payslip upload + Settings |
| Manager | `manager@acme.test` | Team CRM/tasks, leave approvals |
| Employee | `employee@acme.test` | Own tasks, own leave, own payslips |

## What to try

1. **Tasks → Board**: drag a card between status columns (persists), open a task, post a progress update, log time, create a new task from the modal (gets an auto reference like `IT-2026-JUL-023-SK`).
2. **CRM → Deal Pipeline**: drag deals between stages, open a deal and click **Mark won** — a delivery project plus onboarding tasks are created and assigned to employees who are *not* on approved leave.
3. **HR**: submit a leave request as Employee → approve it as Manager → see it appear on the Calendar and the person flip to "On leave". Upload a payslip as Admin → log in as that employee to download it (employees only ever see their own).
4. **Reports**: pick members and generate/download a task progress report.
5. Refresh anything — it's all stored in `database/database.sqlite`.

## Prototype stubs (intentional)

- **Billing**: Settings → Billing shows the plan with a disabled "Manage subscription" button (no Stripe).
- **Payroll/Revenue**: not built — payslips are uploaded PDFs, no tax calculation.
- **Email**: `MAIL_MAILER=log` (nothing is actually sent).
- **Multi-tenancy**: one seeded org ("Acme Startup Ltd"); every table carries `organization_id` and queries are scoped via a `BelongsToOrganization` trait, so the multi-tenant shape is preserved.
