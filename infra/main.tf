###############################################################################
# Radical AI — serverless infrastructure on Google Cloud
#
# Optimised for pay-as-you-go: Cloud Run scales to zero, so an idle service
# costs nothing but storage. See COSTS.md for the full breakdown.
###############################################################################

terraform {
  required_version = ">= 1.6"

  required_providers {
    google = {
      source  = "hashicorp/google"
      version = "~> 6.0"
    }
    random = {
      source  = "hashicorp/random"
      version = "~> 3.6"
    }
  }
}

provider "google" {
  project = var.project_id
  region  = var.region
}

locals {
  service_name = "${var.app_name}-${var.environment}"

  # Every API this stack needs. Enabling them here means `terraform apply`
  # works on a brand-new project with nothing pre-configured.
  required_apis = [
    "run.googleapis.com",
    "artifactregistry.googleapis.com",
    "cloudbuild.googleapis.com",
    "secretmanager.googleapis.com",
    "sqladmin.googleapis.com",
    "storage.googleapis.com",
    "iam.googleapis.com",
    "compute.googleapis.com",
  ]
}

resource "google_project_service" "apis" {
  for_each = toset(local.required_apis)

  service            = each.value
  disable_on_destroy = false
}

###############################################################################
# Container registry
###############################################################################

resource "google_artifact_registry_repository" "app" {
  location      = var.region
  repository_id = var.app_name
  format        = "DOCKER"
  description   = "Container images for ${var.app_name}"

  # Keep storage costs flat by pruning superseded images.
  cleanup_policies {
    id     = "keep-recent"
    action = "KEEP"
    most_recent_versions {
      keep_count = 5
    }
  }

  cleanup_policies {
    id     = "delete-old"
    action = "DELETE"
    condition {
      older_than = "2592000s" # 30 days
    }
  }

  depends_on = [google_project_service.apis]
}

###############################################################################
# Identity — least-privilege service account for the running service
###############################################################################

resource "google_service_account" "app" {
  account_id   = "${var.app_name}-run"
  display_name = "${var.app_name} Cloud Run service account"
  description  = "Runtime identity for the ${var.app_name} Cloud Run service"

  depends_on = [google_project_service.apis]
}

# Read the secrets it needs...
resource "google_secret_manager_secret_iam_member" "app_secrets" {
  for_each = {
    app_key     = google_secret_manager_secret.app_key.id
    db_password = google_secret_manager_secret.db_password.id
  }

  secret_id = each.value
  role      = "roles/secretmanager.secretAccessor"
  member    = "serviceAccount:${google_service_account.app.email}"
}

# ...connect to Cloud SQL...
resource "google_project_iam_member" "app_sql_client" {
  project = var.project_id
  role    = "roles/cloudsql.client"
  member  = "serviceAccount:${google_service_account.app.email}"
}

# ...and read/write only its own bucket (granted on the bucket, not project-wide).
resource "google_storage_bucket_iam_member" "app_bucket" {
  bucket = google_storage_bucket.uploads.name
  role   = "roles/storage.objectAdmin"
  member = "serviceAccount:${google_service_account.app.email}"
}

###############################################################################
# Secrets
###############################################################################

# Laravel's APP_KEY. Generated once and kept in state; rotating it invalidates
# existing sessions and any encrypted column (including 2FA secrets).
resource "random_password" "app_key" {
  length  = 32
  special = false
}

resource "google_secret_manager_secret" "app_key" {
  secret_id = "${local.service_name}-app-key"

  replication {
    auto {}
  }

  depends_on = [google_project_service.apis]
}

resource "google_secret_manager_secret_version" "app_key" {
  secret      = google_secret_manager_secret.app_key.id
  secret_data = "base64:${base64encode(random_password.app_key.result)}"
}

resource "random_password" "db" {
  length           = 32
  special          = true
  override_special = "!#$%&*()-_=+[]{}<>:?"
}

resource "google_secret_manager_secret" "db_password" {
  secret_id = "${local.service_name}-db-password"

  replication {
    auto {}
  }

  depends_on = [google_project_service.apis]
}

