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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\Serializer\OperationResourceResolver;

/**
 * Doctrine-specific operation resource resolver.
 *
 * Handles entity-to-resource mappings from Doctrine's stateOptions:
 * - getEntityClass() for ORM entities
 * - getDocumentClass() for ODM documents
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DoctrineOperationResourceResolver extends OperationResourceResolver
{
    use ClassInfoTrait;

    public function resolve(object|string $resource, Operation $operation): string
    {
        if (\is_string($resource)) {
            return $resource;
        }

        $objectClass = $this->getObjectClass($resource);
        $stateOptions = $operation->getStateOptions();

        // Doctrine-specific: Check for entity or document class in stateOptions
        if ($stateOptions) {
            $entityClass = method_exists($stateOptions, 'getEntityClass')
                ? $stateOptions->getEntityClass()
                : (method_exists($stateOptions, 'getDocumentClass')
                    ? $stateOptions->getDocumentClass()
                    : null);

            // Validate object matches the backing entity/document class
            if ($entityClass && is_a($objectClass, $entityClass, true)) {
                return $operation->getClass();
            }
        }

        // Fallback to core behavior
        return parent::resolve($resource, $operation);
    }
}
