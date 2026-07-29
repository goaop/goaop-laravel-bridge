<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests;

class ConfigPublishTest extends TestCase
{
    public function testConfigCanBePublishedWithTag(): void
    {
        $target = config_path('go_aop.php');
        if (file_exists($target)) {
            unlink($target);
        }

        try {
            $this->artisan('vendor:publish', ['--tag' => 'goaop-config'])->assertSuccessful();

            $this->assertFileExists($target);
        } finally {
            if (file_exists($target)) {
                unlink($target);
            }
        }
    }
}
