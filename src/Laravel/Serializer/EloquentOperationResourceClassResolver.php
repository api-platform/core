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

namespace ApiPlatform\Laravel\Serializer;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\Serializer\OperationResourceClassResolver;

/**
 * Laravel Eloquent-specific operation resource resolver.
 *
 * Handles model-to-resource mappings from Laravel's stateOptions:
 * - getModelClass() for Eloquent models
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class EloquentOperationResourceClassResolver extends OperationResourceClassResolver
{
    use ClassInfoTrait;

    public function resolve(object|string $resource, Operation $operation): string
    {
        if (\is_string($resource)) {
            return $resource;
        }

        $objectClass = $this->getObjectClass($resource);
        $stateOptions = $operation->getStateOptions();

        // Laravel-specific: Check for model class in stateOptions
        if ($stateOptions && method_exists($stateOptions, 'getModelClass')) {
            $modelClass = $stateOptions->getModelClass();

            // Validate object matches the backing model class
            if ($modelClass && is_a($objectClass, $modelClass, true)) {
                return $operation->getClass();
            }
        }

        // Fallback to core behavior
        return parent::resolve($resource, $operation);
    }
}
