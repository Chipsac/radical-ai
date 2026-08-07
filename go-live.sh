#!/usr/bin/env bash
#
# Crewly360 — deploy from this Windows machine.
#
#   ./go-live.sh <project-id> [region]
#
# Wraps deploy.sh with the portable toolchain this machine uses. gcloud and
# terraform are installed under %LOCALAPPDATA% rather than on the system PATH.
#
# CLOUDSDK_PYTHON matters more than it looks: the Microsoft Store build of
# Python redirects reads of %LOCALAPPDATA% into a per-app sandbox, so it
# cannot see the gcloud install and every gcloud call dies with a confusing
# "can't open file ... gcloud.py". Pointing at the real Python install avoids
# the redirection entirely.

set -euo pipefail

LOCAL_APP_DATA="${LOCALAPPDATA:-/c/Users/$USER/AppData/Local}"
LOCAL_APP_DATA="$(printf '%s' "$LOCAL_APP_DATA" | sed 's|\\|/|g; s|^\([A-Za-z]\):|/\L\1|')"

export CLOUDSDK_PYTHON="${LOCAL_APP_DATA}/Programs/Python/Python311/python.exe"
export PATH="${LOCAL_APP_DATA}/gcloud-portable/google-cloud-sdk/bin:${LOCAL_APP_DATA}/terraform-portable/bin:${PATH}"

if [[ ! -x "$CLOUDSDK_PYTHON" ]]; then
  printf '\033[31m%s\033[0m\n' "Real Python not found at ${CLOUDSDK_PYTHON}"
  echo "gcloud cannot run without it. Install Python from python.org (not the"
  echo "Microsoft Store build) and point CLOUDSDK_PYTHON at it."
  exit 1
fi

for cmd in gcloud terraform; do
  command -v "$cmd" >/dev/null 2>&1 || {
    printf '\033[31m%s\033[0m\n' "Missing: $cmd"
    exit 1
  }
done

# gcloud and Terraform authenticate separately. `gcloud auth login` covers the
# CLI; Terraform's Google provider reads Application Default Credentials, which
# is a different credential store. Having one without the other fails several
# minutes in, after buckets have already been created, with a message that does
# not obviously point at the fix. Check both up front instead.
if ! gcloud auth application-default print-access-token >/dev/null 2>&1; then
  printf '\033[31m%s\033[0m\n' "Application Default Credentials are not set up."
  echo
  echo "Terraform needs these separately from the gcloud CLI login. Run:"
  echo
  echo "    gcloud auth application-default login"
  echo
  echo "Sign in with the same account that owns the project."
  exit 1
fi

exec ./deploy.sh "$@"
