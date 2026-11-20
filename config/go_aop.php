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
     | Debug Mode
     |--------------------------------------------------------------------------
     |
     | In debug mode, breakpoints in the original source code work, and the
     | engine compiles a class again when its original file changes.
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
    | Behaviors are attached only to classes in this directory, change it
    | to app_path() if needed.
    |
    */

    'appDir' => base_path(),

    /*
    |--------------------------------------------------------------------------
    | Cache Directory
    |--------------------------------------------------------------------------
    |
    | The engine puts all compiled files and caches in this directory.
    | Use "php artisan aop:warmup" to build the cache during deployment.
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
    | Parsed Files Limit
    |--------------------------------------------------------------------------
    |
    | Number of parsed source files (about 0.2 MB each) the engine keeps in
    | memory while compiling: more re-parses less, fewer uses less memory.
    | Must be a positive integer. It is also the default number of files
    | kept by each process of "php artisan aop:warmup" (--cached-files).
    |
    */

    'parsedFilesLimit' => (int) env('GOAOP_PARSED_FILES_LIMIT', 100),

    /*
    |--------------------------------------------------------------------------
    | Miscellaneous Engine Features
    |--------------------------------------------------------------------------
    |
    | This option should contain a bitmask of values defined in
    | \Go\Aop\Features:
    |
    |   4  - enables interception of "include"/"require" operations
    |       in legacy code (Features::INTERCEPT_INCLUDES).
    |   64 - do not check the cache presence and assume that cache
    |       is already prepared (Features::PREBUILT_CACHE), e.g. after
    |       "php artisan aop:warmup" during deployment. A class below the
    |       include paths that the cache misses then fails the request.
    |   256 - compile classes whose parent class, interface or trait can not
    |       be located by Composer (Features::PARTIAL_REFLECTION): members
    |       inherited from a missing ancestor are not intercepted.
    |
    | Behaviors on system functions and on the "new" operator no longer
    | need a feature flag: their matching rules (pointcuts) enable them.
    | Unknown bits (such as the former 1 and 2) are rejected by the engine.
    |
    | <code>
    |   //
    |   // bitmask of 4 + 64 options.
    |   //
    |   'features' => 4 | 64,
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
    | Behaviors are attached only to classes in these directories; leave
    | it empty to compile all files in the appDir.
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
    | Classes in these directories never get behaviors, e.g. paths holding
    | classes that must never be proxied (exception handlers, the behavior
    | classes themselves, generated code, ...).
    |
    */

    'excludePaths' => [],

    /*
    |--------------------------------------------------------------------------
    | Behavior Classes
    |--------------------------------------------------------------------------
    |
    | List of behavior classes (aspects: classes implementing
    | \Go\Aop\Aspect, with behaviors declared via PHP attributes such as
    | #[Before], #[After], #[Around] and #[AfterThrowing]) that should be
    | registered automatically. They are resolved through the Laravel
    | container, so they can use constructor dependency injection.
    |
    | Alternatively (or additionally), tag any service with "goaop.aspect"
    | in one of your service providers.
    |
    */

    'aspects' => [],
];
