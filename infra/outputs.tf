output "service_url" {
  description = "Public URL of the deployed application."
  value       = google_cloud_run_v2_service.app.uri
}

output "artifact_registry_repo" {
  description = "Docker repository to push images to."
  value       = "${var.region}-docker.pkg.dev/${var.project_id}/${google_artifact_registry_repository.app.repository_id}"
}

output "image_tag_base" {
  description = "Fully qualified image name, without a tag."
  value       = "${var.region}-docker.pkg.dev/${var.project_id}/${google_artifact_registry_repository.app.repository_id}/${var.app_name}"
}

output "service_account_email" {
  description = "Runtime identity of the Cloud Run service."
  value       = google_service_account.app.email
}

output "database_connection_name" {
  description = "Cloud SQL connection name, for `gcloud sql connect` or a proxy."
  value       = google_sql_database_instance.main.connection_name
}

output "uploads_bucket" {
  description = "Cloud Storage bucket holding payslips and documents."
  value       = google_storage_bucket.uploads.name
}

output "next_steps" {
  description = "What to do after the first apply."
  value       = <<-EOT

    Infrastructure is ready.

      1. Build and deploy the application image:
           ./deploy.sh ${var.project_id} ${var.region}

      2. Open the service:
           ${google_cloud_run_v2_service.app.uri}

      3. Create the first workspace via the sign-up page.

    Note: until you push a real image, the service runs Google's placeholder
    "hello" container. That is expected on a first apply.
  EOT
}
