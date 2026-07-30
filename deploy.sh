#!/usr/bin/env bash
#
# Radical AI — one-command deploy to Google Cloud Run.
#
#   ./deploy.sh <project-id> [region]
#
# Builds the container with Cloud Build (no local Docker needed), pushes it to
# Artifact Registry, and rolls it out to Cloud Run. Safe to run repeatedly.

set -euo pipefail

PROJECT_ID="${1:-}"
REGION="${2:-europe-west1}"
APP_NAME="${APP_NAME:-radical-ai}"
ENVIRONMENT="${ENVIRONMENT:-prod}"
SERVICE="${APP_NAME}-${ENVIRONMENT}"

red()   { printf '\033[31m%s\033[0m\n' "$*"; }
green() { printf '\033[32m%s\033[0m\n' "$*"; }
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

bold "==> Deploying ${SERVICE} to ${PROJECT_ID} (${REGION})"
gcloud config set project "$PROJECT_ID" --quiet >/dev/null

# ---------------------------------------------------------------------------
# 1. Infrastructure
# ---------------------------------------------------------------------------
bold "==> Step 1/4  Provisioning infrastructure with Terraform"
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

terraform init -input=false
terraform apply -auto-approve -input=false

IMAGE_BASE="$(terraform output -raw image_tag_base)"
popd >/dev/null

# ---------------------------------------------------------------------------
# 2. Build the image (Cloud Build — no local Docker required)
# ---------------------------------------------------------------------------
TAG="$(date +%Y%m%d-%H%M%S)"
IMAGE="${IMAGE_BASE}:${TAG}"

bold "==> Step 2/4  Building container image"
echo "    ${IMAGE}"
gcloud builds submit \
  --tag "$IMAGE" \
  --project "$PROJECT_ID" \
  --region "$REGION" \
  .

# ---------------------------------------------------------------------------
# 3. Roll out
# ---------------------------------------------------------------------------
bold "==> Step 3/4  Deploying to Cloud Run"
gcloud run deploy "$SERVICE" \
  --image "$IMAGE" \
  --region "$REGION" \
  --project "$PROJECT_ID" \
  --quiet

# ---------------------------------------------------------------------------
# 4. Verify
# ---------------------------------------------------------------------------
bold "==> Step 4/4  Verifying"
URL="$(gcloud run services describe "$SERVICE" --region "$REGION" --format='value(status.url)')"

echo -n "    Health check: "
for attempt in $(seq 1 12); do
  CODE="$(curl -s -o /dev/null -w '%{http_code}' "${URL}/healthz" || echo 000)"
  if [[ "$CODE" == "200" ]]; then
    green "OK"
    break
  fi
  if [[ "$attempt" == 12 ]]; then
    red "FAILED (last status ${CODE})"
    echo
    echo "Check the logs with:"
    echo "  gcloud run services logs read ${SERVICE} --region ${REGION} --limit 50"
    exit 1
  fi
  sleep 5
done

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
