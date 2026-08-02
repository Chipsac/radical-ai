# Radical AI — Production Readiness & Architectural Execution Plan (`prod.md`)

This execution plan outlines the technical roadmap to transform **Radical AI** from a prototype multi-tenant SaaS into an enterprise-grade, highly available, secure, and scalable production application on Google Cloud Platform.

---

## 📑 Roadmap Overview & Execution Phases

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                                   EXECUTION PHASES                                      │
├───────────────────┬───────────────────┬───────────────────┬─────────────────────────────┤
│ Phase 1: Security │ Phase 2: Infra    │ Phase 3: Queues   │ Phase 4: Observability,     │
│ & Multi-Tenancy   │ & High Avail (HA) │ & Distributed Cache│ SaaS Engine & Architecture  │
└───────────────────┴───────────────────┴───────────────────┴─────────────────────────────┘
```

---

## 🔒 Phase 1: Security & Defense-in-Depth Hardening

### 1.1 HTTP Security Headers Middleware
- [ ] Create `App\Http\Middleware\SecurityHeaders.php` to enforce production security headers on all responses:
  - `Content-Security-Policy (CSP)` (strict script/style/frame origins)
  - `Strict-Transport-Security (HSTS)` (`max-age=31536000; includeSubDomains; preload`)
  - `X-Frame-Options: DENY`
  - `X-Content-Type-Options: nosniff`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Permissions-Policy` (camera=(), microphone=(), geolocation=())
