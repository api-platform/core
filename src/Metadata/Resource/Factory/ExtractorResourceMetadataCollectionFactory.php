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
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Extractor\ResourceExtractorInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\DeprecationMetadataTrait;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Creates a resource metadata from {@see Resource} extractors (XML, YAML).
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ExtractorResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use DeprecationMetadataTrait;
    private $extractor;
    private $decorated;
    private $defaults;

    public function __construct(ResourceExtractorInterface $extractor, ResourceMetadataCollectionFactoryInterface $decorated = null, array $defaults = [])
    {
        $this->extractor = $extractor;
        $this->decorated = $decorated;
        $this->defaults = $defaults;
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

        foreach ($this->buildResources($resources, $resourceClass) as $i => $resource) {
            foreach ($this->defaults['attributes'] ?? [] as $key => $value) {
                if (method_exists($resource, 'get'.ucfirst($key)) && !$resource->{'get'.ucfirst($key)}()) {
                    $resource = $resource->{'with'.ucfirst($key)}($value);
                }
            }

            $resourceMetadataCollection[$i] = $resource;
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

            if (isset($node['graphQlOperations'])) {
                $resource = $resource->withGraphQlOperations($this->buildGraphQlOperations($node['graphQlOperations'], $resource));
            }

            $resources[] = $resource->withOperations(new Operations($this->buildOperations($node['operations'] ?? null, $resource)));
        }

        return $resources;
    }

    private function buildOperations(?array $data, ApiResource $resource): array
    {
        $operations = [];

        if (null === $data) {
            foreach ([new Get(), new GetCollection(), new Post(), new Put(), new Patch(), new Delete()] as $operation) {
                $operationName = sprintf('_api_%s_%s%s', $resource->getShortName(), strtolower($operation->getMethod()), $operation instanceof CollectionOperationInterface ? '_collection' : '');
                $operations[$operationName] = $this->getOperationWithDefaults($resource, $operation)->withName($operationName);
            }

            return $operations;
        }

        foreach ($data as $attributes) {
            if (!class_exists($attributes['class'])) {
                throw new \InvalidArgumentException(sprintf('Operation "%s" does not exist.', $attributes['class']));
            }

            /** @var HttpOperation $operation */
            $operation = (new $attributes['class']())->withShortName($resource->getShortName());
            unset($attributes['class']);
            foreach ($attributes as $key => $value) {
                if (null === $value) {
                    continue;
                }

                [$camelCaseKey, $value] = $this->getKeyValue($key, $value);
                $methodName = 'with'.ucfirst($camelCaseKey);

                if (method_exists($operation, $methodName)) {
                    $operation = $operation->{$methodName}($value);
                    continue;
                }

                $operation = $operation->withExtraProperties(array_merge($operation->getExtraProperties(), [$key => $value]));
            }

            if (empty($attributes['name'])) {
                $attributes['name'] = sprintf('_api_%s_%s%s', $operation->getUriTemplate() ?: $operation->getShortName(), strtolower($operation->getMethod()), $operation instanceof CollectionOperationInterface ? '_collection' : '');
            }
            $operations[$attributes['name']] = $this->getOperationWithDefaults($resource, $operation)->withName($attributes['name']);
        }

        return $operations;
    }

    private function buildGraphQlOperations(?array $data, ApiResource $resource): array
    {
        $operations = [];

        foreach ($data as $attributes) {
            /** @var HttpOperation $operation */
            $operation = (new $attributes['graphql_operation_class']())->withShortName($resource->getShortName());
            unset($attributes['graphql_operation_class']);

            foreach ($attributes as $key => $value) {
                if (null === $value) {
                    continue;
                }

                [$camelCaseKey, $value] = $this->getKeyValue($key, $value);
                $methodName = 'with'.ucfirst($camelCaseKey);

                if (method_exists($operation, $methodName)) {
                    $operation = $operation->{$methodName}($value);
                    continue;
                }

                $operation = $operation->withExtraProperties(array_merge($operation->getExtraProperties(), [$key => $value]));
            }

            $operations[] = $operation;
        }

        return $operations;
    }

    private function getOperationWithDefaults(ApiResource $resource, HttpOperation $operation): HttpOperation
    {
        foreach (($this->defaults['attributes'] ?? []) as $key => $value) {
            [$key, $value] = $this->getKeyValue($key, $value);
            if (null === $operation->{'get'.ucfirst($key)}()) {
                $operation = $operation->{'with'.ucfirst($key)}($value);
            }
        }

        foreach (get_class_methods($resource) as $methodName) {
            if (0 !== strpos($methodName, 'get')) {
                continue;
            }

            if (!method_exists($operation, $methodName) || null !== $operation->{$methodName}()) {
                continue;
            }

            if (null === ($value = $resource->{$methodName}())) {
                continue;
            }

            $operation = $operation->{'with'.substr($methodName, 3)}($value);
        }

        return $operation;
    }
}