resource "google_secret_manager_secret_version" "db_password" {
  secret      = google_secret_manager_secret.db_password.id
  secret_data = random_password.db.result
}

###############################################################################
# Object storage — payslips and uploaded documents
###############################################################################

resource "google_storage_bucket" "uploads" {
  name     = "${var.project_id}-${local.service_name}-uploads"
  location = var.region

  # Private: files are streamed through the app, which checks ownership.
  uniform_bucket_level_access = true
  public_access_prevention    = "enforced"

  versioning {
    enabled = true
  }

  lifecycle_rule {
    condition {
      age                = 90
      with_state         = "ARCHIVED"
      num_newer_versions = 3
    }
    action {
      type = "Delete"
    }
  }

  depends_on = [google_project_service.apis]
}

###############################################################################
# Database — PostgreSQL on Cloud SQL
#
# Cloud SQL has no scale-to-zero, so this is the one standing cost in the
# stack. db-f1-micro is the cheapest tier and is plenty for early usage; see
# COSTS.md for the serverless alternatives if you want a true €0 idle bill.
###############################################################################

resource "google_sql_database_instance" "main" {
  name             = "${local.service_name}-db"
  database_version = "POSTGRES_16"
  region           = var.region

  # Guard rail: a stray `terraform destroy` shouldn't take the data with it.
  deletion_protection = var.database_deletion_protection

  settings {
    tier              = var.database_tier
    availability_type = var.environment == "prod" ? "ZONAL" : "ZONAL"
    disk_size         = 10
    disk_type         = "PD_HDD" # cheapest; ample for this workload
    disk_autoresize   = true

    backup_configuration {
      enabled                        = true
      start_time                     = "03:00"
      point_in_time_recovery_enabled = false # keeps cost down
      backup_retention_settings {
        retained_backups = 7
      }
    }

    ip_configuration {
      # Cloud SQL requires at least one network path, so when no VPC is
      # supplied the instance keeps a public IP. That is not the exposure it
      # sounds like: with no authorized networks the IP accepts nothing
      # directly, and Cloud Run reaches the database through the Cloud SQL
      # connector, which is IAM-authenticated and TLS-encrypted.
      #
      # Supply vpc_network_id to switch to private IP only.
      ipv4_enabled    = var.vpc_network_id == ""
      private_network = var.vpc_network_id != "" ? var.vpc_network_id : null

      # Belt and braces: require TLS for any direct connection.
      ssl_mode = "ENCRYPTED_ONLY"

      # Deliberately no authorized_networks — nothing on the internet may
      # connect directly; access is via the connector only.
    }

    insights_config {
      query_insights_enabled = true
    }
  }

  depends_on = [google_project_service.apis]
}

resource "google_sql_database" "app" {
  name     = replace(var.app_name, "-", "_")
  instance = google_sql_database_instance.main.name
}

resource "google_sql_user" "app" {
  name     = replace(var.app_name, "-", "_")
  instance = google_sql_database_instance.main.name
  password = random_password.db.result
}

###############################################################################
# Cloud Run service
###############################################################################

