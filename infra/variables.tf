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

# ---- Behaviour -------------------------------------------------------------

variable "run_migrations_on_boot" {
  type        = bool
  description = "Run database migrations when a container starts. Convenient for small deployments; prefer a dedicated migration step once you run many instances."
  default     = true
}

variable "allow_public_access" {
  type        = bool
  description = "Allow unauthenticated requests. Required for a public SaaS sign-up page."
  default     = true
}
