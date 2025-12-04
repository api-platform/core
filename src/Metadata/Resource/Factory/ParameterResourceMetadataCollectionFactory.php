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

use ApiPlatform\Doctrine\Common\Filter\PropertyAwareFilterInterface;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\ParameterProviderFilterInterface;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\PropertiesAwareInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use ApiPlatform\Serializer\Filter\FilterInterface as SerializerFilterInterface;
use ApiPlatform\State\Parameter\ValueCaster;
use ApiPlatform\State\Util\StateOptionsTrait;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * Prepares Parameters documentation by reading its filter details and declaring an OpenApi parameter.
 */
final class ParameterResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use StateOptionsTrait;

    private array $localPropertyCache = [];

    public function __construct(
        private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory,
        private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null,
        private readonly ?ContainerInterface $filterLocator = null,
        private readonly ?NameConverterInterface $nameConverter = null,
        private readonly ?LoggerInterface $logger = null,
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
    private function getProperties(string $resourceClass, ?Parameter $parameter = null): array
    {
        $k = $resourceClass.($parameter?->getProperties() ? ($parameter->getKey() ?? '') : '').(\is_string($parameter->getFilter()) ? $parameter->getFilter() : '');
        if (isset($this->localPropertyCache[$k])) {
            return $this->localPropertyCache[$k];
        }

        $propertyNames = [];
        $properties = [];
        foreach ($parameter?->getProperties() ?? $this->propertyNameCollectionFactory->create($resourceClass) as $property) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $property);
            if ($propertyMetadata->isReadable()) {
                $propertyNames[] = $property;
                $properties[$property] = $propertyMetadata;
            }
        }

        if (($filter = $this->getFilterInstance($parameter->getFilter())) && $filter instanceof PropertyAwareFilterInterface) {
            if (!method_exists($filter, 'getProperties')) { // @phpstan-ignore-line todo 5.x remove this check
                trigger_deprecation('api-platform/core', 'In API Platform 5.0 "%s" will implement a method named "getProperties"', PropertyAwareFilterInterface::class);
                $refl = new \ReflectionClass($filter);
                $filterProperties = $refl->hasProperty('properties') ? $refl->getProperty('properties')->getValue($filter) : [];
            } else {
                $filterProperties = array_keys($filter->getProperties() ?? []);
            }

            foreach ($filterProperties as $prop) {
                if (!\in_array($prop, $propertyNames, true)) {
                    $propertyNames[] = $this->nameConverter?->denormalize($prop) ?? $prop;
                }
            }
        }

        $this->localPropertyCache[$k] = ['propertyNames' => $propertyNames, 'properties' => $properties];

        return $this->localPropertyCache[$k];
    }

    private function getDefaultParameters(Operation $operation, string $resourceClass, int &$internalPriority): Parameters
    {
        $propertyNames = $properties = [];
        $parameters = $operation->getParameters() ?? new Parameters();

        // First loop we look for the :property placeholder and replace its key
        foreach ($parameters as $key => $parameter) {
            if (!str_contains($key, ':property')) {
                continue;
            }

            ['propertyNames' => $propertyNames, 'properties' => $properties] = $this->getProperties($resourceClass, $parameter);
            $parameter = $parameter->withProperties($propertyNames);

            foreach ($propertyNames as $property) {
                $converted = $this->nameConverter?->denormalize($property) ?? $property;
                $finalKey = str_replace(':property', $converted, $key);
                $parameters->add(
                    $finalKey,
                    $parameter->withProperty($converted)->withKey($finalKey)
                );
            }

            $parameters->remove($key, $parameter::class);
        }

        foreach ($parameters as $key => $parameter) {
            if (!$parameter->getKey()) {
                $parameter = $parameter->withKey($key);
            }

            $filter = $this->getFilterInstance($parameter->getFilter());

            // The filter has a parameter provider
            if (null === $parameter->getProvider() && (($f = $parameter->getFilter()) && $f instanceof ParameterProviderFilterInterface)) {
                $parameter = $parameter->withProvider($f->getParameterProvider());
            }

            $key = $parameter->getKey() ?? $key;

            ['propertyNames' => $propertyNames, 'properties' => $properties] = $this->getProperties($resourceClass, $parameter);

            if ($filter instanceof PropertiesAwareInterface) {
                $parameter = $parameter->withProperties($propertyNames);
            }

            $parameter = $this->setDefaults($key, $parameter, $filter, $properties, $operation);
            // We don't do any type cast yet, a query parameter or an header is always a string or a list of strings
            if (null === $parameter->getNativeType()) {
                // this forces the type to be only a list
                if ('array' === ($parameter->getSchema()['type'] ?? null)) {
                    $parameter = $parameter->withNativeType(Type::list(Type::string()));
                } elseif ('string' === ($parameter->getSchema()['type'] ?? null)) {
                    $parameter = $parameter->withNativeType(Type::string());
                } elseif ('boolean' === ($parameter->getSchema()['type'] ?? null)) {
                    $parameter = $parameter->withNativeType(Type::bool());
                } elseif ('integer' === ($parameter->getSchema()['type'] ?? null)) {
                    $parameter = $parameter->withNativeType(Type::int());
                } elseif ('number' === ($parameter->getSchema()['type'] ?? null)) {
                    $parameter = $parameter->withNativeType(Type::float());
                } else {
                    $parameter = $parameter->withNativeType(Type::union(Type::string(), Type::list(Type::string())));
                }
            }

            if ($parameter->getCastToNativeType() && null === $parameter->getCastFn() && ($nativeType = $parameter->getNativeType())) {
                if ($nativeType->isIdentifiedBy(TypeIdentifier::BOOL)) {
                    $parameter = $parameter->withCastFn([ValueCaster::class, 'toBool']);
                }
                if ($nativeType->isIdentifiedBy(TypeIdentifier::INT)) {
                    $parameter = $parameter->withCastFn([ValueCaster::class, 'toInt']);
                }
                if ($nativeType->isIdentifiedBy(TypeIdentifier::FLOAT)) {
                    $parameter = $parameter->withCastFn([ValueCaster::class, 'toFloat']);
                }
            }

            $priority = $parameter->getPriority() ?? $internalPriority--;
            $parameters->add($key, $parameter->withPriority($priority));
        }

        return $parameters;
    }

    private function addFilterMetadata(Parameter $parameter): Parameter
    {
        if (!$filter = $this->getFilterInstance($parameter->getFilter())) {
            return $parameter;
        }

        if ($filter instanceof ParameterProviderFilterInterface) {
            $parameter = $parameter->withProvider($filter::getParameterProvider());
        }

        if (null === $parameter->getSchema() && $filter instanceof JsonSchemaFilterInterface && $schema = $filter->getSchema($parameter)) {
            $parameter = $parameter->withSchema($schema);
        }

        if (null === $parameter->getOpenApi() && $filter instanceof OpenApiParameterFilterInterface && ($openApiParameter = $filter->getOpenApiParameters($parameter))) {
            $parameter = $parameter->withOpenApi($openApiParameter);
        }

        return $parameter;
    }

    /**
     * @param array<string, ApiProperty> $properties
     */
    private function setDefaults(string $key, Parameter $parameter, ?object $filter, array $properties, Operation $operation): Parameter
    {
        if (null === $parameter->getKey()) {
            $parameter = $parameter->withKey($key);
        }

        if ($filter instanceof SerializerFilterInterface && null === $parameter->getProvider()) {
            $parameter = $parameter->withProvider('api_platform.serializer.filter_parameter_provider');
        }

        $currentKey = $key;
        if (null === $parameter->getProperty() && isset($properties[$key])) {
            $parameter = $parameter->withProperty($key);
        }

        if ($this->nameConverter && $property = $parameter->getProperty()) {
            $parameter = $parameter->withProperty($this->nameConverter->normalize($property));
        }

        if (isset($properties[$currentKey]) && ($eloquentRelation = ($properties[$currentKey]->getExtraProperties()['eloquent_relation'] ?? null)) && isset($eloquentRelation['foreign_key'])) {
            $parameter = $parameter->withProperty($eloquentRelation['foreign_key']);
        }

        $parameter = $this->addFilterMetadata($parameter);

        if ($filter instanceof FilterInterface) {
            try {
                return $this->getLegacyFilterMetadata($parameter, $operation, $filter);
            } catch (RuntimeException $exception) {
                $this->logger?->alert($exception->getMessage(), ['exception' => $exception]);

                return $parameter;
            }
        }

        return $parameter;
    }

    private function getLegacyFilterMetadata(Parameter $parameter, Operation $operation, FilterInterface $filter): Parameter
    {
        $description = $filter->getDescription($this->getStateOptionsClass($operation, $operation->getClass()));
        $key = $parameter->getKey();
        if (($schema = $description[$key]['schema'] ?? null) && null === $parameter->getSchema()) {
            $parameter = $parameter->withSchema($schema);
        }

        if (null === $parameter->getProperty() && ($property = $description[$key]['property'] ?? null)) {
            $parameter = $parameter->withProperty($property);
        }

        if (null === $parameter->getRequired() && ($required = $description[$key]['required'] ?? null)) {
            $parameter = $parameter->withRequired($required);
        }

        if (null === $parameter->getOpenApi() && ($openApi = $description[$key]['openapi'] ?? null) && $openApi instanceof OpenApiParameter) {
            $parameter = $parameter->withOpenApi($openApi);
        }

        return $parameter;
    }

    /**
     * TODO: 5.x use FilterInterface on Laravel eloquent filters.
     *
     * @return FilterInterface|object
     */
    private function getFilterInstance(object|string|null $filter): ?object
    {
        if (!$filter) {
            return null;
        }

        if (\is_object($filter)) {
            return $filter;
        }

        if (!$this->filterLocator->has($filter)) {
            return null;
        }

        return $this->filterLocator->get($filter);
    }
}
