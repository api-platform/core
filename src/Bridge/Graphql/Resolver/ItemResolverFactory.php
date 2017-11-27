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
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Creates a function retrieving an item to resolve a GraphQL query.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @internal
 */
final class ItemResolverFactory extends AbstractResolverFactory implements ItemResolverFactoryInterface
{
    private $itemDataProvider;
    private $normalizer;
    private $identifiersExtractor;
    private $resourceMetadataFactory;

    public function __construct(ItemDataProviderInterface $itemDataProvider, SubresourceDataProviderInterface $subresourceDataProvider, NormalizerInterface $normalizer, IdentifiersExtractorInterface $identifiersExtractor, ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        parent::__construct($subresourceDataProvider);

        $this->itemDataProvider = $itemDataProvider;
        $this->normalizer = $normalizer;
        $this->identifiersExtractor = $identifiersExtractor;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * @throws \Exception
     */
    public function createItemResolver(string $resourceClass, string $rootClass): callable
    {
        return function ($root, $args, $context, ResolveInfo $info) use ($resourceClass, $rootClass) {
            $rootProperty = $info->fieldName;
            $rootIdentifiers = $this->identifiersExtractor->getIdentifiersFromResourceClass($rootClass);
            if (isset($root[$rootProperty])) {
                $item = $this->getSubresource($rootClass, $root, $rootIdentifiers, $rootProperty, $resourceClass, false);
            } else {
                $identifiers = [];
                $uniqueIdentifier = [];
                foreach ($rootIdentifiers as $rootIdentifier) {
                    if (!isset($args[$rootIdentifier])) {
                        continue;
                    }

                    if (\is_array($args[$rootIdentifier])) {
                        if (\count($args[$rootIdentifier]) > 1) {
                            throw Error::createLocatedError('Composite identifiers are not allowed for a resource already used as a composite identifier', $info->fieldNodes, $info->path);
                        }

                        $identifiers[] = $rootIdentifier.'='.\reset($args[$rootIdentifier]);

                        continue;
                    }

                    $identifiers[] = "{$rootIdentifier}={$args[$rootIdentifier]}";
                    $uniqueIdentifier[] = $args[$rootIdentifier];
                }

                if (!$identifiers) {
                    return null;
                }

                $item = $this->itemDataProvider->getItem($resourceClass, \count($identifiers) > 1 ? \implode(';', $identifiers) : $uniqueIdentifier[0]);
            }

            return $item ? $this->normalizer->normalize(
                $item,
                null,
                ['graphql' => true] + $this->resourceMetadataFactory
                    ->create($resourceClass)
                    ->getGraphqlAttribute('query', 'normalization_context', [], true)
            ) : null;
        };
    }
}
