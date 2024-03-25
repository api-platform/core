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
use ApiPlatform\Metadata\HeaderParameterInterface;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\OpenApi;
use ApiPlatform\Serializer\Filter\FilterInterface as SerializerFilterInterface;
use Psr\Container\ContainerInterface;

/**
 * Prepares Parameters documentation by reading its filter details and declaring an OpenApi parameter.
 *
 * @experimental
 */
final class ParameterResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null, private readonly ?ContainerInterface $filterLocator = null)
    {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated?->create($resourceClass) ?? new ResourceMetadataCollection($resourceClass);

        foreach ($resourceMetadataCollection as $i => $resource) {
            $operations = $resource->getOperations();

            $internalPriority = -1;
            foreach ($operations as $operationName => $operation) {
                $parameters = [];
                foreach ($operation->getParameters() ?? [] as $key => $parameter) {
                    $parameter = $this->setDefaults($key, $parameter, $resourceClass);
                    $priority = $parameter->getPriority() ?? $internalPriority--;
                    $parameters[$key] = $parameter->withPriority($priority);
                }

                $operations->add($operationName, $operation->withParameters(new Parameters($parameters)));
            }

            $resourceMetadataCollection[$i] = $resource->withOperations($operations->sort());

            $internalPriority = -1;
            $graphQlOperations = $resource->getGraphQlOperations();
            foreach ($graphQlOperations ?? [] as $operationName => $operation) {
                $parameters = [];
                foreach ($operation->getParameters() ?? [] as $key => $parameter) {
                    $parameter = $this->setDefaults($key, $parameter, $resourceClass);
                    $priority = $parameter->getPriority() ?? $internalPriority--;
                    $parameters[$key] = $parameter->withPriority($priority);
                }

                $graphQlOperations[$operationName] = $operation->withParameters(new Parameters($parameters));
            }

            if ($graphQlOperations) {
                $resourceMetadataCollection[$i] = $resource->withGraphQlOperations($graphQlOperations);
            }
        }

        return $resourceMetadataCollection;
    }

    private function setDefaults(string $key, Parameter $parameter, string $resourceClass): Parameter
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

        if (null === $parameter->getOpenApi() && $openApi = $description[$key]['openapi'] ?? null) {
            if ($openApi instanceof OpenApi\Model\Parameter) {
                $parameter = $parameter->withOpenApi($openApi);
            }

            if (\is_array($openApi)) {
                $parameter->withOpenApi(new OpenApi\Model\Parameter(
                    $key,
                    $parameter instanceof HeaderParameterInterface ? 'header' : 'query',
                    $description[$key]['description'] ?? '',
                    $description[$key]['required'] ?? $openApi['required'] ?? false,
                    $openApi['deprecated'] ?? false,
                    $openApi['allowEmptyValue'] ?? true,
                    $schema,
                    $openApi['style'] ?? null,
                    $openApi['explode'] ?? ('array' === ($schema['type'] ?? null)),
                    $openApi['allowReserved'] ?? false,
                    $openApi['example'] ?? null,
                    isset(
                        $openApi['examples']
                    ) ? new \ArrayObject($openApi['examples']) : null
                ));
            }
        }

        return $parameter;
    }
}
