# Radical AI — SaaS Production Deployment Roadmap

This document outlines the architectural enhancements, integrations, and infrastructure changes required to elevate **Radical AI** from a prototype to a commercial, production-ready SaaS platform.

---

## 🚀 1. Self-Serve Multi-Tenancy & Team Onboarding

- **Organization Creation Workflow**: Implement a multi-step registration flow (`/register`) where a new customer registers both their User account and their new Organization name/domain.
- **Tenant Isolation**: Replace basic `BelongsToOrganization` trait with strict tenant scoping or single-database tenant middleware (or package like `stancl/tenancy` if subdomain isolation like `acme.radicalai.com` is desired).
- **User Invitations**: Build an email-based team invitation system (`/settings/team/invite`) allowing Admins to invite employees/managers with pre-assigned roles and expiring tokens.

---

## 💳 2. Subscription Billing (Stripe / Laravel Cashier)

- **Laravel Cashier Integration**: Install `laravel/cashier` for seamless Stripe integration.
- **Tiered Plans**: Define subscription tiers (e.g., Free Tier: up to 5 users, Pro Tier: $49/mo up to 25 users, Enterprise Tier: custom).
- **Seat-Based Metering**: Automate billing adjustments when new employees are added.
- **Stripe Webhook Handler**: Securely handle events (`invoice.payment_succeeded`, `customer.subscription_deleted`).
- **Self-Serve Billing Portal**: Connect Settings → Billing to Stripe's Customer Portal for card updates, plan upgrades, and invoice downloads.

---

## 🗄️ 3. Cloud Storage & CDN (AWS S3 / Cloudflare R2)

- **Cloud File Storage**: Switch `FILESYSTEM_DISK` from `local` to `s3` or `r2`.
- **Pre-signed URLs & Secure Downloads**: Serve payslips and employee attachments through temporary signed URLs or authenticated proxy downloads to avoid storing files on ephemeral web app servers.

---

## 📧 4. Transactional Email & Notifications

- **Mail Provider**: Configure `Resend`, `Postmark`, or `AWS SES` for transactional delivery.
- **In-App & Email Notifications**:
  - Email Manager when an Employee submits a Leave Request.
  - Email Employee when Leave is Approved/Rejected.
  - Email Employee when a Task is assigned or mentioned in progress updates.
  - Email Employee when a new Payslip PDF is published.

---

## ⚡ 5. Real-Time Collaboration (Laravel Reverb / WebSockets)

- **Live Kanban Updates**: Integrate **Laravel Reverb** (first-party WebSocket server) so card moves on Task Boards or CRM Pipelines reflect live across all open browser windows without page reloads.

---

## 🛡️ 6. Production Security & Infrastructure

- **Database**: Migrate from SQLite file to **Managed PostgreSQL** or **MySQL 8.0** with connection pooling and automated daily backups.
- **Caching & Queues**: Deploy **Redis** for session management, cache, and queue handling (monitored via **Laravel Horizon**).
- **Security Headers & Rate Limiting**:
  - Enforce `Strict-Transport-Security`, `X-Frame-Options: SAMEORIGIN`, and `Content-Security-Policy`.
  - Apply `throttle:login` rate limiting on authentication routes.
- **Audit Logging**: Create an `audit_logs` table tracking security-sensitive actions (role modifications, login attempts, payslip accesses).

---

## 📊 Summary Checklist for Launch

```text
[ ] 1. Multi-Tenant Registration & Email Team Invites
[ ] 2. Stripe Billing Integration (Laravel Cashier)
[ ] 3. Managed PostgreSQL / MySQL Database Setup
[ ] 4. Redis + Laravel Horizon for Background Queues
[ ] 5. AWS S3 / Cloudflare R2 for Payslip Storage
[ ] 6. Transactional Mailer (Resend / AWS SES)
[ ] 7. Real-Time WebSockets (Laravel Reverb)
[ ] 8. Sentry / Flare Exception Monitoring
```
