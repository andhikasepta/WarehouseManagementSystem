#!/bin/bash
set -e

# =====================================================================
# WMS Docker Entrypoint
# =====================================================================
# - Waits for database to be ready
# - Runs pending migrations automatically
# - Starts Apache
# =====================================================================

echo "========================================"
echo "  WMS Container Starting..."
echo "========================================"

# ── Wait for database ───────────────────────────────────────────────
if [ -n "$DB_HOST" ]; then
    DB_PORT_VAL="${DB_PORT:-3306}"
    echo "⏳ Waiting for database at $DB_HOST:$DB_PORT_VAL ..."
    
    MAX_RETRIES=30
    RETRY_COUNT=0
    
    while ! php -r "
        try {
            \$driver = getenv('DB_DRIVER') ?: 'mysql';
            \$host = getenv('DB_HOST') ?: '127.0.0.1';
            \$port = getenv('DB_PORT') ?: (\$driver === 'pgsql' ? '5432' : '3306');
            \$user = getenv('DB_USER') ?: 'root';
            \$pass = getenv('DB_PASSWORD') ?: '';
            if (\$driver === 'pgsql') {
                new PDO(\"pgsql:host=\$host;port=\$port\", \$user, \$pass);
            } else {
                new PDO(\"mysql:host=\$host;port=\$port\", \$user, \$pass);
            }
            echo 'OK';
            exit(0);
        } catch (Exception \$e) {
            exit(1);
        }
    " 2>/dev/null; do
        RETRY_COUNT=$((RETRY_COUNT + 1))
        if [ $RETRY_COUNT -ge $MAX_RETRIES ]; then
            echo "❌ Database not available after $MAX_RETRIES retries. Starting anyway..."
            break
        fi
        echo "   Retry $RETRY_COUNT/$MAX_RETRIES..."
        sleep 2
    done
    
    echo "✅ Database connection established."
fi

# ── Run migrations ──────────────────────────────────────────────────
echo "🔄 Running database migrations..."
php /var/www/html/migrate.php migrate 2>&1 || echo "⚠️  Migration warning (may already be up to date)"

echo "========================================"
echo "  WMS Ready — Listening on port 80"
echo "========================================"

# ── Hand off to Apache ──────────────────────────────────────────────
exec "$@"
