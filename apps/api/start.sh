#!/bin/sh
set -e

# ---------------------------------------------------------------------------
# Entrypoint: start PHP-FPM (foreground, backgrounded via &) then Nginx.
#
# Why not `-D`? The -D flag daemonizes PHP-FPM, which means the process
# detaches from the shell, loses its stdout/stderr, and any startup failure
# is completely silent. In a container we want all processes to stay as
# children of this script so we can detect crashes and forward signals.
# ---------------------------------------------------------------------------

# Run database migrations before starting services
echo "[start.sh] Running database migrations..."
php artisan migrate --force 2>&1
echo "[start.sh] Migrations complete."

# Seed the database with reference data and schedules
echo "[start.sh] Running database seeders..."
php artisan db:seed --force 2>&1
echo "[start.sh] Seeding complete."

# Start PHP-FPM in the foreground but as a background job of this script.
# Redirect its output explicitly so logs appear in the container log stream.
echo "[start.sh] Starting PHP-FPM..."
php-fpm --nodaemonize 2>&1 &
PHP_FPM_PID=$!
echo "[start.sh] PHP-FPM started (pid $PHP_FPM_PID)."

# Give PHP-FPM a moment to bind to port 9000 before Nginx starts forwarding
# requests to it. If it failed to start, the wait below will catch it.
sleep 1

# Verify PHP-FPM is still alive after the brief startup window.
if ! kill -0 "$PHP_FPM_PID" 2>/dev/null; then
    echo "[start.sh] ERROR: PHP-FPM failed to start. Aborting." >&2
    exit 1
fi

# Forward SIGTERM/SIGINT to both child processes so a graceful container
# shutdown (docker stop / Railway deploy) tears everything down cleanly.
_shutdown() {
    echo "[start.sh] Caught shutdown signal — stopping PHP-FPM and Nginx..."
    kill -TERM "$PHP_FPM_PID" 2>/dev/null || true
    kill -TERM "$NGINX_PID"   2>/dev/null || true
    wait "$PHP_FPM_PID" "$NGINX_PID" 2>/dev/null || true
    echo "[start.sh] All processes stopped."
    exit 0
}
trap _shutdown TERM INT

# Start Nginx in the background as well so we can monitor both PIDs.
echo "[start.sh] Starting Nginx..."
nginx -g "daemon off;" 2>&1 &
NGINX_PID=$!
echo "[start.sh] Nginx started (pid $NGINX_PID)."

# Wait for either process to exit. If one dies the container should restart
# rather than silently serving 502s, so we exit with a non-zero status.
wait -n "$PHP_FPM_PID" "$NGINX_PID" 2>/dev/null
EXITED_STATUS=$?

echo "[start.sh] A managed process exited (status $EXITED_STATUS). Shutting down..." >&2
kill -TERM "$PHP_FPM_PID" 2>/dev/null || true
kill -TERM "$NGINX_PID"   2>/dev/null || true
wait "$PHP_FPM_PID" "$NGINX_PID" 2>/dev/null || true
exit "$EXITED_STATUS"
