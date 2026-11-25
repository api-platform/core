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

namespace ApiPlatform\Laravel\Tests;

use ApiPlatform\HttpCache\PurgeTagProviderInterface;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;

class MockPurgeTagProvider implements PurgeTagProviderInterface
{
    public function getTags(object $resource, Operation $operation, array $context = []): iterable
    {
        if ($operation instanceof DeleteOperationInterface) {
            return ['provider_delete'];
        }

        return \is_object($context['previous_data'] ?? null) ? ['provider_update'] : ['provider_insert'];
    }
}
