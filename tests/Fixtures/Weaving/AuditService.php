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
 * Service that constructor-injects both a woven repository and another
 * service — the exact shape that broke interception in issue #8.
 */
class AuditService
{
    public function __construct(
        private readonly PositionRepository $positionRepository,
        private readonly PositionService $positionService,
    ) {
    }

    /**
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function record(array $data): array
    {
        $data['recorded_for'] = $this->positionService->defaultPosition();

        return $this->positionRepository->create($data);
    }
}
