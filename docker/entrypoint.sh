#!/usr/bin/env bash
set -e

echo "Waiting for MySQL 8 database to accept connections..."
until php -r "new PDO('mysql:host=db;port=3306;dbname=restaurant', 'restaurant', 'restaurant');" >/dev/null 2>&1; do
    sleep 2
done

echo "Applying Doctrine migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Loading realistic demo fixtures (50 ingredients, 20 dishes, 6 preparations, 5 suppliers, 3 roles)..."
php bin/console doctrine:fixtures:load --no-interaction

echo "Starting Symfony web server on http://0.0.0.0:8000 ..."
exec php -S 0.0.0.0:8000 -t public
