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

use ApiPlatform\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Util\AnnotationFilterExtractorTrait;
use Doctrine\Common\Annotations\Reader;

/**
 * Creates a resource metadata from {@see Resource} annotations.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class FiltersResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use AnnotationFilterExtractorTrait;

    private $decorated;
    private $reader;

    public function __construct(ResourceMetadataCollectionFactoryInterface $decorated = null, ?Reader $reader = null)
    {
        $this->decorated = $decorated;
        if ($reader) {
            $this->reader = $reader;
        }
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
        } catch (\ReflectionException $reflectionException) {
            throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
        }

        $filters = array_keys($this->readFilterAnnotations($reflectionClass, $this->reader));

        if (\count($filters)) {
            trigger_deprecation('api-platform/core', '2.7', 'Use php attributes instead of doctrine annotations to declare filters.');
        }

        foreach ($resourceMetadataCollection as $i => $resource) {
            foreach ($operations = $resource->getOperations() as $operationName => $operation) {
                $operations->add($operationName, $operation->withFilters(array_unique(array_merge($resource->getFilters() ?? [], $operation->getFilters() ?? [], $filters))));
            }

            $resourceMetadataCollection[$i] = $resource->withOperations($operations);

            foreach ($graphQlOperations = $resource->getGraphQlOperations() ?? [] as $operationName => $operation) {
                $graphQlOperations[$operationName] = $operation->withFilters(array_unique(array_merge($resource->getFilters() ?? [], $operation->getFilters() ?? [], $filters)));
            }

            $resourceMetadataCollection[$i] = $resource->withGraphQlOperations($graphQlOperations);
        }

        return $resourceMetadataCollection;
    }
}
