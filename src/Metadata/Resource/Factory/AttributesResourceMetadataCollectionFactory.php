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

use ApiPlatform\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\State\CreateProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * Creates a resource metadata from {@see ApiResource} annotations.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class AttributesResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use OperationDefaultsTrait;

    public function __construct(private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null, LoggerInterface $logger = null, array $defaults = [], private readonly bool $graphQlEnabled = false)
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

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException) {
            throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
        }

        if ($this->hasResourceAttributes($reflectionClass)) {
            foreach ($this->buildResourceOperations($reflectionClass->getAttributes(), $resourceClass) as $i => $resource) {
                $resourceMetadataCollection[] = $resource;
            }
        }

        return $resourceMetadataCollection;
    }

    /**
     * Builds resource operations to support:
     * Resource
     * Get
     * Post
     * Resource
     * Put
     * Get
     * In the future, we will be able to use nested attributes (https://wiki.php.net/rfc/new_in_initializers).
     *
     * @return ApiResource[]
     */
    private function buildResourceOperations(array $attributes, string $resourceClass): array
    {
        $shortName = (false !== $pos = strrpos($resourceClass, '\\')) ? substr($resourceClass, $pos + 1) : $resourceClass;
        $resources = [];
        $index = -1;
        $operationPriority = 0;

        foreach ($attributes as $attribute) {
            if (ApiResource::class === $attribute->getName()) {
                $resources[++$index] = $this->getResourceWithDefaults($resourceClass, $shortName, $attribute->newInstance());
                continue;
            }

            if (!is_subclass_of($attribute->getName(), HttpOperation::class) && !is_subclass_of($attribute->getName(), GraphQlOperation::class)) {
                continue;
            }

            $operationAttribute = $attribute->newInstance();

            if ($operationAttribute instanceof GraphQlOperation) {
                [$key, $operation] = $this->getOperationWithDefaults($resources[$index], $operationAttribute);
                $graphQlOperations = $resources[$index]->getGraphQlOperations();
                $graphQlOperations[$key] = $operation;
                $resources[$index] = $resources[$index]->withGraphQlOperations($graphQlOperations);
                continue;
            }

            if (-1 === $index || $this->hasSameOperation($resources[$index], $attribute->getName(), $operationAttribute)) {
                $resources[++$index] = $this->getResourceWithDefaults($resourceClass, $shortName, new ApiResource());
            }

            [$key, $operation] = $this->getOperationWithDefaults($resources[$index], $operationAttribute);
            $operation = $operation->withPriority(++$operationPriority);
            $operations = $resources[$index]->getOperations() ?? new Operations();
            $resources[$index] = $resources[$index]->withOperations($operations->add($key, $operation)->sort());
        }

        // Loop again and set default operations if none where found
        foreach ($resources as $index => $resource) {
            $operations = [];

            foreach ($resource->getOperations() ?? $this->getDefaultHttpOperations($resource) as $i => $operation) {
                [$key, $operation] = $this->getOperationWithDefaults($resource, $operation, $resource->getOperations() ? false : true);
                $operations[$key] = $operation;
            }

            $resources[$index] = $resources[$index]->withOperations(new Operations($operations));
            $graphQlOperations = $resource->getGraphQlOperations();

            if ([] === $graphQlOperations || !$this->graphQlEnabled) {
                continue;
            }

            if (null === $graphQlOperations) {
                // Add default graphql operations on the first resource
                if (0 === $index) {
                    $resources[$index] = $this->addDefaultGraphQlOperations($resources[$index]);
                }
                continue;
            }

            $graphQlOperationsWithDefaults = [];
            foreach ($graphQlOperations as $i => $operation) {
                [$key, $operation] = $this->getOperationWithDefaults($resource, $operation);
                $graphQlOperationsWithDefaults[$key] = $operation;
            }

            $resources[$index] = $resources[$index]->withGraphQlOperations($graphQlOperationsWithDefaults);
        }

        return $resources;
    }

    private function hasResourceAttributes(\ReflectionClass $reflectionClass): bool
    {
        foreach ($reflectionClass->getAttributes() as $attribute) {
            if (ApiResource::class === $attribute->getName() || is_subclass_of($attribute->getName(), HttpOperation::class) || is_subclass_of($attribute->getName(), GraphQlOperation::class)) {
                return true;
            }
        }

        return false;
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

    private function getDefaultHttpOperations($resource): iterable
    {
        $post = new Post();
        if ($resource->getUriTemplate() && !$resource->getProvider()) {
            $post = $post->withProvider(CreateProvider::class);
        }

        return [new Get(), new GetCollection(), $post, new Put(), new Patch(), new Delete()];
    }
}
