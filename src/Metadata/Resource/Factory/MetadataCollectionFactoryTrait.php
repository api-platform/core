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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Metadata;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\Util\CamelCaseToSnakeCaseNameConverter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @internal
 *
 * This trait shares the common logic between attributes and Laravel concerns factories
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Kévin Dunglas <kevin@dunglas.dev>
 */
trait MetadataCollectionFactoryTrait
{
    use OperationDefaultsTrait;

    public function __construct(private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null, ?LoggerInterface $logger = null, array $defaults = [], private readonly bool $graphQlEnabled = false)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->defaults = $defaults;
        $this->camelCaseToSnakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();
    }

    private function isResourceMetadata(string $name): bool
    {
        return is_a($name, ApiResource::class, true) || is_subclass_of($name, HttpOperation::class) || is_subclass_of($name, GraphQlOperation::class) || is_a($name, Parameter::class, true);
    }

    /**
     * Builds resource operations to support:
     * Resource
     * Get
     * Post
     * Resource
     * Get
     * In the future, we will be able to use nested attributes (https://wiki.php.net/rfc/new_in_initializers).
     *
     * @param array<Metadata|Parameter> $metadataCollection
     *
     * @return ApiResource[]
     */
    private function buildResourceOperations(array $metadataCollection, string $resourceClass, array $resources = []): array
    {
        $shortName = (false !== $pos = strrpos($resourceClass, '\\')) ? substr($resourceClass, $pos + 1) : $resourceClass;
        $index = -1;
        $operationPriority = 0;
        $hasApiResource = false;
        $globalParameters = new Parameters();

        foreach ($metadataCollection as $metadata) {
            if ($metadata instanceof Parameter) {
                if (!$k = $metadata->getKey()) {
                    throw new RuntimeException('Parameter "key" is mandatory when used on a class.');
                }
                $globalParameters->add($k, $metadata);
                continue;
            }

            if ($metadata instanceof ApiResource) {
                $hasApiResource = true;
                $resource = $this->getResourceWithDefaults($resourceClass, $shortName, $metadata);
                $operations = [];
                foreach ($resource->getOperations() ?? new Operations() as $operation) {
                    [$key, $operation] = $this->getOperationWithDefaults($resource, $operation);
                    $operations[$key] = $operation;
                }
                if ($operations) {
                    $resource = $resource->withOperations(new Operations($operations));
                }
                $resources[++$index] = $resource;
                continue;
            }

            if (!is_subclass_of($metadata, HttpOperation::class) && !is_subclass_of($metadata, GraphQlOperation::class)) {
                continue;
            }

            if ($metadata instanceof GraphQlOperation) {
                [$key, $operation] = $this->getOperationWithDefaults($resources[$index], $metadata);
                $graphQlOperations = $resources[$index]->getGraphQlOperations();
                $graphQlOperations[$key] = $operation;
                $resources[$index] = $resources[$index]->withGraphQlOperations($graphQlOperations);
                continue;
            }

            if (-1 === $index || $this->hasSameOperation($resources[$index], $metadata::class, $metadata)) {
                $resources[++$index] = $this->getResourceWithDefaults($resourceClass, $shortName, new ApiResource());
            }

            [$key, $operation] = $this->getOperationWithDefaults($resources[$index], $metadata);
            if (null === $operation->getPriority()) {
                $operation = $operation->withPriority(++$operationPriority);
            }
            $operations = $resources[$index]->getOperations() ?? new Operations();
            $resources[$index] = $resources[$index]->withOperations($operations->add($key, $operation));
        }

        // Loop again and set default operations if none where found
        foreach ($resources as $index => $resource) {
            if (\count($globalParameters) > 0) {
                $resources[$index] = $resource = $this->mergeOperationParameters($resource, $globalParameters);
            }

            if (null === $resource->getOperations()) {
                $operations = [];
                foreach ($this->getDefaultHttpOperations($resource) as $operation) {
                    [$key, $operation] = $this->getOperationWithDefaults($resource, $operation, true);
                    $operations[$key] = $operation;
                }
                $resources[$index] = $resource = $resource->withOperations(new Operations($operations));
            }

            if ($parameters = $resource->getParameters()) {
                $operations = [];
                foreach ($resource->getOperations() ?? [] as $i => $operation) {
                    $operations[$i] = $this->mergeOperationParameters($operation, $parameters);
                }
                $resources[$index] = $resource = $resource->withOperations(new Operations($operations)); // @phpstan-ignore-line
            }

            if (!$this->graphQlEnabled) {
                continue;
            }

            $graphQlOperations = $resource->getGraphQlOperations();
            if (null === $graphQlOperations) {
                if (!$hasApiResource) {
                    $resources[$index] = $resources[$index]->withGraphQlOperations([]);
                    continue;
                }

                // Add default GraphQL operations on the first resource
                if (0 === $index) {
                    $resources[$index] = $this->addDefaultGraphQlOperations($resources[$index]);
                }
                continue;
            }

            $resources[$index] = $this->completeGraphQlOperations($resources[$index]);
            $graphQlOperations = $resources[$index]->getGraphQlOperations();

            $graphQlOperationsWithDefaults = [];
            foreach ($graphQlOperations as $operation) {
                [$key, $operation] = $this->getOperationWithDefaults($resource, $operation);
                if ($parameters) {
                    $operation = $this->mergeOperationParameters($operation, $parameters);
                }

                $graphQlOperationsWithDefaults[$key] = $operation;
            }

            $resources[$index] = $resources[$index]->withGraphQlOperations($graphQlOperationsWithDefaults);
        }

        return $resources;
    }

    /**
     * Does the resource already have an operation of the $operationClass type?
     * Useful to determine if we need to create a new ApiResource when the class has only operation attributes, for example:.
     *
     * #[Get]
     * #[Get(uriTemplate: '/alternate')]
     * class Example {}
     */
    private function hasSameOperation(ApiResource $resource, string $operationClass, HttpOperation $operation): bool
    {
        foreach ($resource->getOperations() ?? [] as $o) {
            if ($o instanceof $operationClass && $operation->getUriTemplate() === $o->getUriTemplate() && $operation->getName() === $o->getName() && $operation->getRouteName() === $o->getRouteName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @template T of Metadata
     *
     * @param T $resource
     *
     * @return T
     */
    private function mergeOperationParameters(Metadata $resource, Parameters $globalParameters): Metadata
    {
        $parameters = $resource->getParameters() ?? new Parameters();
        foreach ($globalParameters as $parameterName => $parameter) {
            if ($key = $parameter->getKey()) {
                $parameterName = $key;
            }

            if (!$parameters->has($parameterName, $parameter::class)) {
                $parameters->add($parameterName, $parameter);
            }
        }

        return $resource->withParameters($parameters);
    }
}
