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

namespace ApiPlatform\Doctrine\Odm\Serializer;

use ApiPlatform\Doctrine\Odm\State\Options;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\Serializer\OperationResourceClassResolver;

/**
 * Doctrine ODM operation resource resolver.
 *
 * Handles document-to-resource mappings from Doctrine ODM's stateOptions.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DoctrineOdmOperationResourceClassResolver extends OperationResourceClassResolver
{
    use ClassInfoTrait;

    public function resolve(object|string $resource, Operation $operation): string
    {
        if (\is_string($resource)) {
            return $resource;
        }

        $objectClass = $this->getObjectClass($resource);
        $stateOptions = $operation->getStateOptions();

        // Doctrine ODM: Check for document class in stateOptions
        if ($stateOptions instanceof Options) {
            $documentClass = $stateOptions->getDocumentClass();

            // Validate object matches the backing document class
            if ($documentClass && is_a($objectClass, $documentClass, true)) {
                return $operation->getClass();
            }
        }

        // Fallback to core behavior
        return parent::resolve($resource, $operation);
    }
}
