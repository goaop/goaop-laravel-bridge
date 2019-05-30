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

    'debug' => env('GOAOP_DEBUG', env('APP_DEBUG', false)),

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
    |
    */

    'cacheDir' => env('GOAOP_CACHE_DIR', storage_path('app/aspect')),

    /*
    |--------------------------------------------------------------------------
    | Cache File Mode
    |--------------------------------------------------------------------------
    |
    | If configured then will be used as cache file mode for chmod.
    |
    */

    'cacheFileMode' => env('GOAOP_CACHE_PERMISSIONS', 511),

    /*
    |--------------------------------------------------------------------------
    | Miscellaneous AOP Engine Features
    |--------------------------------------------------------------------------
    |
    | This option should contain a bitmask of values defined in
    | \Go\Aop\Features enumeration:
    |
    | Support Options:
    |   1  - enables interception of system function.
    |   2  - enables interception of "new" operator in the source code.
    |   4  - enables interception of "include"/"require" operations
    |       in legacy code.
    |   64 - do not check the cache presence and assume that cache
    |       is already prepared
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

    'features' => env('GOAOP_FEATURES', 0),

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
        app_path()
    ],

    /*
    |--------------------------------------------------------------------------
    | Directories Black List
    |--------------------------------------------------------------------------
    |
    | AOP will check this list to disable AOP for selected directories.
    |
    | Note: The App\Exceptions\Handler SHOULD NOT be handled by the
    | GO! AOP framework, since in case of fatal errors, it will not be
    | possible to correctly process an Exception.
    |
    */

    'excludePaths' => [
        app_path('Exceptions')
    ],

    /*
    |--------------------------------------------------------------------------
    | AOP Container
    |--------------------------------------------------------------------------
    |
    | This option can be useful for extension and fine-tuning of services.
    |
    */

    'containerClass' => \Go\Core\GoAspectContainer::class,
];
