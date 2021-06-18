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

namespace ApiPlatform\Core\Metadata\ResourceCollection\Factory;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource;

/**
 * Creates a resource metadata from {@see Resource} annotations.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
final class AttributeResourceCollectionMetadataFactory implements ResourceCollectionMetadataFactoryInterface
{
    private $defaults;
    private $decorated;

    public function __construct(array $defaults = [], ResourceCollectionMetadataFactoryInterface $decorated = null)
    {
        $this->defaults = $defaults;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceCollection
    {
        $resourceMetadataCollection = [];

        if ($this->decorated) {
            try {
                $resourceMetadataCollection = $this->decorated->create($resourceClass);
            } catch (ResourceClassNotFoundException $resourceNotFoundException) {
            }
        }

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException $reflectionException) {
            throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
        }

        if (\PHP_VERSION_ID >= 80000 && $reflectionClass->getAttributes(Resource::class)) {
            foreach ($this->buildResourceOperations($reflectionClass->getAttributes(), $resourceClass) as $i => $resource) {
                foreach ($this->defaults as $key => $value) {
                    if (!$resource->{'get'.ucfirst($key)}()) {
                        $resource = $resource->{'with'.ucfirst($key)}($value);
                    }
                }

                $resourceMetadataCollection[$i] = $resource;
            }
        }

        if (!$resourceMetadataCollection) {
            throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
        }

        return new ResourceCollection($resourceMetadataCollection);
    }

    /**
     * Builds resource operations to support:.
     *
     * Resource
     * Get
     * Post
     * Resource
     * Put
     * Get
     *
     * In the future, we will be able to use nested attributes (https://wiki.php.net/rfc/new_in_initializers)
     *
     * @return resource[]
     */
    private function buildResourceOperations(array $attributes, string $resourceClass): array
    {
        $shortName = (false !== $pos = strrpos($resourceClass, '\\')) ? substr($resourceClass, $pos + 1) : $resourceClass;
        $resources = [];
        $index = -1;
        foreach ($attributes as $attribute) {
            if (Resource::class === $attribute->getName()) {
                $resource = $attribute->newInstance();
                $resource = $resource->withShortName($shortName);
                $resource = $resource->withClass($resourceClass);
                $resources[++$index] = $resource;
                continue;
            }

            // Create default operations
            if (!is_subclass_of($attribute->getName(), Operation::class)) {
                continue;
            }

            [$key, $operation] = $this->getOperationWithDefaults($resources[$index], $attribute->newInstance());
            $operations = iterator_to_array($resources[$index]->getOperations());
            $operations[$key] = $operation;
            $resources[$index] = $resources[$index]->withOperations($operations);
        }

        // Loop again and set default operations if none where found
        foreach ($resources as $index => $resource) {
            if (\count($resource->getOperations())) {
                continue;
            }

            foreach ([new Get(), new GetCollection(), new Post(), new Put(), new Patch(), new Delete()] as $operation) {
                [$key, $operation] = $this->getOperationWithDefaults($resources[$index], $operation);
                $operations = iterator_to_array($resources[$index]->getOperations());
                $operations[$key] = $operation;
                $resources[$index] = $resources[$index]->withOperations($operations);
            }
        }

        return $resources;
    }

    private function getOperationWithDefaults(Resource $resource, Operation $operation): array
    {
        // @phpstan-ignore-next-line
        foreach ($resource as $property => $value) {
            if ('operations' === $property) {
                continue;
            }

            if ($operation->{'get'.ucfirst($property)}() || !$value) {
                continue;
            }

            $operation = $operation->{'with'.ucfirst($property)}($value);
        }

        $key = sprintf('_api_%s_%s%s', $operation->getUriTemplate() ?: $operation->getShortName(), strtolower($operation->getMethod()), $operation instanceof GetCollection ? '_collection' : '');

        return [$key, $operation];
    }
}
