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

namespace ApiPlatform\Tests\Fixtures\TestBundle\DynamicResource;

use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;

final class DynamicPropertyNameCollectionFactory implements PropertyNameCollectionFactoryInterface
{
    public function __construct(
        private readonly PropertyNameCollectionFactoryInterface $decorated,
        private readonly \Closure $resourceMetadataCollectionFactory,
    ) {
    }

    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        if (DynamicRecord::class !== $resourceClass) {
            return $this->decorated->create($resourceClass, $options);
        }

        $resource = DynamicResourceDefinitions::resourceForOperation(($this->resourceMetadataCollectionFactory)(), $options['operation_name'] ?? null);

        return new PropertyNameCollection(array_keys(DynamicResourceDefinitions::properties($resource)));
    }
}
