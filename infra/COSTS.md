# What this actually costs to run

Figures are Google Cloud list prices for `europe-west1` as of mid-2026, in EUR.
Treat them as close estimates, not a quote — check the
[pricing calculator](https://cloud.google.com/products/calculator) for your own
region and usage.

## The short version

With the default configuration (`use_cloud_sql = false`, external serverless
Postgres):

| Usage | Realistic monthly cost |
|---|---|
| **Idle** (deployed, nobody using it) | **€0** |
| **Light** (a demo, a few hundred visits) | **~€0.30** |
| **Real** (25 users, ~50k requests) | **~€1–3** |
| **Busy** (500k requests) | **~€12–18** |

With `use_cloud_sql = true` add roughly **€8–10/month flat**, because Cloud SQL
never sleeps.

The cost model is simple: everything except the database bills per use and
falls to zero when idle. So the database choice *is* the cost decision.

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

**Default: external serverless Postgres — €0 idle.**
Set `database_url` in `terraform.tfvars` to a connection string from a provider
whose free tier genuinely scales to zero:

- **Neon** — free tier, scales to zero, ~0.5 GB storage. No card required.
- **Supabase** — free tier, 500 MB.

Terraform stores the URL in Secret Manager and skips Cloud SQL entirely. The
application code is identical either way; only the connection changes.

**Alternative: Cloud SQL — ~€8–10/month, always.**
Set `use_cloud_sql = true` and Terraform provisions `db-f1-micro` with 10 GB of
HDD storage. Everything stays inside Google Cloud with no third-party account,
but **Cloud SQL has no scale-to-zero** — it costs the same whether you have one
user or none.

Turning on `database_high_availability` adds REGIONAL failover, SSD storage and
point-in-time recovery. It roughly doubles the database cost, so leave it off
until an outage would cost you more than the upgrade.

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

### Email — free at this scale

Resend's free tier covers 3,000 messages a month, which is far more than
signup verification and team invitations will use early on. Brevo and AWS SES
have comparable free allowances.

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
  --display-name="Radical AI" \
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
