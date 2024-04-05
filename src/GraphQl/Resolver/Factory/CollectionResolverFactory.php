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

use ApiPlatform\GraphQl\Resolver\QueryCollectionResolverInterface;
use ApiPlatform\GraphQl\Resolver\Stage\ReadStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\SecurityPostDenormalizeStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\SecurityStageInterface;
use ApiPlatform\GraphQl\Resolver\Stage\SerializeStageInterface;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Util\CloneTrait;
use ApiPlatform\State\Pagination\ArrayPaginator;
use GraphQL\Type\Definition\ResolveInfo;
use Psr\Container\ContainerInterface;

/**
 * Creates a function retrieving a collection to resolve a GraphQL query or a field returned by a mutation.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class CollectionResolverFactory implements ResolverFactoryInterface
{
    use CloneTrait;

    public function __construct(private readonly ReadStageInterface $readStage, private readonly SecurityStageInterface $securityStage, private readonly SecurityPostDenormalizeStageInterface $securityPostDenormalizeStage, private readonly SerializeStageInterface $serializeStage, private readonly ContainerInterface $queryResolverLocator)
    {
    }

    public function __invoke(?string $resourceClass = null, ?string $rootClass = null, ?Operation $operation = null, ?PropertyMetadataFactoryInterface $propertyMetadataFactory = null): callable
    {
        return function (?array $source, array $args, $context, ResolveInfo $info) use ($resourceClass, $rootClass, $operation): ?array {
            // If authorization has failed for a relation field (e.g. via ApiProperty security), the field is not present in the source: null can be returned directly to ensure the collection isn't in the response.
            if (null === $resourceClass || null === $rootClass || (null !== $source && !\array_key_exists($info->fieldName, $source))) {
                return null;
            }

            if (is_a($resourceClass, \BackedEnum::class, true) && $source && \array_key_exists($info->fieldName, $source)) {
                return $source[$info->fieldName];
            }

            $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => true, 'is_mutation' => false, 'is_subscription' => false];

            if ($operation instanceof Query && $operation->getNested() && !$operation->getResolver() && !$operation->getProvider() && $source && \array_key_exists($info->fieldName, $source)) {
                return ($this->serializeStage)(new ArrayPaginator($source[$info->fieldName], 0, \count($source[$info->fieldName])), $resourceClass, $operation, $resolverContext);
            }

            $collection = ($this->readStage)($resourceClass, $rootClass, $operation, $resolverContext);
            if (!is_iterable($collection)) {
                throw new \LogicException('Collection from read stage should be iterable.');
            }

            $queryResolverId = $operation->getResolver();
            if (null !== $queryResolverId) {
                /** @var QueryCollectionResolverInterface $queryResolver */
                $queryResolver = $this->queryResolverLocator->get($queryResolverId);
                $collection = $queryResolver($collection, $resolverContext);
            }

            // Only perform security stage on the top-level query
            if (null === $source) {
                ($this->securityStage)($resourceClass, $operation, $resolverContext + [
                    'extra_variables' => [
                        'object' => $collection,
                    ],
                ]);
                ($this->securityPostDenormalizeStage)($resourceClass, $operation, $resolverContext + [
                    'extra_variables' => [
                        'object' => $collection,
                        'previous_object' => $this->clone($collection),
                    ],
                ]);
            }

            return ($this->serializeStage)($collection, $resourceClass, $operation, $resolverContext);
        };
    }
}
