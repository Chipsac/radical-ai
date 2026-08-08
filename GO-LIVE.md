# Going live on crewly360.com

Everything in the repository is ready. What remains needs a Google Cloud
billing account, which can only be created by the account holder.

Steps 1 and 2 are yours. Steps 3 onward can be run for you.

---

## 1. Create a billing account and a project

Cloud Run, Cloud SQL and Artifact Registry all refuse to create without
billing enabled. New customers get $300 of credit, valid about 90 days.

1. https://console.cloud.google.com/billing → **Create account**
2. https://console.cloud.google.com/projectcreate → create a project,
   for example `crewly360-prod`
3. Link the project to the billing account

Verify:

```bash
gcloud billing accounts list     # expect one row, OPEN = True
gcloud projects list             # expect the new project
```

Both currently return `Listed 0 items.`

## 2. Set up a sender on your own domain

**Do not send from a personal Gmail account.** This deployment did at first,
and verification emails went to spam — customers could not finish signing up.

Authentication was not the problem; Google signed those messages and they
passed SPF and DKIM. The problem was the shape of the mail: a company display
name on a personal `@gmail.com` address, linking to a recently registered
domain. That is what brand impersonation looks like to a filter. Gmail also
rewrites the From header to the authenticated account, so the sender name
showed the account holder's own name no matter what the application set —
which cannot be fixed from the application side.

This deployment now uses **Zoho Mail** sending as `support@crewly360.com`. Any
provider works; three things have to line up:

1. The From domain matches the domain the links point at
2. SPF authorises the sending servers for that domain
3. DKIM signs with a key published under that domain

**Publish SPF and DKIM and confirm they pass before pointing the app at the new
sender.** A DMARC policy of `p=quarantine` protects you once all three align,
and quarantines you if they do not. Registrars often pre-set `p=quarantine`
with nothing behind it — check before you cut over, not after.

Then generate an app password (Zoho: Security → App Passwords) for step 3. Note
the regional host — an EU-datacentre account needs `smtp.zoho.eu`, and
`smtp.zoho.com` will reject the credentials.

**Without working email, sign-up cannot complete** — the verification message
never arrives and a new user is stuck at the verification wall.

## 3. Fill in the deployment variables

```bash
cp infra/terraform.tfvars.example infra/terraform.tfvars
```

Set `project_id`, `project_number`, `mail_username`, `mail_password` and
`mail_from_address`. `app_url` is already `https://crewly360.com`.

`terraform.tfvars` is gitignored — the app password must not be committed.

`deploy.sh` refuses to run while `mail_password` is still a placeholder. That
check is deliberate: a wrong mail password fails at *send* time, not at boot,
so without it the app comes up healthy and silently stops emailing anyone.

## 4. Build infrastructure and deploy

```bash
./deploy.sh PROJECT_ID europe-west1
```

This creates the state bucket, applies Terraform, builds the image, runs
migrations as a one-off Cloud Run job, then shifts traffic. It refuses to
proceed if mail is unconfigured.

At the end you get a `run.app` URL. Confirm the app works there **before**
attaching the domain — it isolates app faults from DNS faults.

## 5. Attach crewly360.com

Cloud Run's own domain mapping is still Preview and Google does not recommend
it for production. A global load balancer works but costs ~EUR 18/month, which
is hard to justify before there is revenue. Firebase Hosting proxies to Cloud
Run, issues and renews TLS free, and is still first-party Google.

`firebase.json` in the repository root already points at `crewly360-prod` in
`europe-west1`.

```bash
npm install -g firebase-tools
firebase login
firebase use --add          # select the project
firebase deploy --only hosting
```

Then in the Firebase console → Hosting → **Add custom domain**, enter
`crewly360.com` and also `www.crewly360.com`.

Firebase will show the exact records to add at your registrar. They look like
this, but **use the values Firebase gives you, not these**:

| Host | Type | Value |
|------|------|-------|
| `@` | A | `199.36.158.100` |
| `www` | CNAME | `crewly360.com` |

Certificate issuance usually takes 15 minutes to a few hours. The domain is
not live until Firebase reports the certificate as active.

## 6. Verify

- [ ] `https://crewly360.com` loads the landing page over HTTPS
- [ ] `https://www.crewly360.com` redirects to the apex
- [ ] Sign-up completes **and the verification email arrives**
- [ ] The landing-page contact form sends to `mail_contact_to`
- [ ] Password reset links point at `crewly360.com`, not `run.app`
- [ ] `/up` returns healthy

---

## Running cost

| Item | Idle | Notes |
|------|------|-------|
| Cloud Run | EUR 0 | `min_instances = 0`, so nothing runs between requests |
| Cloud SQL | **EUR 8–10/month** | No free tier, does not scale to zero |
| Cloud Storage | ~EUR 0 | Pennies at this volume |
| Firebase Hosting | EUR 0 | Free tier, includes TLS |
| Artifact Registry | ~EUR 0 | A few cents of image storage |

Cloud SQL is effectively the entire bill, and it is charged whether or not
anyone uses the app. The $300 credit covers roughly the first year.

Set a budget alert at https://console.cloud.google.com/billing/budgets before
traffic arrives — it is the only thing that will tell you if something starts
costing money unexpectedly.
