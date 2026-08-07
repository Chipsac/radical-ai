#!/usr/bin/env bash
#
# Crewly360 — one-command deploy to Google Cloud Run.
#
#   ./deploy.sh <project-id> [region]
#
# Builds with Cloud Build (no local Docker), runs migrations once as a job,
# then rolls out. Safe to run repeatedly.

set -euo pipefail

PROJECT_ID="${1:-}"
REGION="${2:-europe-west1}"
APP_NAME="${APP_NAME:-crewly360}"
ENVIRONMENT="${ENVIRONMENT:-prod}"
SERVICE="${APP_NAME}-${ENVIRONMENT}"
STATE_BUCKET="${PROJECT_ID}-${APP_NAME}-tfstate"

red()   { printf '\033[31m%s\033[0m\n' "$*"; }
green() { printf '\033[32m%s\033[0m\n' "$*"; }
amber() { printf '\033[33m%s\033[0m\n' "$*"; }
bold()  { printf '\033[1m%s\033[0m\n' "$*"; }

if [[ -z "$PROJECT_ID" ]]; then
  red "Usage: ./deploy.sh <project-id> [region]"
  echo
  echo "Find your project id with:  gcloud projects list"
  exit 1
fi

for cmd in gcloud terraform; do
  command -v "$cmd" >/dev/null 2>&1 || { red "Missing required command: $cmd"; exit 1; }
done

if ! gcloud auth list --filter=status:ACTIVE --format='value(account)' | grep -q .; then
  red "You are not signed in to gcloud. Run:  gcloud auth login"
  exit 1
fi

if ! gcloud billing projects describe "$PROJECT_ID" --format='value(billingEnabled)' 2>/dev/null | grep -qi true; then
  red "Billing is not enabled on project '${PROJECT_ID}'."
  echo
  echo "Cloud Run and Cloud Storage both require it. Enable it at:"
  echo "  https://console.cloud.google.com/billing/linkedaccount?project=${PROJECT_ID}"
  exit 1
fi

bold "==> Deploying ${SERVICE} to ${PROJECT_ID} (${REGION})"
gcloud config set project "$PROJECT_ID" --quiet >/dev/null

# ---------------------------------------------------------------------------
# 0. Remote state bucket
# ---------------------------------------------------------------------------
bold "==> Step 0/5  Terraform state"
if ! gcloud storage buckets describe "gs://${STATE_BUCKET}" >/dev/null 2>&1; then
  echo "    Creating gs://${STATE_BUCKET}"
  gcloud storage buckets create "gs://${STATE_BUCKET}" \
    --location="$REGION" --uniform-bucket-level-access --quiet
  # Versioning keeps state history, which is the difference between a bad
  # apply being an inconvenience and being unrecoverable.
  gcloud storage buckets update "gs://${STATE_BUCKET}" --versioning --quiet
else
  echo "    Using existing gs://${STATE_BUCKET}"
fi

# ---------------------------------------------------------------------------
# 1. Infrastructure
# ---------------------------------------------------------------------------
bold "==> Step 1/5  Provisioning infrastructure"
pushd infra >/dev/null

if [[ ! -f terraform.tfvars ]]; then
  PROJECT_NUMBER="$(gcloud projects describe "$PROJECT_ID" --format='value(projectNumber)')"
  cat > terraform.tfvars <<EOF
project_id     = "${PROJECT_ID}"
project_number = "${PROJECT_NUMBER}"
region         = "${REGION}"
environment    = "${ENVIRONMENT}"
EOF
  green "    Created infra/terraform.tfvars"
fi

# Refuse to go live with email that cannot send — signup verification and
# team invitations both depend on it, so the app would look broken.
#
# Checking merely that the string "mail_password" appears is not enough: it
# also matches a commented-out line or a PASTE_ME placeholder, either of which
# would deploy a site where nobody can finish registering. Require an
# uncommented assignment holding something the length of a real App Password
# (16 characters, sometimes written as four groups of four).
mail_password_configured() {
  local value
  value="$(sed -n 's/^[[:space:]]*mail_password[[:space:]]*=[[:space:]]*"\(.*\)"[[:space:]]*$/\1/p' \
           terraform.tfvars 2>/dev/null | tail -1)"
  [[ -z "$value" ]] && return 1
  case "${value,,}" in
    *paste*|*your*|*changeme*|*placeholder*|*abcd?efgh*|xxx*) return 1 ;;
  esac
  # Ignore spacing when counting, so "abcd efgh ijkl mnop" is accepted.
  local stripped="${value//[[:space:]]/}"
  [[ "${#stripped}" -ge 12 ]]
}

# An explicit mail_mailer = "log" is a deliberate choice to deploy without
# real email, so honour it rather than blocking.
mail_is_logged_only() {
  grep -Eq '^[[:space:]]*mail_mailer[[:space:]]*=[[:space:]]*"log"' terraform.tfvars 2>/dev/null
}

