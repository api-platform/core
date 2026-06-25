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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Util\AttributeFilterExtractorTrait;

/**
 * Creates a resource metadata from {@see Resource} annotations.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class FiltersResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use AttributeFilterExtractorTrait;

    public function __construct(private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass);

        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException) {
            throw new ResourceClassNotFoundException(\sprintf('Resource "%s" not found.', $resourceClass));
        }

        $classFilters = $this->readFilterAttributes($reflectionClass);
        $filters = [];

        foreach ($classFilters as $id => [$args, $filterClass, $attribute]) {
            if (!$attribute->alias) {
                $filters[] = $id;
            }
        }

        foreach ($resourceMetadataCollection as $i => $resource) {
            foreach ($operations = $resource->getOperations() ?? [] as $operationName => $operation) {
                $operationFilters = array_unique(array_merge($resource->getFilters() ?? [], $operation->getFilters() ?? [], $filters));
                if ($operationFilters) {
                    trigger_deprecation('api-platform/core', '4.4', \sprintf('Declaring filters on the "%s" operation through "Operation::$filters" is deprecated, use the "parameters" argument instead. It will be removed in 6.0.', $operation->getShortName()));
                }

                $operations->add($operationName, $operation->withFilters($operationFilters));
            }

            if ($operations) {
                $resourceMetadataCollection[$i] = $resource->withOperations($operations);
            }

            foreach ($graphQlOperations = $resource->getGraphQlOperations() ?? [] as $operationName => $operation) {
                $graphQlOperations[$operationName] = $operation->withFilters(array_unique(array_merge($resource->getFilters() ?? [], $operation->getFilters() ?? [], $filters)));
            }

            if ($graphQlOperations) {
                $resourceMetadataCollection[$i] = $resource->withGraphQlOperations($graphQlOperations);
            }
        }

        return $resourceMetadataCollection;
    }
}
