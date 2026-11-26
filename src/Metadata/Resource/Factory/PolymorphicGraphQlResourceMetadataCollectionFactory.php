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

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Util\CamelCaseToSnakeCaseNameConverter;
use Psr\Log\NullLogger;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;

/**
 * GraphQL does not support resources with a discriminator map yet: the operations
 * added by default are dropped, explicitly declared ones are rejected.
 */
final class PolymorphicGraphQlResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use OperationDefaultsTrait;

    public function __construct(
        private readonly ClassDiscriminatorResolverInterface $classDiscriminatorResolver,
        private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null,
        array $defaults = [],
    ) {
        $this->defaults = $defaults;
        $this->logger = new NullLogger();
        $this->camelCaseToSnakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated?->create($resourceClass) ?? new ResourceMetadataCollection($resourceClass);

        if (!$resourceMetadataCollection->count() || !$this->isPolymorphic($resourceClass)) {
            return $resourceMetadataCollection;
        }

        foreach ($resourceMetadataCollection as $key => $resource) {
            $graphQlOperations = [];
            $hadDefaults = false;
            foreach ($resource->getGraphQlOperations() ?? [] as $name => $operation) {
                if ($operation->getExtraProperties()['graphql_default_operation'] ?? false) {
                    $hadDefaults = true;
                    continue;
                }

                if ($operation instanceof Query && $operation->getNested()) {
                    $graphQlOperations[$name] = $operation;
                    continue;
                }

                throw new RuntimeException(\sprintf('GraphQL is not supported yet for "%s", a resource with a discriminator map. Remove its "graphQlOperations" or set them to an empty array.', $resourceClass));
            }

            if ($hadDefaults) {
                $completed = $this->completeGraphQlOperations($resource->withGraphQlOperations($graphQlOperations))->getGraphQlOperations();
                foreach ($completed as $name => $operation) {
                    if (!isset($graphQlOperations[$name])) {
                        [$name, $operation] = $this->getOperationWithDefaults($resource, $operation);
                    }
                    $graphQlOperations[$name] = $operation;
                }
            }

            if ($resource->getGraphQlOperations() !== $graphQlOperations) {
                $resourceMetadataCollection[$key] = $resource->withGraphQlOperations($graphQlOperations);
            }
        }

        return $resourceMetadataCollection;
    }

    private function isPolymorphic(string $resourceClass): bool
    {
        if (null !== $this->classDiscriminatorResolver->getMappingForClass($resourceClass)) {
            return true;
        }

        foreach (class_parents($resourceClass) ?: [] as $parent) {
            if (\in_array($resourceClass, $this->classDiscriminatorResolver->getMappingForClass($parent)?->getTypesMapping() ?? [], true)) {
                return true;
            }
        }

        return false;
    }
}
