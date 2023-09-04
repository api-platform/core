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

use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
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

    public function __invoke(string $resourceClass = null, string $rootClass = null, Operation $operation = null): callable
    {
        return function (?array $source, array $args, $context, ResolveInfo $info) use ($resourceClass, $rootClass, $operation) {
            // Data already fetched and normalized (field or nested resource)
            if ($body = $source[$info->fieldName] ?? null) {
                return $body;
            }

            if (null === $resourceClass && \array_key_exists($info->fieldName, $source ?? [])) {
                return $body;
            }

            // If authorization has failed for a relation field (e.g. via ApiProperty security), the field is not present in the source: null can be returned directly to ensure the collection isn't in the response.
            if ($operation && (null === $resourceClass || null === $rootClass || (null !== $source && !\array_key_exists($info->fieldName, $source)))) {
                return null;
            }

            // Handles relay nodes
            $operation ??= new Query();

            $graphQlContext = [];
            $context = ['source' => $source, 'args' => $args, 'info' => $info, 'root_class' => $rootClass, 'graphql_context' => &$graphQlContext];

            if (null === $operation->canValidate()) {
                $operation = $operation->withValidate($operation instanceof Mutation);
            }

            $body = $this->provider->provide($operation, [], $context);

            if (null === $operation->canWrite()) {
                $operation = $operation->withWrite($operation instanceof Mutation && null !== $body);
            }

            return $this->processor->process($body, $operation, [], $context);
        };
    }
}
