#!/usr/bin/env bash

echo "Backing up original 'composer.json'"
cp composer.json original-composer.json

cp phpunit.xml.dist phpunit-run-tests.xml
echo "Installing framework without saving to 'composer.lock'"
composer require "laravel/framework:5.0.*" --no-update
composer require "illuminate/support:5.0.*" --no-update
composer require "illuminate/contracts:5.0.*" --no-update
composer require "orchestra/testbench:3.0.*" --no-update --dev
composer update --prefer-source --no-interaction

echo "Restoring original 'composer.json'"
rm composer.json
mv original-composer.json composer.json

echo "Running tests for Laravel 5.0"
php vendor/bin/phpunit --configuration phpunit-run-tests.xml
rm phpunit-run-tests.xml