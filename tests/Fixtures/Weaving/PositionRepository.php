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
 * Advised weaving target whose pointcut match ("*Repository->create(*)")
 * must survive being constructor-injected into other services.
 */
class PositionRepository
{
    public function __construct(private readonly PositionService $positionService)
    {
    }

    /**
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $data['position'] ??= $this->positionService->defaultPosition();

        return $data;
    }
}
