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

namespace ApiPlatform\Core\GraphQl\Resolver;

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Security\ResourceAccessCheckerInterface;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Checks if the current logged in user can access to this resource.
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait ResourceAccessCheckerTrait
{
    /**
     * @throws Error
     */
    public function canAccess(?ResourceAccessCheckerInterface $resourceAccessChecker, ResourceMetadata $resourceMetadata, string $resourceClass, ResolveInfo $info, $extraVariables = [], string $operationName = null): void
    {
        if (null === $resourceAccessChecker) {
            return;
        }

        $isGranted = $resourceMetadata->getGraphqlAttribute($operationName ?? '', 'access_control', null, true);
        if (null === $isGranted || $resourceAccessChecker->isGranted($resourceClass, $isGranted, $extraVariables)) {
            return;
        }

        throw Error::createLocatedError($resourceMetadata->getGraphqlAttribute($operationName ?? '', 'access_control_message', 'Access Denied.'), $info->fieldNodes, $info->path);
    }
}
