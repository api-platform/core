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
    private readonly LoggerInterface $logger;
    private readonly CamelCaseToSnakeCaseNameConverter $camelCaseToSnakeCaseNameConverter;

    public function __construct(private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null, LoggerInterface $logger = null, private readonly array $defaults = [], private readonly bool $graphQlEnabled = false)
    {
        $this->logger = $logger ?? new NullLogger();
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

    private function getOperationWithDefaults(ApiResource $resource, Operation $operation, bool $generated = false): array
    {
        // Inherit from resource defaults
        foreach (get_class_methods($resource) as $methodName) {
            if (!str_starts_with($methodName, 'get')) {
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

        $operation = $operation->withExtraProperties(array_merge(
            $resource->getExtraProperties(),
            $operation->getExtraProperties(),
            $generated ? ['generated_operation' => true] : []
        ));

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

        if (!$operation instanceof HttpOperation) {
            throw new RuntimeException(sprintf('Operation should be an instance of "%s"', HttpOperation::class));
        }

        if ($operation->getRouteName()) {
            /** @var HttpOperation $operation */
            $operation = $operation->withName($operation->getRouteName());
        }

        // Check for name conflict
        if ($operation->getName()) {
            if (null !== $resource->getOperations() && !$resource->getOperations()->has($operation->getName())) {
                return [$operation->getName(), $operation];
            }

            $this->logger->warning(sprintf('The operation "%s" already exists on the resource "%s", pick a different name or leave it empty. In the meantime we will generate a unique name.', $operation->getName(), $resource->getClass()));
            /** @var HttpOperation $operation */
            $operation = $operation->withName('');
        }

        return [
            sprintf(
                '_api_%s_%s%s',
                $operation->getUriTemplate() ?: $operation->getShortName(),
                strtolower($operation->getMethod() ?? HttpOperation::METHOD_GET),
                $operation instanceof CollectionOperationInterface ? '_collection' : '',
            ),
            $operation,
        ];
    }

    private function addGlobalDefaults(ApiResource|HttpOperation|GraphQlOperation $operation): ApiResource|HttpOperation|GraphQlOperation
    {
        $extraProperties = [];
        foreach ($this->defaults as $key => $value) {
            $upperKey = ucfirst($this->camelCaseToSnakeCaseNameConverter->denormalize($key));
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

        return $operation->withExtraProperties(array_merge($extraProperties, $operation->getExtraProperties()));
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

    private function getDefaultHttpOperations($resource): iterable
    {
        $post = new Post();
        if ($resource->getUriTemplate() && !$resource->getProvider()) {
            $post = $post->withProvider(CreateProvider::class);
        }

        return [new Get(), new GetCollection(), $post, new Put(), new Patch(), new Delete()];
    }
}
