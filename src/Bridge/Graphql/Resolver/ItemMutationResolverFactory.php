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

namespace ApiPlatform\Core\Bridge\Graphql\Resolver;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Serializer\Serializer;

/**
 * Creates a function resolving a GraphQL mutation of an item.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @internal
 */
final class ItemMutationResolverFactory implements ItemMutationResolverFactoryInterface
{
    private $identifiersExtractor;
    private $itemDataProvider;
    private $serializer;
    private $resourceMetadataFactory;
    private $dataPersister;

    public function __construct(IdentifiersExtractorInterface $identifiersExtractor, ItemDataProviderInterface $itemDataProvider, Serializer $serializer, ResourceMetadataFactoryInterface $resourceMetadataFactory, DataPersisterInterface $dataPersister)
    {
        $this->identifiersExtractor = $identifiersExtractor;
        $this->itemDataProvider = $itemDataProvider;
        $this->serializer = $serializer;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->dataPersister = $dataPersister;
    }

    public function createItemMutationResolver(string $resourceClass, string $mutationName): callable
    {
        return function ($root, $args, $context, ResolveInfo $info) use ($resourceClass, $mutationName) {
            $identifiers = $this->identifiersExtractor->getIdentifiersFromResourceClass($resourceClass);
            if (\count($identifiers) > 1) {
                $identifierPairs = \array_map(function ($identifier) use ($args, $info) {
                    if (\is_array($args['input'][$identifier])) {
                        if (\count($args['input'][$identifier]) > 1) {
                            throw Error::createLocatedError('Composite identifiers are not allowed for a resource already used as a composite identifier', $info->fieldNodes, $info->path);
                        }

                        return $identifier.'='.\reset($args['input'][$identifier]);
                    }

                    return "{$identifier}={$args['input'][$identifier]}";
                }, $identifiers);
                $id = \implode(';', $identifierPairs);
            } else {
                $id = $args['input'][$identifiers[0]];
            }

            if ('delete' === $mutationName) {
                $item = $this->itemDataProvider->getItem($resourceClass, $id);

                if (null === $item) {
                    throw Error::createLocatedError("Item $resourceClass $id not found", $info->fieldNodes, $info->path);
                }

                $this->dataPersister->remove($item);

                return $args['input'];
            } elseif ('update' === $mutationName) {
                $item = $this->serializer->denormalize($args['input'], $resourceClass, null, ['resource_class' => $resourceClass]);

                $this->dataPersister->persist($item);

                return $this->serializer->normalize(
                    $item,
                    null,
                    ['graphql' => true] + $this->resourceMetadataFactory
                        ->create($resourceClass)
                        ->getGraphqlAttribute($mutationName, 'normalization_context', [], true)
                );
            }
        };
    }
}
