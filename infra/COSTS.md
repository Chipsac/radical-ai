# What this actually costs to run

Figures are Google Cloud list prices for `europe-west1` as of mid-2026, in EUR.
Treat them as close estimates, not a quote — check the
[pricing calculator](https://cloud.google.com/products/calculator) for your own
region and usage.

## The short version

Default configuration — **all Google Cloud** (Cloud Run, Cloud SQL, Cloud
Storage, Secret Manager, Gmail SMTP):

| Usage | Monthly cost |
|---|---|
| **First 90 days** | **€0** — covered by the $300 new-customer credit |
| **Idle** thereafter | **~€8–10** — Cloud SQL, which never sleeps |
| **Real** (25 users, ~50k requests) | **~€10–13** |
| **Busy** (500k requests) | **~€20–28** |

**There is no always-free tier for Cloud SQL.** It is covered by the $300
credit and by a separate 30-day Cloud SQL trial instance, then it bills
whether or not anyone uses the app.

Everything else in the stack bills per use and genuinely falls to zero when
idle — so the database is effectively the entire running cost.

### If you want a genuine €0 idle bill

The only way to get there is to move the database off Google Cloud, because no
Google database is both free and SQL:

```hcl
use_cloud_sql = false
database_url  = "postgres://…"   # e.g. a free Neon project
```

That drops idle cost to **€0** and busy cost to roughly €12–18. Nothing in the
application changes. It is the one trade-off between "all Google" and "free".

For completeness: Firestore *does* have an always-free tier, but it is a
NoSQL document store. This application is relational — deals joined to
accounts, tasks to projects, leave to employees — so moving to Firestore would
mean rewriting the data layer rather than changing a connection string.

## Line by line

### Cloud Run — ~€0 at low usage

Billed per 100ms of request processing, plus per request. The free tier
(2 million requests, 360k GB-seconds, 180k vCPU-seconds per month) covers a
demo and most early production usage outright.

The key setting is `min_instances = 0`: with no traffic there are no
instances, and no instances means no charge. The trade-off is a cold start of
roughly 1–3 seconds on the first request after an idle period. OPcache and
cached config/routes/views keep that as short as possible.

Setting `min_instances = 1` removes cold starts but costs roughly **€13/month**
for the always-on instance — usually not worth it before you have real users.

### Database — the only decision that really matters

**Default: Cloud SQL — ~€8–10/month, always on.**
Terraform provisions `db-f1-micro` with 10 GB of HDD storage: the smallest and
cheapest configuration available. Everything stays inside Google Cloud with no
third-party account. **Cloud SQL has no scale-to-zero**, so it costs the same
whether you have one user or none.

Two things soften the first months:

- The **$300 new-customer credit** covers roughly 90 days of this entire stack.
- Google also offers a separate **30-day Cloud SQL trial instance**, far larger
  than `db-f1-micro`. Useful for load testing, but it expires.

`database_high_availability` adds REGIONAL failover, SSD storage and
point-in-time recovery. It roughly doubles the database cost — leave it off
until an outage would cost you more than the upgrade does.

**Alternative: external serverless Postgres — €0 idle.**
Set `use_cloud_sql = false` and `database_url` to a connection string from a
provider whose free tier genuinely scales to zero (Neon, Supabase). Terraform
stores the URL in Secret Manager and skips Cloud SQL entirely. The application
code is identical; only the connection string changes.

### Cloud Storage — cents

Payslip PDFs are small. 1 GB of standard regional storage is about €0.02/month.
Realistically under €0.10 unless you store tens of thousands of documents.

### Artifact Registry — ~€0.10/month

Container images, roughly 250 MB each. The cleanup policy in `main.tf` keeps
only the 5 most recent and deletes anything over 30 days, so this stays flat
instead of growing with every deploy.

### Secret Manager — ~€0.12/month

€0.06 per secret version per month, and we store two.

### Cloud Build — free

120 build-minutes per day are free. A build here takes 3–5 minutes, so unless
you deploy more than about 20 times a day you will not be billed.

### Email — free

Google Cloud has no transactional email service of its own, and blocks
outbound port 25, so mail has to leave through SMTP somewhere else. The
all-Google option is **Gmail SMTP with an App Password**: free, and around 500
messages a day — far more than signup verification and invitations will use
early on.

If you outgrow that, or want a custom sending domain with proper SPF and DKIM,
Resend and Brevo both have free tiers in the thousands per month. Only the four
`mail_*` values in `terraform.tfvars` change.

## Keeping the bill down

The controls are all in `terraform.tfvars`:

- **`min_instances = 0`** — the single most important setting. Leave it at 0.
- **`max_instances = 10`** — a hard ceiling. Even under an unexpected traffic
  spike (or a bot), you cannot be billed for more than 10 instances at once.
- **`concurrency = 80`** — each instance serves up to 80 simultaneous requests,
  so fewer instances are needed for the same traffic.
- **`database_tier`** — `db-f1-micro` is the cheapest. Only move up when the
  database is genuinely the bottleneck.

Worth setting up regardless:

```bash
# A budget alert so there are no surprises
gcloud billing budgets create \
  --billing-account=YOUR_BILLING_ACCOUNT_ID \
  --display-name="Crewly360" \
  --budget-amount=25EUR \
  --threshold-rule=percent=50 \
  --threshold-rule=percent=90
```

## Tearing it down

```bash
cd infra
terraform destroy
```

The database has `deletion_protection = true`, so Terraform will refuse to
delete it until you set `database_deletion_protection = false` and re-apply.
That is deliberate — it is the difference between destroying an environment and
destroying your customers' data.
