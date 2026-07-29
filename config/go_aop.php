<?php
/*
 * Go! AOP framework configuration for Laravel.
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */
declare(strict_types=1);


return [

    /*
     |--------------------------------------------------------------------------
     | AOP Debug Mode
     |--------------------------------------------------------------------------
     |
     | When AOP is in debug mode, then breakpoints in the original source
     | code will work. Also engine will refresh cache files if the original
     | files were changed.
     |
     | For production mode, no extra filemtime checks and better
     | integration with opcache.
     |
     */

    'debug' => (bool) env('GOAOP_DEBUG', env('APP_DEBUG', false)),

    /*
    |--------------------------------------------------------------------------
    | Application Root Directory
    |--------------------------------------------------------------------------
    |
    | AOP will be applied only to the files in this directory, change it
    | to app_path() if needed.
    |
    */

    'appDir' => base_path(),

    /*
    |--------------------------------------------------------------------------
    | AOP Cache Directory
    |--------------------------------------------------------------------------
    |
    | AOP engine will put all transformed files and caches in that directory.
    | Use "php artisan aop:warmup" to pre-generate proxies during deployment.
    |
    */

    'cacheDir' => env('GOAOP_CACHE_DIR', storage_path('framework/aop')),

    /*
    |--------------------------------------------------------------------------
    | Cache File Mode
    |--------------------------------------------------------------------------
    |
    | File mode (chmod) for generated cache files, expressed in octal digits.
    | The value is parsed with octdec(), so "770" means 0770 (rwxrwx---).
    |
    */

    'cacheFileMode' => octdec((string) env('GOAOP_CACHE_PERMISSIONS', '770')),

    /*
    |--------------------------------------------------------------------------
    | Miscellaneous AOP Engine Features
    |--------------------------------------------------------------------------
    |
    | This option should contain a bitmask of values defined in
    | \Go\Aop\Features:
    |
    |   1  - enables interception of system functions.
    |   2  - enables interception of "new" operator in the source code.
    |   4  - enables interception of "include"/"require" operations
    |       in legacy code.
    |   64 - do not check the cache presence and assume that cache
    |       is already prepared.
    |
    | <code>
    |   //
    |   // bitmask of 1 + 2 + 4 options.
    |   //
    |   'features' => 1 | 2 | 4,
    |
    | </code>
    |
    */

    'features' => (int) env('GOAOP_FEATURES', 0),

    /*
    |--------------------------------------------------------------------------
    | Directories White List
    |--------------------------------------------------------------------------
    |
    | AOP will check this list to apply an AOP to selected directories only,
    | leave it empty if you want AOP to be applied to all files in the appDir.
    |
    */

    'includePaths' => [
        app()->path()
    ],

    /*
    |--------------------------------------------------------------------------
    | Directories Black List
    |--------------------------------------------------------------------------
    |
    | AOP will check this list to disable AOP for selected directories,
    | e.g. paths holding classes that must never be proxied (exception
    | handlers, the aspects themselves, generated code, ...).
    |
    */

    'excludePaths' => [],

    /*
    |--------------------------------------------------------------------------
    | Application Aspects
    |--------------------------------------------------------------------------
    |
    | List of aspect classes (implementing \Go\Aop\Aspect, with advice
    | methods declared via PHP attributes such as #[Before], #[After],
    | #[Around] and #[AfterThrowing]) that should be registered
    | automatically. Aspects are resolved through the Laravel container,
    | so they can use constructor dependency injection.
    |
    | Alternatively (or additionally), tag any service with "goaop.aspect"
    | in one of your service providers.
    |
    */

    'aspects' => [],
];
