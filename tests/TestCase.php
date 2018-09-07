<?php

namespace Go\Laravel\GoAopBridge\Tests;

if (class_exists(\Laravel\Lumen\Testing\TestCase::class)) {
    /*
     * Setup Lumen base TestCase
     */

    abstract class TestCase extends \Laravel\Lumen\Testing\TestCase {

        public function createApplication()
        {
            $app = new \Laravel\Lumen\Application(
                realpath(__DIR__.'/../')
            );

            $app->singleton(
                \Illuminate\Contracts\Debug\ExceptionHandler::class,
                \App\Exceptions\Handler::class
            );
            $app->singleton(
                \Illuminate\Contracts\Console\Kernel::class,
                \App\Console\Kernel::class
            );

            return $app;
        }

    }
} else if (class_exists(\Orchestra\Testbench\TestCase::class)) {
    /*
     * Setup Laravel base TestCase
     */

    abstract class TestCase extends \Orchestra\Testbench\TestCase {}
} else {
    throw new \RuntimeException('No base TestCase-class was found! Did the framework install properly?');
}
