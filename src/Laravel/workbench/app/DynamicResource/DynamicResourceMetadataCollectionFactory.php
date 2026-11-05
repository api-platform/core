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

namespace Workbench\App\DynamicResource;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Decorates the innermost resource metadata factory so that the core factories still apply their defaults.
 */
final class DynamicResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $decorated)
    {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        if (!DynamicResourceDefinitions::has($resourceClass)) {
            return $this->decorated->create($resourceClass);
        }

        return new ResourceMetadataCollection($resourceClass, [DynamicResourceDefinitions::resource($resourceClass)]);
    }
}
