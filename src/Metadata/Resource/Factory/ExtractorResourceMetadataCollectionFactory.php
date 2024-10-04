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
use ApiPlatform\Metadata\Extractor\ResourceExtractorInterface;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Util\CamelCaseToSnakeCaseNameConverter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Creates a resource metadata from {@see Resource} extractors (XML, YAML).
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ExtractorResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use OperationDefaultsTrait;

    public function __construct(private readonly ResourceExtractorInterface $extractor, private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null, array $defaults = [], ?LoggerInterface $logger = null, private readonly bool $graphQlEnabled = false)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->defaults = $defaults;
        $this->camelCaseToSnakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();
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

        if (!(class_exists($resourceClass) || interface_exists($resourceClass)) || !$resources = $this->extractor->getResources()[$resourceClass] ?? false) {
            return $resourceMetadataCollection;
        }

        foreach ($this->buildResources($resources, $resourceClass) as $resource) {
            foreach ($this->defaults['attributes'] ?? [] as $key => $value) {
                if (method_exists($resource, 'get'.ucfirst($key)) && !$resource->{'get'.ucfirst($key)}()) {
                    $resource = $resource->{'with'.ucfirst($key)}($value);
                }
            }

            $resourceMetadataCollection[] = $resource;
        }

        return $resourceMetadataCollection;
    }

    /**
     * Builds resources to support:.
     *
     * @return ApiResource[]
     */
    private function buildResources(array $nodes, string $resourceClass): array
    {
        $shortName = (false !== $pos = strrpos($resourceClass, '\\')) ? substr($resourceClass, $pos + 1) : $resourceClass;
        $resources = [];
        foreach ($nodes as $node) {
            $resource = (new ApiResource())
                ->withShortName($shortName)
                ->withClass($resourceClass);
            foreach ($node as $key => $value) {
                $methodName = 'with'.ucfirst($key);
                if ('operations' !== $key && null !== $value && method_exists($resource, $methodName)) {
                    $resource = $resource->{$methodName}($value);
                }
            }

            if ($this->graphQlEnabled) {
                $resource = $this->addGraphQlOperations($node['graphQlOperations'] ?? null, $resource);
            }

            $resources[] = $this->addOperations($node['operations'] ?? null, $resource);
        }

        return $resources;
    }

    private function addOperations(?array $data, ApiResource $resource): ApiResource
    {
        $operations = [];

        if (null === $data) {
            foreach ($this->getDefaultHttpOperations($resource) as $operation) {
                [$key, $operation] = $this->getOperationWithDefaults($resource, $operation);
                $operations[$key] = $operation;
            }

            return $resource->withOperations(new Operations($operations));
        }

        foreach ($data as $attributes) {
            if (!class_exists($attributes['class'])) {
                throw new \InvalidArgumentException(\sprintf('Operation "%s" does not exist.', $attributes['class']));
            }

            /** @var HttpOperation $operation */
            $operation = (new $attributes['class']())->withShortName($resource->getShortName());
            unset($attributes['class']);
            foreach ($attributes as $key => $value) {
                if (null === $value) {
                    continue;
                }

                $camelCaseKey = $this->camelCaseToSnakeCaseNameConverter->denormalize($key);
                $methodName = 'with'.ucfirst($camelCaseKey);

                if (method_exists($operation, $methodName)) {
                    $operation = $operation->{$methodName}($value);
                    continue;
                }

                $operation = $operation->withExtraProperties(array_merge($operation->getExtraProperties(), [$key => $value]));
            }

            [$key, $operation] = $this->getOperationWithDefaults($resource, $operation);
            $operations[$key] = $operation;
        }

        return $resource->withOperations(new Operations($operations));
    }

    private function addGraphQlOperations(?array $data, ApiResource $resource): ApiResource
    {
        $operations = [];

        if (null === $data) {
            return $this->addDefaultGraphQlOperations($resource);
        }

        foreach ($data as $attributes) {
            if (!class_exists($attributes['class'])) {
                throw new \InvalidArgumentException(\sprintf('Operation "%s" does not exist.', $attributes['class']));
            }

            /** @var GraphQlOperation $operation */
            $operation = (new $attributes['class']())->withShortName($resource->getShortName());
            unset($attributes['class']);
            foreach ($attributes as $key => $value) {
                if (null === $value) {
                    continue;
                }

                $camelCaseKey = $this->camelCaseToSnakeCaseNameConverter->denormalize($key);
                $methodName = 'with'.ucfirst($camelCaseKey);

                if (method_exists($operation, $methodName)) {
                    $operation = $operation->{$methodName}($value);
                    continue;
                }

                $operation = $operation->withExtraProperties(array_merge($operation->getExtraProperties(), [$key => $value]));
            }

            [$key, $operation] = $this->getOperationWithDefaults($resource, $operation);
            $operations[$key] = $operation;
        }

        $resource = $resource->withGraphQlOperations($operations);

        return $this->completeGraphQlOperations($resource);
    }
}
