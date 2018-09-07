#!/usr/bin/env bash

echo "Backing up original 'composer.json'"
cp composer.json original-composer.json

echo "Installing framework without saving to 'composer.lock'"
cp phpunit.xml.dist phpunit-50.xml
composer require "illuminate/cache:5.0.*" --no-update
composer require "illuminate/config:5.0.*" --no-update
composer require "illuminate/console:5.0.*" --no-update
composer require "illuminate/database:5.0.*" --no-update
composer require "illuminate/support:5.0.*" --no-update
composer require "phpunit/phpunit:4.*" --no-update --dev
composer update --prefer-source --no-interaction

echo "Restoring original 'composer.json'"
rm composer.json
mv original-composer.json composer.json

echo "Running tests for Laravel 5.0"
php -n vendor/bin/phpunit --configuration phpunit-50.xml
rm phpunit-50.xml