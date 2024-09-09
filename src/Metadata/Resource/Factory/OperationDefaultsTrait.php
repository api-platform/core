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
use ApiPlatform\Metadata\Exception\RuntimeException;
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
use ApiPlatform\Metadata\Util\CamelCaseToSnakeCaseNameConverter;
use ApiPlatform\State\CreateProvider;
use Psr\Log\LoggerInterface;

trait OperationDefaultsTrait
{
    private CamelCaseToSnakeCaseNameConverter $camelCaseToSnakeCaseNameConverter;
    private array $defaults = [];
    private LoggerInterface $logger;

    private function addGlobalDefaults(ApiResource|Operation $operation): ApiResource|Operation
    {
        $extraProperties = $this->defaults['extra_properties'] ?? [];

        foreach ($this->defaults as $key => $value) {
            if ('operations' === $key) {
                continue;
            }

            $upperKey = ucfirst($this->camelCaseToSnakeCaseNameConverter->denormalize($key));
            $getter = 'get'.$upperKey;

            if (!method_exists($operation, $getter)) {
                if (!isset($extraProperties[$key])) {
                    $extraProperties[$key] = $value;
                }

                continue;
            }

            $currentValue = $operation->{$getter}();

            if (\is_array($currentValue) && $currentValue) {
                $operation = $operation->{'with'.$upperKey}(array_merge($value, $currentValue));
            }

            if (null !== $currentValue || null === $value) {
                continue;
            }

            $operation = $operation->{'with'.$upperKey}($value);
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

    private function getDefaultHttpOperations($resource): iterable
    {
        if (enum_exists($resource->getClass())) {
            return new Operations([new GetCollection(paginationEnabled: false), new Get()]);
        }

        if (($defaultOperations = $this->defaults['operations'] ?? null) && null === $resource->getOperations()) {
            $operations = [];

            foreach ($defaultOperations as $defaultOperation) {
                $operation = new $defaultOperation();

                if ($operation instanceof Post && $resource->getUriTemplate() && !$resource->getProvider()) {
                    $operation = $operation->withProvider(CreateProvider::class);
                }

                $operations[] = $operation;
            }

            return new Operations($operations);
        }

        $post = new Post();
        if ($resource->getUriTemplate() && !$resource->getProvider()) {
            $post = $post->withProvider(CreateProvider::class);
        }

        return [new Get(), new GetCollection(), $post, new Patch(), new Delete()];
    }

    private function addDefaultGraphQlOperations(ApiResource $resource): ApiResource
    {
        $operations = enum_exists($resource->getClass()) ? [new QueryCollection(paginationEnabled: false), new Query()] : [new QueryCollection(), new Query(), (new Mutation())->withName('update'), (new DeleteMutation())->withName('delete'), (new Mutation())->withName('create')];
        $graphQlOperations = [];
        foreach ($operations as $operation) {
            [$key, $operation] = $this->getOperationWithDefaults($resource, $operation);
            $graphQlOperations[$key] = $operation;
        }

        if ($resource->getMercure()) {
            [$key, $operation] = $this->getOperationWithDefaults($resource, (new Subscription())->withDescription("Subscribes to the update event of a {$operation->getShortName()}."));
            $graphQlOperations[$key] = $operation;
        }

        return $resource->withGraphQlOperations($graphQlOperations);
    }

    /**
     * Adds nested query operations if there are no existing query ones on the resource.
     * They are needed when the resource is queried inside a root query, using a relation.
     * Since the nested argument is used, root queries will not be generated for these operations.
     */
    private function completeGraphQlOperations(ApiResource $resource): ApiResource
    {
        $graphQlOperations = $resource->getGraphQlOperations();

        $hasQueryOperation = false;
        $hasQueryCollectionOperation = false;
        foreach ($graphQlOperations as $operation) {
            if ($operation instanceof Query && !$operation instanceof QueryCollection) {
                $hasQueryOperation = true;
            }
            if ($operation instanceof QueryCollection) {
                $hasQueryCollectionOperation = true;
            }
        }

        if (!$hasQueryOperation) {
            $queryOperation = (new Query())->withNested(true);
            $graphQlOperations[$queryOperation->getName()] = $queryOperation;
        }
        if (!$hasQueryCollectionOperation) {
            $queryCollectionOperation = (new QueryCollection())->withNested(true);
            $graphQlOperations[$queryCollectionOperation->getName()] = $queryCollectionOperation;
        }

        return $resource->withGraphQlOperations($graphQlOperations);
    }

    private function getOperationWithDefaults(ApiResource $resource, Operation $operation, bool $generated = false, array $ignoredOptions = []): array
    {
        // Inherit from resource defaults
        foreach (get_class_methods($resource) as $methodName) {
            if (!str_starts_with($methodName, 'get')) {
                continue;
            }

            if (\in_array(lcfirst(substr($methodName, 3)), $ignoredOptions, true)) {
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
            throw new RuntimeException(\sprintf('Operation should be an instance of "%s"', HttpOperation::class));
        }

        if (!$operation->getName() && $operation->getRouteName()) {
            /** @var HttpOperation $operation */
            $operation = $operation->withName($operation->getRouteName());
        }

        $operationName = $operation->getName() ?? $this->getDefaultOperationName($operation, $resource->getClass());

        return [
            $operationName,
            $operation,
        ];
    }

    private function getDefaultShortname(string $resourceClass): string
    {
        return (false !== $pos = strrpos($resourceClass, '\\')) ? substr($resourceClass, $pos + 1) : $resourceClass;
    }

    private function getDefaultOperationName(HttpOperation $operation, string $resourceClass): string
    {
        $path = ($operation->getRoutePrefix() ?? '').($operation->getUriTemplate() ?? '');

        return \sprintf(
            '_api_%s_%s%s',
            $path ?: ($operation->getShortName() ?? $this->getDefaultShortname($resourceClass)),
            strtolower($operation->getMethod()),
            $operation instanceof CollectionOperationInterface ? '_collection' : '');
    }
}
