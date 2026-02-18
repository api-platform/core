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

namespace ApiPlatform\Validator\Metadata\Resource\Factory;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use ApiPlatform\Validator\Util\ParameterValidationConstraints;
use Psr\Container\ContainerInterface;

final class ParameterValidationResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use ParameterValidationConstraints;

    public function __construct(
        private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null,
        private readonly ?ContainerInterface $filterLocator = null,
        private readonly array $defaultParameters = [],
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated?->create($resourceClass) ?? new ResourceMetadataCollection($resourceClass);

        $defaultParams = $this->buildDefaultParameters();

        foreach ($resourceMetadataCollection as $i => $resource) {
            $resource = $this->applyDefaults($resource, $defaultParams);

            $operations = $resource->getOperations();

            foreach ($operations as $operationName => $operation) {
                $parameters = $operation->getParameters() ?? new Parameters();
                foreach ($parameters as $key => $parameter) {
                    $parameters->add($key, $this->addSchemaValidation($parameter));
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

            foreach ($graphQlOperations as $operationName => $operation) {
                $parameters = $operation->getParameters() ?? new Parameters();
                foreach ($operation->getParameters() ?? [] as $key => $parameter) {
                    $parameters->add($key, $this->addSchemaValidation($parameter));
                }

                if (\count($parameters) > 0) {
                    $graphQlOperations[$operationName] = $operation->withParameters($parameters);
                }
            }

            $resourceMetadataCollection[$i] = $resource->withGraphQlOperations($graphQlOperations);
        }

        return $resourceMetadataCollection;
    }

    private function addSchemaValidation(Parameter $parameter, ?array $schema = null, ?bool $required = null, ?OpenApiParameter $openApi = null): Parameter
    {
        if (null !== $parameter->getConstraints()) {
            return $parameter;
        }

        $assertions = $this->getParameterValidationConstraints($parameter, $schema, $required, $openApi);
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

    /**
     * Builds Parameter objects from the default configuration array.
     *
     * @return array<string, Parameter> Array of Parameter objects indexed by their key
     */
    private function buildDefaultParameters(): array
    {
        $parameters = [];

        foreach ($this->defaultParameters as $parameterClass => $config) {
            if (!is_subclass_of($parameterClass, Parameter::class)) {
                continue;
            }

            $key = $config['key'] ?? null;
            if (!$key) {
                $key = (new \ReflectionClass($parameterClass))->getShortName();
            }

            $identifier = $key;

            $parameter = $this->createParameterFromConfig($parameterClass, $config);
            $parameters[$identifier] = $parameter;
        }

        return $parameters;
    }

    /**
     * Creates a Parameter instance from configuration.
     *
     * @param class-string<Parameter> $parameterClass The parameter class name
     * @param array<string, mixed>    $config         The configuration array
     *
     * @return Parameter The created parameter instance
     */
    private function createParameterFromConfig(string $parameterClass, array $config): Parameter
    {
        return new $parameterClass(
            key: $config['key'] ?? null,
            schema: $config['schema'] ?? null,
            openApi: null,
            provider: null,
            filter: $config['filter'] ?? null,
            property: $config['property'] ?? null,
            description: $config['description'] ?? null,
            properties: null,
            required: $config['required'] ?? false,
            priority: $config['priority'] ?? null,
            hydra: $config['hydra'] ?? null,
            constraints: $config['constraints'] ?? null,
            security: $config['security'] ?? null,
            securityMessage: $config['security_message'] ?? null,
            extraProperties: $config['extra_properties'] ?? [],
            filterContext: null,
            nativeType: null,
            castToArray: null,
            castToNativeType: null,
            castFn: null,
            default: $config['default'] ?? null,
            filterClass: $config['filter_class'] ?? null,
        );
    }

    /**
     * Applies default parameters to the resource.
     *
     * @param array<string, Parameter> $defaultParams The default parameters to apply
     */
    private function applyDefaults(ApiResource $resource, array $defaultParams): ApiResource
    {
        $resourceParameters = $resource->getParameters() ?? new Parameters();
        $mergedResourceParameters = $this->mergeParameters($resourceParameters, $defaultParams);
        $resource = $resource->withParameters($mergedResourceParameters);

        foreach ($operations = $resource->getOperations() ?? [] as $operationName => $operation) {
            $operationParameters = $operation->getParameters() ?? new Parameters();
            $mergedOperationParameters = $this->mergeParameters($operationParameters, $defaultParams);
            $operations->add((string) $operationName, $operation->withParameters($mergedOperationParameters));
        }

        if ($operations) {
            $resource = $resource->withOperations($operations);
        }

        foreach ($graphQlOperations = $resource->getGraphQlOperations() ?? [] as $operationName => $operation) {
            $operationParameters = $operation->getParameters() ?? new Parameters();
            $mergedOperationParameters = $this->mergeParameters($operationParameters, $defaultParams);
            $graphQlOperations[$operationName] = $operation->withParameters($mergedOperationParameters);
        }

        if ($graphQlOperations) {
            $resource = $resource->withGraphQlOperations($graphQlOperations);
        }

        return $resource;
    }

    /**
     * Merges default parameters with operation-specific parameters.
     *
     * @param Parameters               $operationParameters The parameters already defined on the operation
     * @param array<string, Parameter> $defaultParams       The default parameters to merge
     *
     * @return Parameters The merged parameters
     */
    private function mergeParameters(Parameters $operationParameters, array $defaultParams): Parameters
    {
        $merged = new Parameters($defaultParams);

        foreach ($operationParameters as $key => $param) {
            $merged->add($key, $param);
        }

        return $merged;
    }
}
