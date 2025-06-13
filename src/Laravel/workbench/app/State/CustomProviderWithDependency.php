<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Workbench\App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Workbench\App\Services\DummyService;

/**
 * @implements ProviderInterface<ServiceProvider>
 */
class CustomProviderWithDependency implements ProviderInterface
{
    public function __construct(private readonly DummyService $service)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $s = new \stdClass();
        $s->test = $this->service->dummyMethod();

        return $s;
    }
}
