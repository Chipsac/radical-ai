#!/bin/sh
set -e

# ---------------------------------------------------------------------------
# Cloud Run container entrypoint.
#
# Cloud Run assigns the listening port at runtime via $PORT, so nginx's config
# is templated here rather than baked in. Laravel's caches are also built at
# start-up: the config depends on secrets that only exist at runtime, so they
# cannot be baked into the image.
# ---------------------------------------------------------------------------

: "${PORT:=8080}"

echo "[entrypoint] starting Radical AI on port ${PORT}"

# Substitute the port into the nginx config.
sed -i "s/\${PORT}/${PORT}/g" /etc/nginx/nginx.conf

# Fail fast with a clear message rather than a confusing stack trace.
if [ -z "${APP_KEY}" ]; then
    echo "[entrypoint] FATAL: APP_KEY is not set. Generate one with 'php artisan key:generate --show'." >&2
    exit 1
fi

# ---- Optional: run migrations on boot -------------------------------------
# Off by default. A dedicated migration job is safer at scale, because every
# starting instance would otherwise race to migrate. Enable for simple setups.
if [ "${RUN_MIGRATIONS_ON_BOOT}" = "true" ]; then
    echo "[entrypoint] running database migrations"
    php artisan migrate --force --no-interaction || {
        echo "[entrypoint] FATAL: migrations failed" >&2
        exit 1
    }
fi

# ---- Warm Laravel's caches ------------------------------------------------
# Cached config/routes/views make a meaningful difference to cold-start time.
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

# Storage symlink for any publicly served uploads (harmless if it exists).
php artisan storage:link --no-interaction 2>/dev/null || true

echo "[entrypoint] handing off to supervisord"
exec supervisord -c /etc/supervisord.conf
