#!/bin/bash
set -e

# =====================================================================
# Warehouse Management System (WMS) - Docker Entrypoint
# Target Host: 103.123.100.12
# =====================================================================
# This script runs on container startup before Apache starts.
# It handles:
#   1. Waiting for the database to be reachable
#   2. Running pending database migrations
#   3. Setting correct file and directory permissions
# =====================================================================

echo "============================================="
echo " WMS - Starting Container on 103.123.100.12"
echo "============================================="

# ── Load environment variables if not already set in environment ───
DB_DRIVER="${DB_DRIVER:-pgsql}"
DB_HOST="${DB_HOST:-103.123.100.14}"
DB_NAME="${DB_NAME:-dashboard_db}"
DB_USER="${DB_USER:-deutsch}"
DB_PASSWORD="${DB_PASSWORD:-S3pt4@}"

if [ "$DB_DRIVER" = "pgsql" ]; then
    DB_PORT="${DB_PORT:-5432}"
    DSN="pgsql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_NAME}"
else
    DB_PORT="${DB_PORT:-3306}"
    DSN="mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_NAME}"
fi

echo "[entrypoint] Database Driver : ${DB_DRIVER}"
echo "[entrypoint] Database Host   : ${DB_HOST}:${DB_PORT}"
echo "[entrypoint] Database Name   : ${DB_NAME}"
echo "[entrypoint] Database User   : ${DB_USER}"

# ── Wait for Database Connection ────────────────────────────────────
MAX_RETRIES=20
RETRY_COUNT=0
DB_CONNECTED=0

echo "[entrypoint] Checking database connectivity..."

while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    if php -r "
        try {
            \$pdo = new PDO('${DSN}', '${DB_USER}', '${DB_PASSWORD}', [
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            echo 'CONNECTED';
            exit(0);
        } catch (Exception \$e) {
            exit(1);
        }
    " 2>/dev/null; then
        DB_CONNECTED=1
        echo "[entrypoint] Database is connected and ready!"
        break
    fi

    RETRY_COUNT=$((RETRY_COUNT + 1))
    echo "[entrypoint] Database not ready yet (attempt ${RETRY_COUNT}/${MAX_RETRIES}). Retrying in 2s..."
    sleep 2
done

if [ $DB_CONNECTED -eq 0 ]; then
    echo "[entrypoint] WARNING: Could not connect to database after ${MAX_RETRIES} attempts."
    echo "[entrypoint] Continuing container startup. Please check DB credentials and host connectivity."
else
    # ── Run database migrations ────────────────────────────────────
    echo "[entrypoint] Running database migrations..."
    if [ -f /var/www/html/backend/migrate.php ]; then
        php /var/www/html/backend/migrate.php migrate || {
            echo "[entrypoint] WARNING: Database migration returned non-zero exit code, continuing..."
        }
    fi
fi

# ── Ensure correct permissions on runtime directories ───────────────
echo "[entrypoint] Ensuring file permissions for uploads and cache..."
mkdir -p /var/www/html/uploads
chown -R www-data:www-data /var/www/html/uploads 2>/dev/null || true
chmod -R 775 /var/www/html/uploads 2>/dev/null || true

echo "============================================="
echo " WMS - Ready! Starting Apache Web Server..."
echo "============================================="

# ── Execute the main container command (apache2-foreground) ────────
exec "$@"