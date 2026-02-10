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

namespace ApiPlatform\Serializer;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Util\ClassInfoTrait;

/**
 * Generic operation resource resolver.
 *
 * This is the base implementation that simply returns the object's actual class.
 * Framework-specific resolvers (Doctrine, Laravel, etc.) extend this to add
 * validation against entity/model classes from their stateOptions.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class OperationResourceResolver implements OperationResourceResolverInterface
{
    use ClassInfoTrait;

    /**
     * Generic implementation: returns the object's actual class.
     *
     * Framework-specific resolvers will override to validate against entity/model
     * classes from their stateOptions.
     */
    public function resolve(object|string $resource, Operation $operation): string
    {
        if (\is_string($resource)) {
            return $resource;
        }

        // Core: just return the object's actual class
        // Decorators will add framework-specific entity validation
        return $this->getObjectClass($resource);
    }
}
