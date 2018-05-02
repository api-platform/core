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

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * @param object $object
     *
     * @throws Error
     */
    public function canAccess(ResourceAccessCheckerInterface $resourceAccessChecker = null, ResourceMetadata $resourceMetadata, string $resourceClass, ResolveInfo $info, $object = null, string $operationName = null)
    {
        if (null === $resourceAccessChecker) {
            return;
        }

        $isGranted = $resourceMetadata->getGraphqlAttribute($operationName, 'access_control', null, true);
        if (null === $isGranted || $resourceAccessChecker->isGranted($resourceClass, $isGranted, ['object' => $object])) {
            return;
        }

        throw Error::createLocatedError('Access Denied.', $info->fieldNodes, $info->path);
    }
}