resource "google_cloud_run_v2_service" "app" {
  name     = local.service_name
  location = var.region

  # Only Google's load balancer fronts the service.
  ingress = "INGRESS_TRAFFIC_ALL"

  deletion_protection = false

  template {
    service_account = google_service_account.app.email

    # The heart of the cost model: no traffic, no instances, no charge.
    scaling {
      min_instance_count = var.min_instances
      max_instance_count = var.max_instances
    }

    # Attach Cloud SQL over the built-in connector (unix socket).
    volumes {
      name = "cloudsql"
      cloud_sql_instance {
        instances = [google_sql_database_instance.main.connection_name]
      }
    }

    containers {
      image = var.container_image

      ports {
        container_port = 8080
      }

      resources {
        limits = {
          cpu    = var.cpu
          memory = var.memory
        }
        # Billing follows request processing rather than wall-clock time.
        cpu_idle          = true
        startup_cpu_boost = true
      }

      volume_mounts {
        name       = "cloudsql"
        mount_path = "/cloudsql"
      }

      # ---- Application configuration ----
      env {
        name  = "APP_ENV"
        value = "production"
      }
      env {
        name  = "APP_DEBUG"
        value = "false"
      }
      env {
        name  = "APP_NAME"
        value = "Radical AI"
      }
      env {
        name  = "APP_URL"
        value = var.app_url != "" ? var.app_url : "https://${local.service_name}-${var.project_number}.${var.region}.run.app"
      }
      env {
        name  = "LOG_CHANNEL"
        value = "stderr"
      }

      # ---- Database over the Cloud SQL unix socket ----
      # Laravel's pgsql driver passes `host` straight to PDO, which treats a
      # path as a unix socket directory — so the socket goes in DB_HOST, not
      # DB_SOCKET (that one is MySQL-only and would be silently ignored).
      env {
        name  = "DB_CONNECTION"
        value = "pgsql"
      }
      env {
        name  = "DB_HOST"
        value = "/cloudsql/${google_sql_database_instance.main.connection_name}"
      }
      env {
        name  = "DB_SSLMODE"
        value = "disable" # the unix socket is already local and private
      }
      env {
        name  = "DB_DATABASE"
        value = google_sql_database.app.name
      }
      env {
        name  = "DB_USERNAME"
        value = google_sql_user.app.name
      }

      # ---- Stateless drivers: instances are ephemeral and interchangeable ----
      env {
        name  = "SESSION_DRIVER"
        value = "database"
      }
      env {
        name  = "CACHE_STORE"
        value = "database"
      }
      env {
        name  = "QUEUE_CONNECTION"
        value = "database"
      }

      # ---- Uploads go to Cloud Storage, never the container filesystem ----
      env {
        name  = "FILESYSTEM_DISK"
        value = "gcs"
      }
      env {
        name  = "GCS_BUCKET"
        value = google_storage_bucket.uploads.name
      }
      env {
        name  = "GOOGLE_CLOUD_PROJECT"
        value = var.project_id
      }

      env {
        name  = "RUN_MIGRATIONS_ON_BOOT"
        value = tostring(var.run_migrations_on_boot)
      }

      # ---- Secrets ----
      env {
        name = "APP_KEY"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.app_key.secret_id
            version = "latest"
          }
        }
      }
      env {
        name = "DB_PASSWORD"
        value_source {
          secret_key_ref {
            secret  = google_secret_manager_secret.db_password.secret_id
            version = "latest"
          }
        }
      }

      # Cloud Run restarts an instance that stops passing these.
      startup_probe {
        http_get {
          path = "/healthz"
        }
        initial_delay_seconds = 5
        timeout_seconds       = 5
        period_seconds        = 5
        failure_threshold     = 10
      }

      liveness_probe {
        http_get {
          path = "/healthz"
        }
        initial_delay_seconds = 30
        timeout_seconds       = 5
        period_seconds        = 60
        failure_threshold     = 3
      }
    }

    max_instance_request_concurrency = var.concurrency
    timeout                          = "300s"
  }

  traffic {
    type    = "TRAFFIC_TARGET_ALLOCATION_TYPE_LATEST"
    percent = 100
  }

  depends_on = [
    google_project_service.apis,
    google_secret_manager_secret_version.app_key,
    google_secret_manager_secret_version.db_password,
    google_secret_manager_secret_iam_member.app_secrets,
  ]

  lifecycle {
    # The image tag changes with every deploy; that's the deploy script's job,
    # not Terraform's, so don't fight over it.
    ignore_changes = [template[0].containers[0].image]
  }
}

# Public SaaS: anyone can reach the sign-in page. Authorisation is the
# application's job, not the network's.
resource "google_cloud_run_v2_service_iam_member" "public" {
  count = var.allow_public_access ? 1 : 0

  project  = google_cloud_run_v2_service.app.project
  location = google_cloud_run_v2_service.app.location
  name     = google_cloud_run_v2_service.app.name
  role     = "roles/run.invoker"
  member   = "allUsers"
}
