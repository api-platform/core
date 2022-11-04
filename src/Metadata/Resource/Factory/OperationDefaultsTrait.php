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

use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

trait OperationDefaultsTrait
{
    private CamelCaseToSnakeCaseNameConverter $camelCaseToSnakeCaseNameConverter;
    private array $defaults = [];
    private LoggerInterface $logger;

    private function addGlobalDefaults(ApiResource|Operation $operation): ApiResource|Operation
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
        if ($operation->getName() && null !== ($operations = $resource->getOperations())) {
            if (!$operations->has($operation->getName())) {
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
}
