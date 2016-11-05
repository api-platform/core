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
 * Resolves the custom operations path.
 *
 * @author Guilhem N. <egetick@gmail.com>
 * @author Jérémy Leherpeur <jeremy@leherpeur.net>
 */
final class CustomOperationPathResolver implements OperationPathResolverInterface
{
    private $deferred;

    public function __construct(OperationPathResolverInterface $deferred)
    {
        $this->deferred = $deferred;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveOperationPath(ResourceMetadata $resourceMetadata, array $operation, bool $collection) : string
    {
        if (isset($operation['path'])) {
            return $operation['path'];
        }

        return $this->deferred->resolveOperationPath($resourceMetadata, $operation, $collection);
    }
}