- [ ] Register `SecurityHeaders` in the `web` group in [`bootstrap/app.php`](file:///c:/Users/anura/Downloads/AI%20Coding/RadicalAI/bootstrap/app.php).

### 1.2 Distributed Rate Limiting Across Replicas
- [ ] Configure Redis throttling for sensitive endpoints:
  - `POST /login` (5 attempts per minute per IP/email)
  - `POST /two-factor-challenge` (5 attempts per minute)
  - `POST /register` (3 requests per hour per IP)
  - `POST /contact` (2 enquiries per 5 minutes)
- [ ] Ensure rate limits utilize shared Redis cache so limits sync across all auto-scaled Cloud Run container instances.

### 1.3 PostgreSQL Row-Level Security (RLS)
- [ ] Implement database-level RLS policies on multi-tenant tables (`organizations`, `users`, `employees`, `deals`, `accounts`, `contacts`, `tasks`, `leave_requests`, `payslips`):
  ```sql
  ALTER TABLE deals ENABLE ROW LEVEL SECURITY;
  CREATE POLICY tenant_isolation_policy ON deals
      USING (organization_id = NULLIF(current_setting('app.current_organization_id', true), '')::BIGINT);
  ```
- [ ] Create a database middleware/listener to set `app.current_organization_id` on every PDO connection automatically from [`TenantContext`](file:///c:/Users/anura/Downloads/AI%20Coding/RadicalAI/app/Support/TenantContext.php).

### 1.4 Upload File Security & Virus Scanning
- [ ] Add strict MIME-type signature verification (finfo) and file extension sanitization on payslip and document upload endpoints.
- [ ] Implement an asynchronous virus scanning step using ClamAV or GCP Virus Scanning API before storing uploaded files to Cloud Storage.

---

## 🏗️ Phase 2: Infrastructure & High Availability (HA) Upgrades

### 2.1 Cloud SQL Production HA & Disaster Recovery
- [ ] Update [`infra/main.tf`](file:///c:/Users/anura/Downloads/AI%20Coding/RadicalAI/infra/main.tf):
  - Change `availability_type` from `ZONAL` to `REGIONAL` (automated cross-zone failover).
  - Enable `point_in_time_recovery_enabled = true` with 7-day retention.
  - Upgrade `disk_type` from `PD_HDD` to `PD_SSD` for production performance.
  - Configure `database_flags` (`max_connections`, `log_disconnections`, `log_checkpoints`).

### 2.2 Private IP & Serverless VPC Connector
- [ ] Provision Google Cloud Serverless VPC Access Connector in [`infra/main.tf`](file:///c:/Users/anura/Downloads/AI%20Coding/RadicalAI/infra/main.tf).
- [ ] Set `ipv4_enabled = false` on Cloud SQL and enforce private IP routing (`private_network = google_compute_network.vpc.id`).
- [ ] Attach VPC Access Connector to the Cloud Run service definition.

### 2.3 Database Connection Pooling with PgBouncer
- [ ] Deploy PgBouncer sidecar or Cloud SQL Auth Proxy connection pooler to handle container auto-scaling concurrency without exhausting database connection limits.

---

## ⚡ Phase 3: Distributed Caching, Decoupled Queues & Performance

### 3.1 GCP Memorystore (Redis) Provisioning
- [ ] Add `google_redis_instance` resource to [`infra/main.tf`](file:///c:/Users/anura/Downloads/AI%20Coding/RadicalAI/infra/main.tf).
- [ ] Update production environment variables:
  - `CACHE_STORE=redis`
  - `SESSION_DRIVER=redis`
  - `QUEUE_CONNECTION=redis`
  - `REDIS_CLIENT=phpredis`

### 3.2 Decoupled Worker Deployment
- [ ] Create dedicated Cloud Run Service definition for Queue Workers (`radical-ai-worker`).
- [ ] Update entrypoint to execute `php artisan queue:work --tries=3 --timeout=90` independently from the Web HTTP container.
- [ ] Alternatively, integrate Laravel Cloud Tasks driver for serverless event execution.

---

## 📊 Phase 4: Observability & Operational Readiness

### 4.1 Structured JSON Logging for GCP Cloud Logging
- [ ] Configure `LOG_CHANNEL=stderr` with JSON formatter in `config/logging.php` to include:
  - `severity` (DEBUG, INFO, WARNING, ERROR, CRITICAL)
  - `tenant_id` (from [`TenantContext`](file:///c:/Users/anura/Downloads/AI%20Coding/RadicalAI/app/Support/TenantContext.php))
  - `user_id`
  - `trace_id` (W3C trace context)

### 4.2 Centralized Error Reporting & APM
- [ ] Integrate Sentry or Google Cloud Error Reporting SDK into `bootstrap/app.php`.
- [ ] Configure uptime checks and alert notifications (email/Slack/PagerDuty) on `/up` and `/health` endpoints.

### 4.3 Deep Diagnostic Health Endpoint
- [ ] Implement expanded `/health` endpoint checking:
  - Database ping & query latency
  - Redis cache ping
  - Storage bucket read/write check
  - Queue lag monitoring

---

## 💼 Phase 5: SaaS Engine (Billing, Quotas, Subdomains & Compliance)

### 5.1 Payment Gateway & Subscription Engine
- [ ] Integrate Stripe / Paddle SDK for subscription billing lifecycle:
  - Webhooks for `invoice.paid`, `invoice.payment_failed`, `customer.subscription.deleted`.
  - Seat-based billing per active employee.
- [ ] Add quota middleware (`EnforceTenantQuotas`) enforcing plan limits:
  - Startup Plan: max 15 employees, 5GB storage.
  - Enterprise Plan: unlimited employees, 100GB storage.

### 5.2 Custom Domain & Subdomain Routing
- [ ] Implement subdomain tenant resolution middleware (`{tenant}.radicalai.com`).
- [ ] Support custom domain CNAME routing with automated Google Cloud SSL certificate issuance.

### 5.3 Data Portability & GDPR Compliance
- [ ] Create automated Tenant Data Export tool (`php artisan tenant:export {organization_id}`) returning encrypted ZIP containing JSON dumps and stored files.
- [ ] Create automated Tenant Hard-Purge routine adhering to 30-day retention policies.

---

## 🏛️ Phase 6: Service Layer Refactoring & Domain Architecture

### 6.1 Action / Service Objects
- [ ] Refactor multi-step controllers into dedicated domain actions:
  - `App\Domain\Organization\Actions\ProvisionTenantAction`
  - `App\Domain\Team\Actions\InviteTeamMemberAction`
  - `App\Domain\Auth\Actions\VerifyTotpAction`

### 6.2 Event-Driven Decoupling
- [ ] Convert synchronous side effects to asynchronous event listeners:
  - `OrganizationProvisioned` -> `SeedDemoDataListener`, `SendWelcomeEmailListener`
  - `InvitationCreated` -> `SendInvitationEmailListener`
  - `PayslipUploaded` -> `ProcessPayslipNotificationListener`

---

## ✅ Verification & Test Plan

1. **Automated Unit & Feature Tests**:
   - Run `php artisan test --parallel` to ensure 100% pass rate across all feature, security, and tenant isolation tests.
2. **Multi-Tenant Security Audit**:
   - Verify zero cross-tenant data access across all REST endpoints using automated security tests in [`TenantIsolationTest.php`](file:///c:/Users/anura/Downloads/AI%20Coding/RadicalAI/tests/Feature/TenantIsolationTest.php).
3. **Load & Scaling Test**:
   - Execute load testing with k6 or Locust simulating 500 concurrent users against Cloud Run + Cloud SQL + Redis.
4. **Disaster Recovery Drill**:
   - Perform automated database failover drill and PITR recovery validation.
