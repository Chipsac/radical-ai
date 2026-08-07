# Crewly360

An all-in-one SaaS combining **CRM**, **HR + payslips**, and a **Task Tracker** in
one multi-tenant application. Built with Laravel 12, deployed serverless on
Google Cloud Run.

The point of the product: three modules share one team and one database, so the
person who wins a deal is the same person delivering the project and booking
annual leave — no re-entering the same people into three systems.

---

## Running it locally

Requires PHP 8.2+ (with `pdo_sqlite`), Composer, and Node 18+.

```bash
composer install
```

```bash
cp .env.example .env && php artisan key:generate && touch database/database.sqlite
```

```bash
php artisan migrate --seed
```

```bash
npm install && npm run build && php artisan serve
```

Open http://localhost:8000.

In local development the login page offers one-click demo logins (Admin /
Manager / Employee, password `password`). **Those routes do not exist in
production** — they are registered only in the `local` and `testing`
environments.

---

## Deploying

```bash
./deploy.sh <your-gcp-project-id> europe-west1
```

One command provisions the infrastructure, builds the image, deploys it and
verifies the health check. Full walkthrough in **[DEPLOYMENT.md](DEPLOYMENT.md)**;
cost breakdown in **[infra/COSTS.md](infra/COSTS.md)** (roughly €8–12/month,
almost all of it the database — Cloud Run itself scales to zero).

---

## Architecture

| Concern | Choice | Why |
|---|---|---|
| Compute | Cloud Run | Scales to zero; you pay per request, not per hour |
| Database | Cloud SQL (PostgreSQL 16) | Private IP only, reached over the Cloud SQL socket |
| File storage | Cloud Storage | Cloud Run containers are ephemeral |
| Secrets | Secret Manager | Never in the image, never in Terraform output |
| Sessions/cache/queue | Database | Any instance can serve any request |
| Infrastructure | Terraform | `infra/` — reproducible, reviewable |

### Multi-tenancy

Every domain table carries an `organization_id`, and a global scope filters
every query by the active tenant. Two properties matter:

- **It fails closed.** With no tenant context a query returns *nothing* rather
  than everything. A missing scope is a bug, not a data breach.
- **Cross-tenant writes are refused.** Passing another tenant's id explicitly
  throws rather than silently writing.

Background jobs and console commands declare their tenant explicitly via
`TenantContext::asTenant()`. Genuinely cross-tenant work must opt out through
`withoutTenancy()`, which makes those places easy to find and review.

### Authentication

- Organisation sign-up provisions a tenant, its owning admin, and baseline
  reference data in a single transaction
- Email verification, and a production password policy (12+ characters, mixed
  case, number, symbol, checked against known breached passwords)
- **Two-factor authentication** — TOTP (RFC 6238), implemented directly and
  [verified against the RFC's published test vectors](tests/Unit/TotpServiceTest.php),
  with single-use recovery codes
- Team invitations with unguessable, expiring tokens
- Role-based access: admin / manager / employee
- An audit log of security-relevant events

### Onboarding and in-app help

New organisations are walked through a three-step wizard (profile → invite team
→ starting point), with progress stored server-side so it survives closing the
tab. Throughout the app there are contextual `?` tooltips, dismissible
first-visit explainers, and per-page guided tours, plus a searchable help
drawer. Dismissals are stored per user, so they persist across devices.

---

## Tests

```bash
php artisan test
```

67 tests covering tenant isolation, the two-factor loop, onboarding, CRM and
leave workflows, plus the TOTP implementation against RFC 6238 vectors.

---

## Project layout

```
app/
  Http/Controllers/     CRM, Tasks, HR, onboarding, help, auth
  Models/               Domain models; Concerns/BelongsToOrganization
  Services/             TenantProvisioner, TotpService, DemoDataSeeder
  Support/              TenantContext, HelpLibrary
docker/                 nginx, php-fpm, supervisor, entrypoint
infra/                  Terraform: Cloud Run, Cloud SQL, GCS, IAM, secrets
tests/                  Feature and unit tests
deploy.sh               One-command deploy
DEPLOYMENT.md           Deployment guide
```
