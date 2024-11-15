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

use ApiPlatform\Doctrine\Odm\State\Options as DoctrineODMOptions;
use ApiPlatform\Doctrine\Orm\State\Options as DoctrineORMOptions;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use ApiPlatform\Serializer\Filter\FilterInterface as SerializerFilterInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Prepares Parameters documentation by reading its filter details and declaring an OpenApi parameter.
 *
 * @experimental
 */
final class ParameterResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    private array $localPropertyCache;

    public function __construct(
        private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory,
        private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null,
        private readonly ?ContainerInterface $filterLocator = null,
        private readonly ?NameConverterInterface $nameConverter = null,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated?->create($resourceClass) ?? new ResourceMetadataCollection($resourceClass);

        foreach ($resourceMetadataCollection as $i => $resource) {
            $operations = $resource->getOperations();

            $internalPriority = -1;
            foreach ($operations as $operationName => $operation) {
                $parameters = $this->getDefaultParameters($operation, $resourceClass, $internalPriority);
                if (\count($parameters) > 0) {
                    $operations->add($operationName, $operation->withParameters($parameters));
                }
            }

            $resourceMetadataCollection[$i] = $resource->withOperations($operations->sort());

            if (!$graphQlOperations = $resource->getGraphQlOperations()) {
                continue;
            }

            $internalPriority = -1;
            foreach ($graphQlOperations as $operationName => $operation) {
                $parameters = $this->getDefaultParameters($operation, $resourceClass, $internalPriority);
                if (\count($parameters) > 0) {
                    $graphQlOperations[$operationName] = $operation->withParameters($parameters);
                }
            }

            $resourceMetadataCollection[$i] = $resource->withGraphQlOperations($graphQlOperations);
        }

        return $resourceMetadataCollection;
    }

    /**
     * @return array{propertyNames: string[], properties: array<string, ApiProperty>}
     */
    private function getProperties(string $resourceClass): array
    {
        if (isset($this->localPropertyCache[$resourceClass])) {
            return $this->localPropertyCache[$resourceClass];
        }

        $propertyNames = [];
        $properties = [];
        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $property);
            if ($propertyMetadata->isReadable()) {
                $propertyNames[] = $property;
                $properties[$property] = $propertyMetadata;
            }
        }

        $this->localPropertyCache = [$resourceClass => ['propertyNames' => $propertyNames, 'properties' => $properties]];

        return $this->localPropertyCache[$resourceClass];
    }

    private function getDefaultParameters(Operation $operation, string $resourceClass, int &$internalPriority): Parameters
    {
        ['propertyNames' => $propertyNames, 'properties' => $properties] = $this->getProperties($resourceClass);
        $parameters = $operation->getParameters() ?? new Parameters();
        foreach ($parameters as $key => $parameter) {
            if (':property' === $key) {
                foreach ($propertyNames as $property) {
                    $converted = $this->nameConverter?->denormalize($property) ?? $property;
                    $propertyParameter = $this->setDefaults($converted, $parameter, $resourceClass, $properties, $operation);
                    $priority = $propertyParameter->getPriority() ?? $internalPriority--;
                    $parameters->add($converted, $propertyParameter->withPriority($priority)->withKey($converted));
                }

                $parameters->remove($key, $parameter::class);
                continue;
            }

            $key = $parameter->getKey() ?? $key;

            if (str_contains($key, ':property')) {
                $p = [];
                foreach ($propertyNames as $prop) {
                    $p[$this->nameConverter?->denormalize($prop) ?? $prop] = $prop;
                }

                $parameter = $parameter->withExtraProperties($parameter->getExtraProperties() + ['_properties' => $p]);
            }

            $parameter = $this->setDefaults($key, $parameter, $resourceClass, $properties, $operation);
            $priority = $parameter->getPriority() ?? $internalPriority--;
            $parameters->add($key, $parameter->withPriority($priority));
        }

        return $parameters;
    }

    private function addFilterMetadata(Parameter $parameter): Parameter
    {
        if (!($filterId = $parameter->getFilter())) {
            return $parameter;
        }

        $filter = \is_object($filterId) ? $filterId : $this->filterLocator->get($filterId);

        if (!$filter) {
            return $parameter;
        }

        if (null === $parameter->getSchema() && $filter instanceof JsonSchemaFilterInterface && $schema = $filter->getSchema($parameter)) {
            $parameter = $parameter->withSchema($schema);
        }

        if (null === $parameter->getOpenApi() && $filter instanceof OpenApiParameterFilterInterface && ($openApiParameter = $filter->getOpenApiParameters($parameter)) && $openApiParameter instanceof OpenApiParameter) {
            $parameter = $parameter->withOpenApi($openApiParameter);
        }

        return $parameter;
    }

    /**
     * @param array<string, ApiProperty> $properties
     */
    private function setDefaults(string $key, Parameter $parameter, string $resourceClass, array $properties, Operation $operation): Parameter
    {
        if (null === $parameter->getKey()) {
            $parameter = $parameter->withKey($key);
        }

        $filter = $parameter->getFilter();
        if (\is_string($filter) && $this->filterLocator->has($filter)) {
            $filter = $this->filterLocator->get($filter);
        }

        if ($filter instanceof SerializerFilterInterface && null === $parameter->getProvider()) {
            $parameter = $parameter->withProvider('api_platform.serializer.filter_parameter_provider');
        }

        // Read filter description to populate the Parameter
        $description = $filter instanceof FilterInterface ? $filter->getDescription($this->getFilterClass($operation)) : [];
        if (($schema = $description[$key]['schema'] ?? null) && null === $parameter->getSchema()) {
            $parameter = $parameter->withSchema($schema);
        }

        $currentKey = $key;
        if (null === $parameter->getProperty() && isset($properties[$key])) {
            $parameter = $parameter->withProperty($key);
        }

        if (null === $parameter->getProperty() && $this->nameConverter && ($nameConvertedKey = $this->nameConverter->normalize($key)) && isset($properties[$nameConvertedKey])) {
            $parameter = $parameter->withProperty($key)->withExtraProperties(['_query_property' => $nameConvertedKey] + $parameter->getExtraProperties());
            $currentKey = $nameConvertedKey;
        }

        if ($this->nameConverter && $property = $parameter->getProperty()) {
            $parameter = $parameter->withProperty($this->nameConverter->normalize($property));
        }

        if (isset($properties[$currentKey]) && ($eloquentRelation = ($properties[$currentKey]->getExtraProperties()['eloquent_relation'] ?? null)) && isset($eloquentRelation['foreign_key'])) {
            $parameter = $parameter->withExtraProperties(['_query_property' => $eloquentRelation['foreign_key']] + $parameter->getExtraProperties());
        }

        if (null === $parameter->getRequired() && ($required = $description[$key]['required'] ?? null)) {
            $parameter = $parameter->withRequired($required);
        }

        return $this->addFilterMetadata($parameter);
    }

    private function getFilterClass(Operation $operation): ?string
    {
        $stateOptions = $operation->getStateOptions();
        if ($stateOptions instanceof DoctrineORMOptions) {
            return $stateOptions->getEntityClass();
        }
        if ($stateOptions instanceof DoctrineODMOptions) {
            return $stateOptions->getDocumentClass();
        }

        return $operation->getClass();
    }
}
