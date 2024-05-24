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

namespace ApiPlatform\GraphQl\Resolver\Factory;

use ApiPlatform\GraphQl\State\Provider\NoopProvider;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use GraphQL\Type\Definition\ResolveInfo;

class ResolverFactory implements ResolverFactoryInterface
{
    public function __construct(
        private readonly ProviderInterface $provider,
        private readonly ProcessorInterface $processor
    ) {
    }

    public function __invoke(?string $resourceClass = null, ?string $rootClass = null, ?Operation $operation = null, ?PropertyMetadataFactoryInterface $propertyMetadataFactory = null): callable
    {
        return function (?array $source, array $args, $context, ResolveInfo $info) use ($resourceClass, $rootClass, $operation, $propertyMetadataFactory) {
            if (\array_key_exists($info->fieldName, $source ?? [])) {
                $body = $source[$info->fieldName];

                // special treatment for nested resources without a resolver/provider
                if ($operation instanceof Query && $operation->getNested() && !$operation->getResolver() && (!$operation->getProvider() || NoopProvider::class === $operation->getProvider())) {
                    return \is_array($body) ? $this->resolve(
                        $source,
                        $args,
                        $info,
                        $rootClass,
                        $operation,
                        new ArrayPaginator($body, 0, \count($body))
                    ) : $body;
                }

                $propertyMetadata = $rootClass ? $propertyMetadataFactory?->create($rootClass, $info->fieldName) : null;
                $type = $propertyMetadata?->getBuiltinTypes()[0] ?? null;
                // Data already fetched and normalized (field or nested resource)
                if ($body || null === $resourceClass || ($type && !$type->isCollection())) {
                    return $body;
                }
            }

            // If authorization has failed for a relation field (e.g. via ApiProperty security), the field is not present in the source: null can be returned directly to ensure the collection isn't in the response.
            if ($operation && (null === $resourceClass || null === $rootClass || (null !== $source && !\array_key_exists($info->fieldName, $source)))) {
                return null;
            }

            return $this->resolve($source, $args, $info, $rootClass, $operation, null);
        };
    }

    private function resolve(?array $source, array $args, ResolveInfo $info, ?string $rootClass = null, ?Operation $operation = null, mixed $body = null)
    {
        // Handles relay nodes
        $operation ??= new Query();

        $graphQlContext = [];
        $context = ['source' => $source, 'args' => $args, 'info' => $info, 'root_class' => $rootClass, 'graphql_context' => &$graphQlContext];

        if (null === $operation->canValidate()) {
            $operation = $operation->withValidate($operation instanceof Mutation && !$operation instanceof DeleteOperationInterface);
        }

        $body ??= $this->provider->provide($operation, [], $context);

        if (null === $operation->canWrite()) {
            $operation = $operation->withWrite($operation instanceof Mutation && null !== $body);
        }

        return $this->processor->process($body, $operation, [], $context);
    }
}
