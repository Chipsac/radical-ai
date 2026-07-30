# Deploying Radical AI to Google Cloud Run

From nothing to a live public URL in about 15 minutes, most of which is waiting
for the first build.

---

## Before you start

You need three things:

1. **A Google Cloud account with billing enabled.** New accounts get $300 of
   free credit. Billing must be on even though the running cost is a few euro a
   month — Cloud Run and Cloud SQL are not available on a billing-free project.
2. **The `gcloud` CLI** — [install guide](https://cloud.google.com/sdk/docs/install)
3. **Terraform 1.6+** — [install guide](https://developer.hashicorp.com/terraform/install)

You do **not** need Docker locally: the image is built by Cloud Build.

Check everything is present:

```bash
gcloud version && terraform version
```

---

## Step 1 — Sign in and pick a project

```bash
gcloud auth login
```

```bash
gcloud projects create radical-ai-prod --name="Radical AI"
```

If you already have a project, skip that and note its id. Then link billing —
this must be done in the console, since it involves your payment account:
[console.cloud.google.com/billing](https://console.cloud.google.com/billing)

---

## Step 2 — Deploy

From the repository root:

```bash
./deploy.sh radical-ai-prod europe-west1
```

That single command:

1. Enables the required Google Cloud APIs
2. Provisions everything with Terraform — Cloud Run, Cloud SQL, Cloud Storage,
   Secret Manager, Artifact Registry, and a least-privilege service account
3. Builds the container image with Cloud Build
4. Deploys it and waits for the health check to pass
5. Prints your public URL

The first run takes 10–15 minutes, mostly creating the database. Later
deploys take 3–4 minutes.

> **On Windows**, run this from Git Bash or WSL. PowerShell cannot execute
> `.sh` scripts directly.

---

## Step 3 — Create your first workspace

Open the URL the script prints and go to `/register`. The first sign-up creates
your organisation and makes you its admin, then walks you through the setup
wizard.

There are no seeded demo accounts in production — that would be a security
hole. Sample data is offered as a choice during onboarding instead.

---

## What gets created

| Resource | Purpose | Idle cost |
|---|---|---|
| Cloud Run service | Runs the app, scales to zero | €0 |
| Cloud SQL (PostgreSQL 16) | Application database | ~€8/mo |
| Cloud Storage bucket | Payslips and documents | ~€0.02/mo |
| Secret Manager | `APP_KEY`, database password | ~€0.12/mo |
| Artifact Registry | Container images | ~€0.10/mo |
| Service account | Runtime identity, least privilege | Free |

Full breakdown in [infra/COSTS.md](infra/COSTS.md).

---

## Security posture

- **No public database.** Cloud SQL has no public IP; Cloud Run reaches it
  through the Cloud SQL connector's unix socket.
- **Secrets are never in the image or in Terraform output.** They live in
  Secret Manager and are injected at runtime.
- **Least privilege.** The service account can read its two secrets, connect to
  Cloud SQL, and read/write its own bucket. Nothing else. Bucket access is
  granted on the bucket, not project-wide.
- **Private bucket** with public access prevention enforced. Payslips are
  streamed through the app, which checks ownership on every request.
- **HTTPS everywhere**, terminated by Google's load balancer. Laravel is
  configured to force the `https` scheme so generated links do not break.
- **Demo login routes do not exist in production** — they are registered only
  in `local` and `testing` environments.

---

## Common operations

**Deploy a change**

```bash
./deploy.sh radical-ai-prod europe-west1
```

**Watch the logs**

```bash
gcloud run services logs tail radical-ai-prod --region europe-west1
```

**Run a migration by hand** (if `run_migrations_on_boot` is off)

```bash
gcloud run jobs create migrate --image IMAGE --region europe-west1 --command php --args artisan,migrate,--force
```

**Connect to the database**

```bash
gcloud sql connect radical-ai-prod-db --user=radical_ai --database=radical_ai
```

**Attach a custom domain**

```bash
gcloud run domain-mappings create --service radical-ai-prod --domain app.yourdomain.com --region europe-west1
```

Then set `app_url` in `terraform.tfvars` to the new domain and re-apply, so
generated links and invitation emails use it.

---

## Troubleshooting

**The health check fails after deploying**

```bash
gcloud run services logs read radical-ai-prod --region europe-west1 --limit 50
```

Nearly always one of: migrations failing against an empty database, or
`APP_KEY` not injected. The entrypoint fails loudly with a clear message when
`APP_KEY` is missing.

**"Permission denied" during `terraform apply`**

Your account needs `roles/owner` or an equivalent combination on the project.
API enablement in particular requires elevated permissions.

**First request is slow**

Expected with `min_instances = 0` — that cold start is the price of a €0 idle
bill. Set `min_instances = 1` in `terraform.tfvars` if you would rather pay
~€13/month to avoid it.

**Deleting everything**

```bash
cd infra && terraform destroy
```

The database is deletion-protected; you must set
`database_deletion_protection = false` and apply before it will go.
