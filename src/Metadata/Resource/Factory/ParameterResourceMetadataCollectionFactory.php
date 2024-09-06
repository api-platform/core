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

use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use ApiPlatform\Serializer\Filter\FilterInterface as SerializerFilterInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\DivisibleBy;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Unique;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Prepares Parameters documentation by reading its filter details and declaring an OpenApi parameter.
 *
 * @experimental
 */
final class ParameterResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(
        private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null,
        private readonly ?ContainerInterface $filterLocator = null,
        private readonly ?NameConverterInterface $nameConverter = null,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated?->create($resourceClass) ?? new ResourceMetadataCollection($resourceClass);
        $properties = array_flip(iterator_to_array($this->propertyNameCollectionFactory->create($resourceClass)));
        foreach ($resourceMetadataCollection as $i => $resource) {
            $operations = $resource->getOperations();

            $internalPriority = -1;
            foreach ($operations as $operationName => $operation) {
                $parameters = $operation->getParameters() ?? new Parameters();
                foreach ($parameters as $key => $parameter) {
                    if (':property' === $key) {
                        foreach ($properties as $property) {
                            $parameter = $this->setDefaults($property, $parameter, $resourceClass, $properties);
                            $priority = $parameter->getPriority() ?? $internalPriority--;
                            $parameters->add($property, $parameter->withPriority($priority)->withKey($property));
                        }

                        $parameters->remove($key, $parameter::class);
                        continue;
                    }

                    $key = $parameter->getKey() ?? $key;
                    $parameter = $this->setDefaults($key, $parameter, $resourceClass, $properties);
                    $priority = $parameter->getPriority() ?? $internalPriority--;
                    $parameters->add($key, $parameter->withPriority($priority));
                }

                // As we deprecate the parameter validator, we declare a parameter for each filter transfering validation to the new system
                if ($operation->getFilters() && 0 === $parameters->count()) {
                    $parameters = $this->addFilterValidation($operation);
                }

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
                $parameters = $operation->getParameters() ?? new Parameters();
                foreach ($operation->getParameters() ?? [] as $key => $parameter) {
                    $key = $parameter->getKey() ?? $key;
                    $parameter = $this->setDefaults($key, $parameter, $resourceClass, $properties);
                    $priority = $parameter->getPriority() ?? $internalPriority--;
                    $parameters->add($key, $parameter->withPriority($priority));
                }

                $graphQlOperations[$operationName] = $operation->withParameters($parameters);
            }

            $resourceMetadataCollection[$i] = $resource->withGraphQlOperations($graphQlOperations);
        }

        return $resourceMetadataCollection;
    }

    /**
     * @param array<string, int> $properties
     */
    private function setDefaults(string $key, Parameter $parameter, string $resourceClass, array $properties): Parameter
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
        $description = $filter instanceof FilterInterface ? $filter->getDescription($resourceClass) : [];
        if (($schema = $description[$key]['schema'] ?? null) && null === $parameter->getSchema()) {
            $parameter = $parameter->withSchema($schema);
        }

        if (null === $parameter->getProperty() && ($property = $description[$key]['property'] ?? null)) {
            $parameter = $parameter->withProperty($property);
        }

        if (null === $parameter->getProperty() && isset($properties[$key])) {
            $parameter = $parameter->withProperty($key);
        }

        if (null === $parameter->getProperty() && ($nameConvertedKey = $this->nameConverter->normalize($key)) && isset($properties[$nameConvertedKey])) {
            $parameter = $parameter->withProperty($nameConvertedKey);
        }

        if (null === $parameter->getRequired() && ($required = $description[$key]['required'] ?? null)) {
            $parameter = $parameter->withRequired($required);
        }

        if (null === $parameter->getOpenApi() && ($openApi = $description[$key]['openapi'] ?? null) && $openApi instanceof OpenApiParameter) {
            $parameter = $parameter->withOpenApi($openApi);
        }

        $schema = $parameter->getSchema() ?? (($openApi = $parameter->getOpenApi()) ? $openApi->getSchema() : null);

        // Only add validation if the Symfony Validator is installed
        if (interface_exists(ValidatorInterface::class) && !$parameter->getConstraints()) {
            $parameter = $this->addSchemaValidation($parameter, $schema, $parameter->getRequired() ?? $description['required'] ?? false, $parameter->getOpenApi() ?: null);
        }

        return $parameter;
    }

    private function addSchemaValidation(Parameter $parameter, ?array $schema = null, bool $required = false, ?OpenApiParameter $openApi = null): Parameter
    {
        $assertions = [];

        if ($required && false !== ($allowEmptyValue = $openApi?->getAllowEmptyValue())) {
            $assertions[] = new NotNull(message: \sprintf('The parameter "%s" is required.', $parameter->getKey()));
        }

        if (false === ($allowEmptyValue ?? $openApi?->getAllowEmptyValue())) {
            $assertions[] = new NotBlank(allowNull: !$required);
        }

        if (isset($schema['exclusiveMinimum'])) {
            $assertions[] = new GreaterThan(value: $schema['exclusiveMinimum']);
        }

        if (isset($schema['exclusiveMaximum'])) {
            $assertions[] = new LessThan(value: $schema['exclusiveMaximum']);
        }

        if (isset($schema['minimum'])) {
            $assertions[] = new GreaterThanOrEqual(value: $schema['minimum']);
        }

        if (isset($schema['maximum'])) {
            $assertions[] = new LessThanOrEqual(value: $schema['maximum']);
        }

        if (isset($schema['pattern'])) {
            $assertions[] = new Regex($schema['pattern']);
        }

        if (isset($schema['maxLength']) || isset($schema['minLength'])) {
            $assertions[] = new Length(min: $schema['minLength'] ?? null, max: $schema['maxLength'] ?? null);
        }

        if (isset($schema['minItems']) || isset($schema['maxItems'])) {
            $assertions[] = new Count(min: $schema['minItems'] ?? null, max: $schema['maxItems'] ?? null);
        }

        if (isset($schema['multipleOf'])) {
            $assertions[] = new DivisibleBy(value: $schema['multipleOf']);
        }

        if ($schema['uniqueItems'] ?? false) {
            $assertions[] = new Unique();
        }

        if (isset($schema['enum'])) {
            $assertions[] = new Choice(choices: $schema['enum']);
        }

        if (isset($schema['type']) && 'array' === $schema['type']) {
            $assertions[] = new Type(type: 'array');
        }

        if (!$assertions) {
            return $parameter;
        }

        if (1 === \count($assertions)) {
            return $parameter->withConstraints($assertions[0]);
        }

        return $parameter->withConstraints($assertions);
    }

    private function addFilterValidation(HttpOperation $operation): Parameters
    {
        $parameters = new Parameters();
        $internalPriority = -1;

        foreach ($operation->getFilters() as $filter) {
            if (!$this->filterLocator->has($filter)) {
                continue;
            }

            $filter = $this->filterLocator->get($filter);
            foreach ($filter->getDescription($operation->getClass()) as $parameterName => $definition) {
                $key = $parameterName;
                $required = $definition['required'] ?? false;
                $schema = $definition['schema'] ?? null;

                $openApi = null;
                if (isset($definition['openapi']) && $definition['openapi'] instanceof OpenApiParameter) {
                    $openApi = $definition['openapi'];
                }

                // The query parameter validator forced this, lets maintain BC on filters
                if (true === $required && !$openApi) {
                    $openApi = new OpenApiParameter(name: $key, in: 'query', allowEmptyValue: false);
                }

                $parameters->add($key, $this->addSchemaValidation(
                    // we disable openapi and hydra on purpose as their docs comes from filters see the condition for addFilterValidation above
                    new QueryParameter(key: $key, property: $definition['property'] ?? null, priority: $internalPriority--, schema: $schema, openApi: false, hydra: false),
                    $schema,
                    $required,
                    $openApi
                ));
            }
        }

        return $parameters;
    }
}
