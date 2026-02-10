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

/**
 * Resolves the resource class to use for serializing or generating IRIs.
 *
 * Validates that objects match the entity/model class from operation's stateOptions
 * before mapping them to the resource class. This prevents entity-to-resource
 * mappings from leaking to unrelated objects (e.g., DateTimeImmutable).
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface OperationResourceResolverInterface
{
    /**
     * Resolves the resource class to use for serializing/generating IRIs.
     *
     * @param object|string $resource  The object or class name to resolve
     * @param Operation     $operation The operation context providing stateOptions
     *
     * @return string The resource class to use
     */
    public function resolve(object|string $resource, Operation $operation): string;
}
