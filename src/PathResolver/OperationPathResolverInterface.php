<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\PathResolver;

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

/**
 * Resolves the path of a resource operation.
 *
 * @author Paul Le Corre <paul@lecorre.me>
 * @author Jérémy Leherpeur <jeremy@leherpeur.net>
 */
interface OperationPathResolverInterface
{
    /**
     * Resolves the operation path.
     *
     * @param ResourceMetadata $resourceMetadata
     * @param array            $operation        The operation metadata
     * @param bool             $collection
     *
     * @return string
     */
    public function resolveOperationPath(ResourceMetadata $resourceMetadata, array $operation, bool $collection) : string;
}
