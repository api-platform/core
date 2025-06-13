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

/**
 * @implements ProviderInterface<ServiceProvider>
 */
class CustomProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): \stdClass
    {
        $s = new \stdClass();
        $s->test = 'ok';

        return $s;
    }
}
