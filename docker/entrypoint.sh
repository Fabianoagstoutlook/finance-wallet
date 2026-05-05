#!/usr/bin/env sh
set -eu

cd /var/www

# Instala dependencias Node conforme lockfile.
if [ ! -d node_modules ]; then
  if [ -f package-lock.json ]; then
    npm ci
  elif [ -f package.json ]; then
    npm install
  fi
fi

DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-finance}"
DB_USERNAME="${DB_USERNAME:-laravel}"
DB_PASSWORD="${DB_PASSWORD:-secret}"
export DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD

# Aguarda o MySQL aceitar conexoes para evitar falha no migrate.
ATTEMPTS=0
MAX_ATTEMPTS=30
DB_READY=0
until php -r "
try {
  new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
  exit(0);
} catch (Throwable $e) {
  exit(1);
}
"; do
  ATTEMPTS=$((ATTEMPTS + 1))
  if [ "$ATTEMPTS" -ge "$MAX_ATTEMPTS" ]; then
    echo "MySQL nao ficou pronto a tempo; pulando migrate automatico."
    break
  fi
  echo "Aguardando MySQL em ${DB_HOST}:${DB_PORT}... (${ATTEMPTS}/${MAX_ATTEMPTS})"
  sleep 2
done
if [ "$ATTEMPTS" -lt "$MAX_ATTEMPTS" ]; then
  DB_READY=1
fi

# Gera APP_KEY apenas quando ainda nao existe.
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate --force
fi

# Executa migrate + seed na subida; --force para ambiente containerizado.
if [ "$DB_READY" -eq 1 ]; then
  php artisan migrate --seed --force
fi

exec "$@"
