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

use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Adds default parameters from the global configuration to all resources and operations.
 *
 * @author Maxence Castel <maxence.castel59@gmail.com>
 */
final class DefaultParametersResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    /**
     * @param array<string, array<string, mixed>> $defaultParameters Array where keys are parameter class names and values are their configuration
     */
    public function __construct(
        private readonly array $defaultParameters = [],
        private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null,
    ) {
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

        if (empty($this->defaultParameters)) {
            return $resourceMetadataCollection;
        }

        $defaultParams = $this->buildDefaultParameters();

        foreach ($resourceMetadataCollection as $i => $resource) {
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

            $resourceMetadataCollection[$i] = $resource;
        }

        return $resourceMetadataCollection;
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
