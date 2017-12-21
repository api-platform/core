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

namespace ApiPlatform\Core\Graphql\Resolver\Factory;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use ApiPlatform\Core\Graphql\Serializer\ItemNormalizer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Creates a function retrieving a collection to resolve a GraphQL query.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class CollectionResolverFactory implements ResolverFactoryInterface
{
    private $collectionDataProvider;
    private $subresourceDataProvider;
    private $normalizer;
    private $identifiersExtractor;
    private $requestStack;
    private $paginationEnabled;
    private $resourceMetadataFactory;

    public function __construct(CollectionDataProviderInterface $collectionDataProvider, SubresourceDataProviderInterface $subresourceDataProvider, NormalizerInterface $normalizer, IdentifiersExtractorInterface $identifiersExtractor, ResourceMetadataFactoryInterface $resourceMetadataFactory, RequestStack $requestStack = null, bool $paginationEnabled = false)
    {
        $this->subresourceDataProvider = $subresourceDataProvider;
        $this->collectionDataProvider = $collectionDataProvider;
        $this->normalizer = $normalizer;
        $this->identifiersExtractor = $identifiersExtractor;
        $this->requestStack = $requestStack;
        $this->paginationEnabled = $paginationEnabled;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    public function __invoke(string $resourceClass = null, string $rootClass = null, string $operationName = null): callable
    {
        return function ($source, $args, $context, ResolveInfo $info) use ($resourceClass, $rootClass) {
            if (null !== $request = $this->requestStack->getCurrentRequest()) {
                $request->attributes->set(
                    '_graphql_collections_args',
                    [$resourceClass => $args] + $request->attributes->get('_graphql_collections_args', [])
                );
            }

            if (isset($source[$rootProperty = $info->fieldName], $source[ItemNormalizer::ITEM_KEY])) {
                $rootResolvedFields = $this->identifiersExtractor->getIdentifiersFromItem(unserialize($source[ItemNormalizer::ITEM_KEY]));
                $subresource = $this->getSubresource($rootClass, $rootResolvedFields, array_keys($rootResolvedFields), $rootProperty, $resourceClass, true);
                $collection = $subresource ?? [];
            } else {
                $collection = $this->collectionDataProvider->getCollection($resourceClass);
            }

            $normalizationContext = $this->resourceMetadataFactory
                ->create($resourceClass)
                ->getGraphqlAttribute('query', 'normalization_context', [], true);
            $normalizationContext['attributes'] = $this->fieldsToAttributes($info);

            if (!$this->paginationEnabled) {
                $data = [];
                foreach ($collection as $index => $object) {
                    $data[$index] = $this->normalizer->normalize($object, ItemNormalizer::FORMAT, $normalizationContext);
                }

                return $data;
            }

            $offset = 0;
            if (isset($args['after'])) {
                $after = base64_decode($args['after'], true);
                if (false === $after) {
                    throw Error::createLocatedError(sprintf('Cursor %s is invalid', $args['after']), $info->fieldNodes, $info->path);
                }
                $offset = 1 + (int) $after;
            }

            $data = ['edges' => [], 'pageInfo' => ['endCursor' => null, 'hasNextPage' => false]];
            if ($collection instanceof PaginatorInterface && ($totalItems = $collection->getTotalItems()) > 0) {
                $data['pageInfo']['endCursor'] = base64_encode((string) ($totalItems - 1));
                $data['pageInfo']['hasNextPage'] = $collection->getCurrentPage() !== $collection->getLastPage() && (float) $collection->count() === $collection->getItemsPerPage();
            }

            foreach ($collection as $index => $object) {
                $data['edges'][$index] = [
                    'node' => $this->normalizer->normalize($object, ItemNormalizer::FORMAT, $normalizationContext),
                    'cursor' => base64_encode((string) ($index + $offset)),
                ];
            }

            return $data;
        };
    }

    private function fieldsToAttributes(ResolveInfo $info): array
    {
        $fields = $info->getFieldSelection(PHP_INT_MAX);
        if (isset($fields['edges']['node'])) {
            $fields = $fields['edges']['node'];
        }

        return $fields;
    }

    /**
     * @throws ResourceClassNotSupportedException
     *
     * @return object|null
     */
    private function getSubresource(string $rootClass, array $rootResolvedFields, array $rootIdentifiers, string $rootProperty, string $subresourceClass, bool $isCollection)
    {
        $identifiers = [];
        $resolvedIdentifiers = [];
        foreach ($rootIdentifiers as $rootIdentifier) {
            if (isset($rootResolvedFields[$rootIdentifier])) {
                $identifiers[$rootIdentifier] = $rootResolvedFields[$rootIdentifier];
            }

            $resolvedIdentifiers[] = [$rootIdentifier, $rootClass];
        }

        return $this->subresourceDataProvider->getSubresource($subresourceClass, $identifiers, [
            'property' => $rootProperty,
            'identifiers' => $resolvedIdentifiers,
            'collection' => $isCollection,
        ]);
    }
}
