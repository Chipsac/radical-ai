###############################################################################
# Remote state
#
# Terraform state records everything it manages, including generated
# passwords. Left on a laptop it is a single point of failure: lose the
# machine and you can no longer update or tear down your own infrastructure,
# and nobody else can deploy.
#
# The bucket must exist before the first `terraform init`. deploy.sh creates
# it automatically, or by hand:
#
#   gcloud storage buckets create gs://PROJECT_ID-crewly360-tfstate \
#       --location=europe-west1 --uniform-bucket-level-access
#   gcloud storage buckets update gs://PROJECT_ID-crewly360-tfstate --versioning
#
# The bucket name is supplied at init time rather than hardcoded, because it
# includes the project id:
#
#   terraform init -backend-config="bucket=PROJECT_ID-crewly360-tfstate"
#
# Object versioning on the bucket gives state history, and GCS provides the
# locking that stops two people applying at once.
###############################################################################

terraform {
  backend "gcs" {
    prefix = "crewly360/state"
  }
}
