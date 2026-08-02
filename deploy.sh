#!/usr/bin/env bash
#
# Radical AI — one-command deploy to Google Cloud Run.
#
#   ./deploy.sh <project-id> [region]
#
# Builds with Cloud Build (no local Docker), runs migrations once as a job,
# then rolls out. Safe to run repeatedly.

set -euo pipefail

PROJECT_ID="${1:-}"
REGION="${2:-europe-west1}"
APP_NAME="${APP_NAME:-radical-ai}"
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
if ! grep -q 'mail_password' terraform.tfvars 2>/dev/null; then
  red "    Email is not configured."
  echo
  echo "    Signup verification and team invitations both need working email."
  echo "    Without it nobody can finish registering."
  echo
  echo "    Add to infra/terraform.tfvars (Resend's free tier covers 3,000/month):"
  echo
  echo '      mail_host         = "smtp.resend.com"'
  echo '      mail_username     = "resend"'
  echo '      mail_password     = "re_your_api_key"'
  echo '      mail_from_address = "onboarding@resend.dev"'
  echo
  echo "    To deploy anyway for a look around, set:  mail_mailer = \"log\""
  echo "    and add a placeholder mail_password."
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
SA="$(cd infra && terraform output -raw service_account_email)"

if gcloud run jobs describe "$JOB" --region "$REGION" >/dev/null 2>&1; then
  gcloud run jobs update "$JOB" --image "$IMAGE" --region "$REGION" --quiet
else
  gcloud run jobs create "$JOB" \
    --image "$IMAGE" \
    --region "$REGION" \
    --service-account "$SA" \
    --command php \
    --args artisan,migrate,--force \
    --quiet
fi

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

echo -n "    Liveness:  "
for attempt in $(seq 1 12); do
  CODE="$(curl -s -o /dev/null -w '%{http_code}' "${URL}/healthz" || echo 000)"
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
