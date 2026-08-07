variable "project_id" {
  type        = string
  description = "GCP project id to deploy into."
}

variable "project_number" {
  type        = string
  description = "GCP project number, used to predict the default Cloud Run URL. Find it with: gcloud projects describe PROJECT_ID --format='value(projectNumber)'"
  default     = ""
}

variable "region" {
  type        = string
  description = "Region for all regional resources. europe-west1 is a good default for EU data residency."
  default     = "europe-west1"
}

variable "app_name" {
  type        = string
  description = "Short name used to prefix resources."
  default     = "crewly360"

  validation {
    condition     = can(regex("^[a-z][a-z0-9-]{1,20}$", var.app_name))
    error_message = "app_name must be lowercase letters, digits and hyphens, starting with a letter."
  }
}

variable "environment" {
  type        = string
  description = "Environment suffix (prod, staging, dev)."
  default     = "prod"
}

variable "container_image" {
  type        = string
  description = "Full image reference to deploy. The deploy script overrides this per build."
  default     = "us-docker.pkg.dev/cloudrun/container/hello"
}

variable "app_url" {
  type        = string
  description = "Public URL of the service. Leave blank to use the generated run.app URL; set it when you attach a custom domain."
  default     = ""
}

# ---- Scaling and cost controls --------------------------------------------

variable "min_instances" {
  type        = number
  description = "Instances kept warm. 0 means the service scales to zero and costs nothing when idle, at the price of a cold start on the first request."
  default     = 0
}

variable "max_instances" {
  type        = number
  description = "Upper bound on instances — the safety net against a runaway bill."
  default     = 10
}

variable "cpu" {
  type        = string
  description = "vCPU per instance."
  default     = "1"
}

variable "memory" {
  type        = string
  description = "Memory per instance. PHP-FPM with 8 workers is comfortable at 512Mi."
  default     = "512Mi"
}

variable "concurrency" {
  type        = number
  description = "Requests handled simultaneously per instance. Higher means fewer instances and a lower bill."
  default     = 80
}

# ---- Database --------------------------------------------------------------

variable "use_cloud_sql" {
  type        = bool
  description = <<-EOT
    true (default) — provision Cloud SQL. Keeps the whole stack inside Google
    Cloud with no third-party accounts. Note there is no always-free tier for
    Cloud SQL: it is covered by the $300 new-customer credit and by the 30-day
    Cloud SQL trial instance, after which db-f1-micro costs roughly
    EUR 8-10/month and does not scale to zero.

    false — use an external serverless Postgres via database_url instead.
    Providers such as Neon have free tiers that genuinely scale to zero, so an
    idle workspace costs nothing. Cheaper, but no longer Google-only.
  EOT
  default     = true
}

variable "database_url" {
  type        = string
  description = "Full Postgres connection URL, used when use_cloud_sql is false. e.g. postgres://user:pass@host/db?sslmode=require"
  default     = ""
  sensitive   = true
}

variable "database_high_availability" {
  type        = bool
  description = "Cloud SQL only. Enables REGIONAL failover, SSD storage and point-in-time recovery. Roughly doubles the database cost — leave off until you have customers who would notice an outage."
  default     = false
}

variable "database_tier" {
  type        = string
  description = "Cloud SQL machine type. db-f1-micro is the cheapest option."
  default     = "db-f1-micro"
}

variable "database_deletion_protection" {
  type        = bool
  description = "Block accidental deletion of the database instance."
  default     = true
}

variable "vpc_network_id" {
  type        = string
  description = "Optional VPC network id for a private-IP Cloud SQL instance. Leave blank to use the Cloud SQL connector only."
  default     = ""
}

# ---- Email -----------------------------------------------------------------
# Signup verification and team invitations both depend on working email.
#
# Google Cloud has no first-party transactional email service — it blocks
# outbound port 25 and expects you to use SMTP elsewhere. The Google-only
# option is Gmail SMTP with an App Password, which these defaults target:
# free, and roughly 500 messages a day, which is plenty early on.
#
# Requires 2-Step Verification on the Google account, then an App Password
# from https://myaccount.google.com/apppasswords

variable "mail_mailer" {
  type        = string
  description = "Laravel mail transport. 'smtp' for a real provider, 'log' only for a dry run where no email is expected to arrive."
  default     = "smtp"
}

variable "mail_host" {
  type        = string
  description = "SMTP host. Gmail: smtp.gmail.com · Resend: smtp.resend.com · Brevo: smtp-relay.brevo.com"
  default     = "smtp.gmail.com"
}

variable "mail_port" {
  type        = number
  description = "SMTP port. 587 for STARTTLS."
  default     = 587
}

variable "mail_scheme" {
  type        = string
  description = "SMTP encryption scheme."
  default     = "tls"
}

variable "mail_username" {
  type        = string
  description = "SMTP username. For Gmail this is the full address, e.g. you@gmail.com."
  default     = ""
}

variable "mail_password" {
  type        = string
  description = "SMTP password. For Gmail this is a 16-character App Password, not your account password. Stored in Secret Manager, never in the container definition."
  default     = ""
  sensitive   = true
}

variable "mail_from_address" {
  type        = string
  description = "The From address on outgoing mail. Gmail rewrites this to the authenticated account, so set it to the same address as mail_username."
  default     = ""
}

variable "mail_contact_to" {
  type        = string
  description = "Where landing-page enquiries are delivered. Defaults to mail_from_address."
  default     = ""
}

# ---- Behaviour -------------------------------------------------------------

variable "run_migrations_on_boot" {
  type        = bool
  description = "Run migrations as each container starts. Off by default: with more than one instance they race each other. The deploy script runs migrations once, as a Cloud Run job, before shifting traffic."
  default     = false
}

variable "allow_public_access" {
  type        = bool
  description = "Allow unauthenticated requests. Required for a public SaaS sign-up page."
  default     = true
}