if ! mail_password_configured && ! mail_is_logged_only; then
  red "    Email is not configured."
  echo
  echo "    Signup verification and team invitations both need working email."
  echo "    Without it nobody can finish registering."
  echo
  echo "    Gmail SMTP is the all-Google option — free, ~500 messages a day."
  echo "      1. Turn on 2-Step Verification: https://myaccount.google.com/security"
  echo "      2. Create an App Password:      https://myaccount.google.com/apppasswords"
  echo
  echo "    Then add to infra/terraform.tfvars:"
  echo
  echo '      mail_host         = "smtp.gmail.com"'
  echo '      mail_username     = "you@gmail.com"'
  echo '      mail_password     = "abcd efgh ijkl mnop"   # the App Password'
  echo '      mail_from_address = "you@gmail.com"'
  echo
  echo "    To deploy anyway for a look around, set:  mail_mailer = \"log\""
  echo "    and add a placeholder mail_password. Signup will not complete."
  exit 1
fi

terraform init -input=false -backend-config="bucket=${STATE_BUCKET}" -reconfigure
terraform apply -auto-approve -input=false

IMAGE_BASE="$(terraform output -raw image_tag_base)"
DB_MODE="$(terraform output -raw database_mode)"
popd >/dev/null

echo "    Database: ${DB_MODE}"

# ---------------------------------------------------------------------------
# 2. Build
# ---------------------------------------------------------------------------
TAG="$(date +%Y%m%d-%H%M%S)"
IMAGE="${IMAGE_BASE}:${TAG}"

bold "==> Step 2/5  Building container image"
echo "    ${IMAGE}"
gcloud builds submit --tag "$IMAGE" --project "$PROJECT_ID" --region "$REGION" .

# ---------------------------------------------------------------------------
# 3. Migrate — once, before any new instance serves traffic
# ---------------------------------------------------------------------------
bold "==> Step 3/5  Running database migrations"
JOB="${SERVICE}-migrate"

# The job itself is defined in Terraform so that it carries the same
# environment, secrets and Cloud SQL attachment as the service. Creating it
# here by hand is what previously produced a job with no environment at all,
# which migrated a throwaway SQLite database and reported success. Only the
# image is set per release.
if ! gcloud run jobs describe "$JOB" --region "$REGION" >/dev/null 2>&1; then
  red "    Migration job '${JOB}' does not exist."
  echo "    It is created by Terraform — run this script again to apply infra first."
  exit 1
fi

gcloud run jobs update "$JOB" --image "$IMAGE" --region "$REGION" --quiet

if ! gcloud run jobs execute "$JOB" --region "$REGION" --wait --quiet; then
  red "    Migrations failed — not deploying. The running version is untouched."
  echo "    Logs:  gcloud run jobs executions list --job ${JOB} --region ${REGION}"
  exit 1
fi
green "    Migrations applied"

# ---------------------------------------------------------------------------
# 4. Roll out
# ---------------------------------------------------------------------------
bold "==> Step 4/5  Deploying to Cloud Run"
gcloud run deploy "$SERVICE" \
  --image "$IMAGE" \
  --region "$REGION" \
  --project "$PROJECT_ID" \
  --quiet

# ---------------------------------------------------------------------------
# 5. Verify
# ---------------------------------------------------------------------------
bold "==> Step 5/5  Verifying"
URL="$(gcloud run services describe "$SERVICE" --region "$REGION" --format='value(status.url)')"

# Probe /up, not /healthz. Cloud Run's own startup and liveness probes hit
# /healthz directly on the container port, but Google's front end does not
# serve that path to the internet — an external request gets Google's 404, not
# the app's. Checking it from here reported a perfectly healthy deploy as
# FAILED. /up is Laravel's built-in health route and is publicly reachable.
echo -n "    Liveness:  "
for attempt in $(seq 1 12); do
  CODE="$(curl -s -o /dev/null -w '%{http_code}' "${URL}/up" || echo 000)"
  if [[ "$CODE" == "200" ]]; then green "OK"; break; fi
  if [[ "$attempt" == 12 ]]; then
    red "FAILED (last status ${CODE})"
    echo "    gcloud run services logs read ${SERVICE} --region ${REGION} --limit 50"
    exit 1
  fi
  sleep 5
done

echo -n "    Readiness: "
READY="$(curl -s "${URL}/health" || echo '{}')"
if echo "$READY" | grep -q '"status":"ok"'; then
  green "OK — database, cache and storage all reachable"
else
  amber "DEGRADED"
  echo "    $READY"
fi

echo
green "================================================================"
green " Deployed successfully"
green "================================================================"
echo
bold  " Your app is live at:"
echo  "   ${URL}"
echo
echo  " Create the first workspace:"
echo  "   ${URL}/register"
echo
echo  " Tail the logs:"
echo  "   gcloud run services logs tail ${SERVICE} --region ${REGION}"
echo
