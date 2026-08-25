#!/bin/bash
set -e

# =====================================================================
# Warehouse Management System (WMS) - Docker Entrypoint
# =====================================================================
# This script runs on container startup before Apache starts.
# It handles:
#   1. Waiting for the database to be ready
#   2. Running pending database migrations
#   3. Setting correct file permissions
# =====================================================================

echo "============================================="
echo " WMS - Container Starting"
echo "============================================="

# ── Wait for database ──────────────────────────────────────────────
DB_HOST="${DB_HOST:-db}"
DB_DRIVER="${DB_DRIVER:-mysql}"

# Set default port based on driver
if [ "$DB_DRIVER" = "pgsql" ]; then
    DB_PORT="${DB_PORT:-5432}"
    DSN="pgsql:host=${DB_HOST};port=${DB_PORT}"
    DB_USER_DEFAULT="postgres"
else
    DB_PORT="${DB_PORT:-3306}"
    DSN="mysql:host=${DB_HOST};port=${DB_PORT}"
    DB_USER_DEFAULT="root"
fi

echo "[entrypoint] Waiting for database (${DB_DRIVER}) at ${DB_HOST}:${DB_PORT}..."

MAX_RETRIES=30
RETRY_COUNT=0

while ! php -r "
    try {
        new PDO('${DSN}', '${DB_USER:-${DB_USER_DEFAULT}}', '${DB_PASSWORD:-}');
        echo 'OK';
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null; do
    RETRY_COUNT=$((RETRY_COUNT + 1))
    if [ $RETRY_COUNT -ge $MAX_RETRIES ]; then
        echo "[entrypoint] ERROR: Could not connect to database after ${MAX_RETRIES} attempts."
        exit 1
    fi
    echo "[entrypoint] Database not ready (attempt ${RETRY_COUNT}/${MAX_RETRIES}). Retrying in 2s..."
    sleep 2
done

echo "[entrypoint] Database is ready!"

# ── Run database migrations ────────────────────────────────────────
echo "[entrypoint] Running database migrations..."
php /var/www/html/backend/migrate.php migrate || {
    echo "[entrypoint] WARNING: Migration failed, but continuing startup..."
}

# ── Ensure correct permissions ─────────────────────────────────────
echo "[entrypoint] Setting file permissions..."
chown -R www-data:www-data /var/www/html/uploads 2>/dev/null || true
chmod -R 775 /var/www/html/uploads 2>/dev/null || true

echo "============================================="
echo " WMS - Ready! Starting Apache..."
echo "============================================="

# ── Execute the main container command (apache2-foreground) ────────
exec "$@"
