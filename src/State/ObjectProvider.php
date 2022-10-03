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

namespace ApiPlatform\State;

use ApiPlatform\Metadata\Operation;

/**
 * An ItemProvider that just create a new object.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @experimental
 */
final class ObjectProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        $refl = new \ReflectionClass($operation->getClass());

        return $refl->newInstanceWithoutConstructor();
    }
}
