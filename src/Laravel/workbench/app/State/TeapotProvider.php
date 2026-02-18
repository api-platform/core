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
use Workbench\App\ApiResource\ServiceProvider;
use Workbench\App\Exceptions\TeapotException;

/**
 * @implements ProviderInterface<ServiceProvider>
 */
class TeapotProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        throw new TeapotException('I\'m boiling');
    }
}
