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

namespace ApiPlatform\Core\Graphql\Resolver;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Creates a function resolving a GraphQL mutation of an item.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ItemMutationResolverFactory implements ItemMutationResolverFactoryInterface
{
    private $identifiersExtractor;
    private $itemDataProvider;
    private $normalizer;
    private $resourceMetadataFactory;
    private $dataPersister;

    public function __construct(IdentifiersExtractorInterface $identifiersExtractor, ItemDataProviderInterface $itemDataProvider, NormalizerInterface $normalizer, ResourceMetadataFactoryInterface $resourceMetadataFactory, DataPersisterInterface $dataPersister)
    {
        if (!$normalizer instanceof DenormalizerInterface) {
            throw new InvalidArgumentException(sprintf('The normalizer must implements the "%s" interface', DenormalizerInterface::class));
        }

        $this->identifiersExtractor = $identifiersExtractor;
        $this->itemDataProvider = $itemDataProvider;
        $this->normalizer = $normalizer;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->dataPersister = $dataPersister;
    }

    public function createItemMutationResolver(string $resourceClass, string $mutationName): callable
    {
        return function ($root, $args, $context, ResolveInfo $info) use ($resourceClass, $mutationName) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            $item = null;
            if ('update' === $mutationName || 'delete' === $mutationName) {
                $id = $this->getIdentifier($this->identifiersExtractor->getIdentifiersFromResourceClass($resourceClass), $args, $info);
                $item = $this->getItem($resourceClass, $id, $resourceMetadata, $info);
            }

            switch ($mutationName) {
                case 'create':
                case 'update':
                    $context = null === $item ? ['resource_class' => $resourceClass] : ['resource_class' => $resourceClass, 'object_to_populate' => $item];
                    $item = $this->normalizer->denormalize($args['input'], $resourceClass, null, $context);
                    $this->dataPersister->persist($item);

                    return $this->normalizer->normalize(
                        $item,
                        null,
                        ['graphql' => true] + $resourceMetadata->getGraphqlAttribute($mutationName, 'normalization_context', [], true)
                    );

                case 'delete':
                    $this->dataPersister->remove($item);

                    return $args['input'];
            }
        };
    }

    private function getIdentifier(array $identifiers, $args, $info)
    {
        if (1 === \count($identifiers)) {
            return $args['input'][$identifiers[0]];
        }

        $identifierPairs = [];
        foreach ($identifiers as $key => $identifier) {
            if (!\is_array($args['input'][$identifier])) {
                $identifierPairs[$key] = "{$identifier}={$args['input'][$identifier]}";

                continue;
            }

            if (\count($args['input'][$identifier]) > 1) {
                throw Error::createLocatedError('Composite identifiers are not allowed for a resource already used as a composite identifier', $info->fieldNodes, $info->path);
            }

            $identifierPairs[$key] = "$identifier=".reset($args['input'][$identifier]);
        }

        return implode(';', $identifierPairs);
    }

    private function getItem(string $resourceClass, $id, ResourceMetadata $resourceMetadata, $info)
    {
        $item = $this->itemDataProvider->getItem($resourceClass, $id);
        if (null === $item) {
            throw Error::createLocatedError(sprintf('Item "%s" identified by "%s" not found', $resourceMetadata->getShortName(), $id), $info->fieldNodes, $info->path);
        }

        return $item;
    }
}
