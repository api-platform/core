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

namespace ApiPlatform\Doctrine\Orm\Serializer;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\Serializer\OperationResourceClassResolver;

/**
 * Doctrine ORM operation resource resolver.
 *
 * Handles entity-to-resource mappings from Doctrine ORM's stateOptions.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DoctrineOrmOperationResourceClassResolver extends OperationResourceClassResolver
{
    use ClassInfoTrait;

    public function resolve(object|string $resource, Operation $operation): string
    {
        if (\is_string($resource)) {
            return $resource;
        }

        $objectClass = $this->getObjectClass($resource);
        $stateOptions = $operation->getStateOptions();

        // Doctrine ORM: Check for entity class in stateOptions
        if ($stateOptions instanceof Options) {
            $entityClass = $stateOptions->getEntityClass();

            // Validate object matches the backing entity class
            if ($entityClass && is_a($objectClass, $entityClass, true)) {
                return $operation->getClass();
            }
        }

        // Fallback to core behavior
        return parent::resolve($resource, $operation);
    }
}
