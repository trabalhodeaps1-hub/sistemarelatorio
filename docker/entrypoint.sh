#!/bin/sh
set -e

# O comando de deploy (migrate + caches) roda apenas no container "app".
if [ "${RUN_DEPLOY_TASKS:-0}" = "1" ]; then
    echo "[entrypoint] aguardando o banco..."
    until pg_isready -h "${DB_HOST:-postgres}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-postgres}" >/dev/null 2>&1; do
        sleep 2
    done

    php artisan migrate --force
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
fi

exec "$@"
