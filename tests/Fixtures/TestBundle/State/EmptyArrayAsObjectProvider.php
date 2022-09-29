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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\EmptyArrayAsObject;

final class EmptyArrayAsObjectProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): EmptyArrayAsObject
    {
        return new EmptyArrayAsObject();
    }
}
