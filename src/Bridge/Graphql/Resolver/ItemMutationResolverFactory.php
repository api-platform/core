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
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;

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
    private $dataPersister;

    public function __construct(IdentifiersExtractorInterface $identifiersExtractor, ItemDataProviderInterface $itemDataProvider, DataPersisterInterface $dataPersister)
    {
        $this->identifiersExtractor = $identifiersExtractor;
        $this->itemDataProvider = $itemDataProvider;
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
            $item = $this->itemDataProvider->getItem($resourceClass, $id);

            if (null === $item) {
                throw Error::createLocatedError("Item $resourceClass $id not found", $info->fieldNodes, $info->path);
            }

            if ('delete' === $mutationName) {
                $this->dataPersister->remove($item);
            }

            return $args['input'];
        };
    }
}
