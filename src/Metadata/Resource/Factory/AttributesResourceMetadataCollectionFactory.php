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
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\DeprecationMetadataTrait;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Creates a resource metadata from {@see ApiResource} annotations.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class AttributesResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use DeprecationMetadataTrait;

    private $defaults;
    private $decorated;
    private $logger;
    private $graphQlEnabled;

    public function __construct(ResourceMetadataCollectionFactoryInterface $decorated = null, LoggerInterface $logger = null, array $defaults = [], bool $graphQlEnabled = false)
    {
        $this->defaults = $defaults;
        $this->decorated = $decorated;
        $this->logger = $logger ?? new NullLogger();
        $this->graphQlEnabled = $graphQlEnabled;
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
        } catch (\ReflectionException $reflectionException) {
            throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
        }

        if (\PHP_VERSION_ID >= 80000 && $this->hasResourceAttributes($reflectionClass)) {
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
            foreach ($resource->getOperations() ?? [new Get(), new GetCollection(), new Post(), new Put(), new Patch(), new Delete()] as $i => $operation) {
                [$key, $operation] = $this->getOperationWithDefaults($resource, $operation);
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

    private function getOperationWithDefaults(ApiResource $resource, Operation $operation): array
    {
        // Inherit from resource defaults
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

            // TODO: remove in 3.0
            if ($operation instanceof HttpOperation && 'getUriVariables' === $methodName && !$operation->getUriTemplate() && $operation instanceof CollectionOperationInterface && !$operation->getUriVariables()) {
                trigger_deprecation('api-platform', '2.7', 'Identifiers are declared on the default #[ApiResource] but you did not specify identifiers on the collection operation. In 3.0 the collection operations can have identifiers, you should specify identifiers on the operation not on the resource to avoid unwanted behavior.');
                continue;
            }

            $operation = $operation->{'with'.substr($methodName, 3)}($value);
        }

        $operation = $operation->withExtraProperties(array_merge($resource->getExtraProperties(), $operation->getExtraProperties()));

        // Add global defaults attributes to the operation
        $operation = $this->addGlobalDefaults($operation);

        if ($operation instanceof GraphQlOperation) {
            if (!$operation->getName()) {
                throw new RuntimeException('No GraphQL operation name.');
            }

            if ($operation instanceof Mutation) {
                $operation = $operation->withDescription(ucfirst("{$operation->getName()}s a {$resource->getShortName()}."));
            }

            return [$operation->getName(), $operation];
        }

        if ($operation instanceof HttpOperation && $operation->getRouteName()) {
            $operation = $operation->withName($operation->getRouteName());
        }

        // Check for name conflict
        if ($operation->getName() && null !== ($operations = $resource->getOperations())) {
            if (!$operations->has($operation->getName())) {
                return [$operation->getName(), $operation];
            }

            $this->logger->warning(sprintf('The operation "%s" already exists on the resource "%s", pick a different name or leave it empty. In the meantime we will generate a unique name.', $operation->getName(), $resource->getClass()));
            $operation = $operation->withName('');
        }

        return [
            sprintf('_api_%s_%s%s', $operation->getUriTemplate() ?: $operation->getShortName(), strtolower($operation->getMethod() ?? HttpOperation::METHOD_GET), $operation instanceof CollectionOperationInterface ? '_collection' : ''),
            $operation,
        ];
    }

    /**
     * @param ApiResource|HttpOperation|GraphQlOperation $operation
     */
    private function addGlobalDefaults($operation)
    {
        $extraProperties = $operation->getExtraProperties();
        foreach ($this->defaults as $key => $value) {
            [$newKey, $value] = $this->getKeyValue($key, $value);
            $upperKey = ucfirst($newKey);
            $getter = 'get'.$upperKey;

            if (!method_exists($operation, $getter)) {
                if (!isset($extraProperties[$key])) {
                    $extraProperties[$key] = $value;
                }
            } else {
                $currentValue = $operation->{$getter}();

                if (\is_array($currentValue) && $currentValue) {
                    $operation = $operation->{'with'.$upperKey}(array_merge($value, $currentValue));
                }

                if (null !== $currentValue) {
                    continue;
                }

                $operation = $operation->{'with'.$upperKey}($value);
            }
        }

        return $operation->withExtraProperties($extraProperties);
    }

    private function getResourceWithDefaults(string $resourceClass, string $shortName, ApiResource $resource): ApiResource
    {
        $resource = $resource
            ->withShortName($resource->getShortName() ?? $shortName)
            ->withClass($resourceClass);

        return $this->addGlobalDefaults($resource);
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

    private function addDefaultGraphQlOperations(ApiResource $resource): ApiResource
    {
        $graphQlOperations = [];
        foreach ([new QueryCollection(), new Query(), (new Mutation())->withName('update'), (new DeleteMutation())->withName('delete'), (new Mutation())->withName('create')] as $i => $operation) {
            [$key, $operation] = $this->getOperationWithDefaults($resource, $operation);
            $graphQlOperations[$key] = $operation;
        }

        if ($resource->getMercure()) {
            [$key, $operation] = $this->getOperationWithDefaults($resource, (new Subscription())->withDescription("Subscribes to the update event of a {$operation->getShortName()}."));
            $graphQlOperations[$key] = $operation;
        }

        return $resource->withGraphQlOperations($graphQlOperations);
    }
}
