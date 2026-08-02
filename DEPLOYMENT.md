# Deploying Radical AI to Google Cloud Run

From nothing to a live public URL in about 15 minutes, most of which is waiting
for the first build.

---

Everything here runs on Google Cloud — Cloud Run, Cloud SQL, Cloud Storage,
Secret Manager, Artifact Registry and Gmail SMTP. No third-party accounts.

## Before you start

**1. A Google Cloud account with billing enabled.**
New accounts get $300 of free credit, which covers roughly the first 90 days of
this whole stack. Billing has to be on regardless: Cloud Run and Cloud SQL are
not available on a billing-free project.

**2. A Gmail App Password**, so the app can send verification and invitation
emails. Google Cloud has no email service of its own, so mail leaves through
Gmail SMTP — free, around 500 messages a day.

- Turn on 2-Step Verification → [myaccount.google.com/security](https://myaccount.google.com/security)
- Create an App Password → [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)
- Keep the 16-character password for step 2 below

**3. The `gcloud` CLI** — [install guide](https://cloud.google.com/sdk/docs/install)

**4. Terraform 1.6+** — [install guide](https://developer.hashicorp.com/terraform/install)

You do **not** need Docker locally: the image is built by Cloud Build.

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

## Step 2 — Configure email

Run the deploy once to generate `infra/terraform.tfvars`, then add your Gmail
details to it:

```hcl
mail_host         = "smtp.gmail.com"
mail_username     = "you@gmail.com"
mail_password     = "abcd efgh ijkl mnop"   # the App Password, not your login
mail_from_address = "you@gmail.com"
mail_contact_to   = "you@gmail.com"
```

The deploy script refuses to continue without this. That is deliberate: signup
verification and team invitations both send email, so a deployment without it
looks broken to the first person who tries to register.

To deploy without email just to look around, set `mail_mailer = "log"` and a
placeholder password — but signup will not complete.

## Step 3 — Deploy

```bash
./deploy.sh radical-ai-prod europe-west1
```

That single command:

1. Creates a versioned GCS bucket for Terraform state
2. Enables the required Google Cloud APIs
3. Provisions everything — Cloud Run, Cloud SQL, Cloud Storage, Secret
   Manager, Artifact Registry, and a least-privilege service account
4. Builds the container image with Cloud Build
5. Runs database migrations once, as a Cloud Run job, before shifting traffic
6. Deploys, checks liveness and readiness, and prints your public URL

The first run takes 10–15 minutes, mostly creating the database. Later deploys
take 3–4 minutes.

> **On Windows**, run this from Git Bash or WSL. PowerShell cannot execute
> `.sh` scripts directly.

---

## Step 4 — Create your first workspace

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
| Cloud SQL (PostgreSQL 16) | Application database — **never sleeps** | ~€8–10/mo |
| Cloud Storage bucket | Payslips and documents | ~€0.02/mo |
| Cloud Storage bucket | Terraform state, versioned | ~€0.01/mo |
| Secret Manager | `APP_KEY`, database and SMTP passwords | ~€0.18/mo |
| Artifact Registry | Container images, pruned automatically | ~€0.10/mo |
| Cloud Run job | Runs migrations on each deploy | Per second, ~€0 |
| Service account | Runtime identity, least privilege | Free |
| Gmail SMTP | Verification and invitation email | Free |

The $300 new-customer credit covers roughly the first 90 days of all of it.
After that Cloud SQL is effectively the entire bill, because everything else
falls to zero when idle.

Full breakdown in [infra/COSTS.md](infra/COSTS.md).

---

## Security posture

- **The database accepts no direct connections.** Cloud SQL keeps a public IP
  (it requires at least one network path, and no VPC is provisioned by
  default), but there are **no authorized networks**, so nothing on the
  internet can reach it. Cloud Run connects through the Cloud SQL connector,
  which is IAM-authenticated and TLS-encrypted, and `ssl_mode` is pinned to
  `ENCRYPTED_ONLY`. Set `vpc_network_id` to move to private IP only.
- **Secrets are never in the image or in Terraform output.** `APP_KEY`, the
  database password and the SMTP password all live in Secret Manager and are
  injected at runtime.
- **Least privilege.** The service account can read its three secrets, connect
  to Cloud SQL, and read/write its own bucket. Nothing else. Bucket access is
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

**Run migrations by hand** (deploy.sh does this automatically)

```bash
gcloud run jobs execute radical-ai-prod-migrate --region europe-west1 --wait
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
