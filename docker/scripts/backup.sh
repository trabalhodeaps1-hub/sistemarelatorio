#!/bin/sh
# Backup diário: pg_dump do banco + tar das fotos (storage/app/private). Retenção de 7 dias.
# Cron do host (03:00):  0 3 * * * /opt/autorelatorio/docker/scripts/backup.sh >> /var/log/autorelatorio-backup.log 2>&1
set -e

cd "$(dirname "$0")/../.."
DESTINO="${BACKUP_DIR:-/var/backups/autorelatorio}"
RETENCAO_DIAS="${BACKUP_RETENCAO_DIAS:-7}"
DATA="$(date +%Y%m%d-%H%M%S)"
COMPOSE="docker compose -f docker-compose.prod.yml --env-file .env.production"

mkdir -p "$DESTINO"

# Banco
$COMPOSE exec -T postgres sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --format=custom' > "$DESTINO/db-$DATA.dump"

# Fotos privadas
$COMPOSE exec -T app tar -czf - -C /var/www/html/storage/app private > "$DESTINO/fotos-$DATA.tar.gz"

# Retenção
find "$DESTINO" -type f \( -name 'db-*.dump' -o -name 'fotos-*.tar.gz' \) -mtime +"$RETENCAO_DIAS" -delete

echo "[$(date)] backup ok: db-$DATA.dump, fotos-$DATA.tar.gz"
