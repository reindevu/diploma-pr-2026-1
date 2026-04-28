#!/bin/sh
set -eu

cd /var/www/html

echo "Waiting for PostgreSQL..."

php <<'PHP'
<?php
declare(strict_types=1);

$host = getenv('DB_HOST') ?: 'db';
$port = getenv('DB_PORT') ?: '5432';
$name = getenv('DB_NAME') ?: 'flour_and_fire';
$user = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: 'postgres';
$dsn = getenv('DB_DSN') ?: sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $name);

$attempts = 30;

while ($attempts-- > 0) {
    try {
        new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        exit(0);
    } catch (Throwable $exception) {
        fwrite(STDOUT, "Postgres is not ready yet...\n");
        sleep(2);
    }
}

fwrite(STDERR, "Could not connect to PostgreSQL.\n");
exit(1);
PHP

if [ "${AUTO_MIGRATE:-0}" = "1" ]; then
  echo "Checking schema..."

  set +e
  php <<'PHP'
<?php
declare(strict_types=1);

$host = getenv('DB_HOST') ?: 'db';
$port = getenv('DB_PORT') ?: '5432';
$name = getenv('DB_NAME') ?: 'flour_and_fire';
$user = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: 'postgres';
$dsn = getenv('DB_DSN') ?: sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $name);

$pdo = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$exists = $pdo->query("SELECT to_regclass('public.users')")->fetchColumn();

if ($exists === null) {
    exit(10);
}

exit(0);
PHP
  schema_status=$?
  set -e

  if [ "$schema_status" -eq 10 ]; then
    echo "Running migrations..."
    php scripts/migrate.php

    if [ "${AUTO_SEED:-0}" = "1" ]; then
      echo "Running seeds..."
      php scripts/seed.php
    fi
  else
    echo "Schema already exists, skipping migrations."
    echo "Running schema upgrades..."
    php scripts/upgrade.php
  fi
fi

if [ -n "${ADMIN_EMAIL:-}" ] && [ -n "${ADMIN_PASSWORD:-}" ]; then
  echo "Ensuring admin user exists..."
  php scripts/make_admin.php \
    "${ADMIN_EMAIL}" \
    "${ADMIN_PASSWORD}" \
    "${ADMIN_FIRST_NAME:-Admin}" \
    "${ADMIN_LAST_NAME:-User}"
fi

exec "$@"
