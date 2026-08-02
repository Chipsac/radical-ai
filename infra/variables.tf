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
  default     = "radical-ai"

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
    false (default) — use an external serverless Postgres via database_url.
    Free tiers from Neon or Supabase scale to zero, so an idle workspace costs
    nothing. This is the cheapest configuration.

    true — provision Cloud SQL here instead. Self-contained and fully managed,
    but it never sleeps: budget roughly EUR 8-10/month even with no traffic.
  EOT
  default     = false
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
# Defaults suit Resend, whose free tier covers 3,000 messages a month.

variable "mail_mailer" {
  type        = string
  description = "Laravel mail transport. 'smtp' for a provider, 'log' only for a dry run where no email is expected to arrive."
  default     = "smtp"
}

variable "mail_host" {
  type        = string
  description = "SMTP host. Resend: smtp.resend.com · Brevo: smtp-relay.brevo.com · SES: email-smtp.<region>.amazonaws.com"
  default     = "smtp.resend.com"
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
  description = "SMTP username. Resend uses the literal string 'resend'."
  default     = "resend"
}

variable "mail_password" {
  type        = string
  description = "SMTP password or API key. Stored in Secret Manager, never in the container definition."
  default     = ""
  sensitive   = true
}

variable "mail_from_address" {
  type        = string
  description = "The From address on outgoing mail. Must be on a domain you have verified with your provider."
  default     = "onboarding@resend.dev"
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
