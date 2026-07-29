<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests\Fixtures\Weaving;

/**
 * Weaving target for the DeclareParents introduction test. Deliberately
 * declares no interfaces of its own — Stringable is introduced by the
 * IntroductionAspect at weave time.
 */
class ReportGenerator
{
    public function title(): string
    {
        return 'Quarterly report';
    }
}
