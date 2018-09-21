#!/usr/bin/env bash

echo "Backing up original 'composer.json'"
cp composer.json original-composer.json

if [ ! -d "./storage/app" ]; then
    echo "Creating storage directory"
    mkdir -p storage/app
fi

cp phpunit.xml.dist phpunit-run-tests.xml
echo "Installing framework without saving to 'composer.lock'"
composer require "laravel/lumen-framework:5.5.*" --no-update
composer require "phpunit/phpunit:4.*" --no-update --dev
composer update --prefer-source --no-interaction

echo "Restoring original 'composer.json'"
rm composer.json
mv original-composer.json composer.json

echo "Running tests for Lumen 5.5"
php vendor/bin/phpunit --configuration phpunit-run-tests.xml
rm phpunit-run-tests.xml