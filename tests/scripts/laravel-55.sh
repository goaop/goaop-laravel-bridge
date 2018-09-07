#!/usr/bin/env bash

echo "Backing up original 'composer.json'"
cp composer.json original-composer.json

cp phpunit.xml.dist phpunit-laravel-55.xml
echo "Installing framework without saving to 'composer.lock'"
composer require "laravel/framework:5.5.*" --no-update
composer require "illuminate/support:5.5.*" --no-update
composer require "illuminate/contracts:5.5.*" --no-update
composer require "orchestra/testbench:3.5.*" --no-update --dev
composer update --prefer-source --no-interaction

echo "Restoring original 'composer.json'"
rm composer.json
mv original-composer.json composer.json

echo "Running tests for Laravel 5.5"
php vendor/bin/phpunit --configuration phpunit-laravel-55.xml
rm phpunit-laravel-55.xml