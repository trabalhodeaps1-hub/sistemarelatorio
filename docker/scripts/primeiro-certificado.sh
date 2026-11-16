#!/bin/sh
# Emite o primeiro certificado Let's Encrypt (rodar uma única vez, antes de subir o nginx com HTTPS).
# Uso: APP_DOMAIN=os.suaempresa.com.br EMAIL=ti@suaempresa.com.br ./docker/scripts/primeiro-certificado.sh
set -e
: "${APP_DOMAIN:?defina APP_DOMAIN}"
: "${EMAIL:?defina EMAIL}"

docker run --rm -p 80:80 \
  -v autorelatorio_certbot-certs:/etc/letsencrypt \
  certbot/certbot certonly --standalone -d "$APP_DOMAIN" --email "$EMAIL" --agree-tos --no-eff-email
