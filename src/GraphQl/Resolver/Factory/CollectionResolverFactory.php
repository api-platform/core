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

namespace ApiPlatform\Core\GraphQl\Resolver\Factory;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use ApiPlatform\Core\GraphQl\Resolver\FieldsToAttributesTrait;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use ApiPlatform\Core\GraphQl\Resolver\ResourceAccessCheckerTrait;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Security\ResourceAccessCheckerInterface;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Creates a function retrieving a collection to resolve a GraphQL query or a field returned by a mutation.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class CollectionResolverFactory implements ResolverFactoryInterface
{
    use FieldsToAttributesTrait;
    use ResourceAccessCheckerTrait;

    private $collectionDataProvider;
    private $subresourceDataProvider;
    private $queryResolverLocator;
    private $normalizer;
    private $resourceAccessChecker;
    private $requestStack;
    private $paginationEnabled;
    private $resourceMetadataFactory;

    public function __construct(CollectionDataProviderInterface $collectionDataProvider, SubresourceDataProviderInterface $subresourceDataProvider, ContainerInterface $queryResolverLocator, NormalizerInterface $normalizer, ResourceMetadataFactoryInterface $resourceMetadataFactory, ResourceAccessCheckerInterface $resourceAccessChecker = null, RequestStack $requestStack = null, bool $paginationEnabled = false)
    {
        $this->collectionDataProvider = $collectionDataProvider;
        $this->subresourceDataProvider = $subresourceDataProvider;
        $this->queryResolverLocator = $queryResolverLocator;
        $this->normalizer = $normalizer;
        $this->resourceAccessChecker = $resourceAccessChecker;
        $this->requestStack = $requestStack;
        $this->paginationEnabled = $paginationEnabled;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    public function __invoke(string $resourceClass = null, string $rootClass = null, string $operationName = null): callable
    {
        return function ($source, $args, $context, ResolveInfo $info) use ($resourceClass, $rootClass, $operationName) {
            if (null === $resourceClass) {
                return null;
            }

            if ($this->requestStack && null !== $request = $this->requestStack->getCurrentRequest()) {
                $request->attributes->set(
                    '_graphql_collections_args',
                    [$resourceClass => $args] + $request->attributes->get('_graphql_collections_args', [])
                );
            }

            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $normalizationContext = $resourceMetadata->getGraphqlAttribute($operationName ?? 'query', 'normalization_context', [], true);
            $normalizationContext['attributes'] = $this->fieldsToAttributes($info);
            $dataProviderContext = $normalizationContext;
            $dataProviderContext['filters'] = $this->getNormalizedFilters($args);
            $dataProviderContext['graphql'] = true;
            $normalizationContext['resource_class'] = $resourceClass;

            $collection = [];

            if ($resourceMetadata->getGraphqlAttribute($operationName ?? 'query', 'read', true, true)) {
                if (isset($rootClass, $source[$rootProperty = $info->fieldName], $source[ItemNormalizer::ITEM_IDENTIFIERS_KEY])) {
                    $rootResolvedFields = $source[ItemNormalizer::ITEM_IDENTIFIERS_KEY];
                    $subresourceCollection = $this->getSubresource($rootClass, $rootResolvedFields, array_keys($rootResolvedFields), $rootProperty, $resourceClass, true, $dataProviderContext);
                    if (!is_iterable($subresourceCollection)) {
                        throw new \UnexpectedValueException('Expected subresource collection to be iterable');
                    }
                    $collection = $subresourceCollection ?? [];
                } else {
                    $collection = $this->collectionDataProvider->getCollection($resourceClass, null, $dataProviderContext);
                }
            }

            $queryResolverId = $resourceMetadata->getGraphqlAttribute($operationName ?? 'query', 'collection_query');
            if (null !== $queryResolverId) {
                /** @var QueryCollectionResolverInterface $queryResolver */
                $queryResolver = $this->queryResolverLocator->get($queryResolverId);
                $collection = $queryResolver($collection, ['source' => $source, 'args' => $args, 'info' => $info]);
            }

            $this->canAccess($this->resourceAccessChecker, $resourceMetadata, $resourceClass, $info, [
                'object' => $collection,
                'previous_object' => \is_object($collection) ? clone $collection : $collection,
            ], $operationName ?? 'query');

            if (!$this->paginationEnabled) {
                if (!$resourceMetadata->getGraphqlAttribute($operationName ?? 'query', 'serialize', true, true)) {
                    return [];
                }

                $data = [];
                foreach ($collection as $index => $object) {
                    $data[$index] = $this->normalizer->normalize($object, ItemNormalizer::FORMAT, $normalizationContext);
                }

                return $data;
            }

            if (!($collection instanceof PaginatorInterface)) {
                throw Error::createLocatedError(sprintf('Collection returned by the collection data provider must implement %s', PaginatorInterface::class), $info->fieldNodes, $info->path);
            }

            $offset = 0;
            $totalItems = $collection->getTotalItems();
            $nbPageItems = $collection->count();
            if (isset($args['after'])) {
                $after = base64_decode($args['after'], true);
                if (false === $after) {
                    throw Error::createLocatedError(sprintf('Cursor %s is invalid', $args['after']), $info->fieldNodes, $info->path);
                }
                $offset = 1 + (int) $after;
            }
            if (isset($args['before'])) {
                $before = base64_decode($args['before'], true);
                if (false === $before) {
                    throw Error::createLocatedError(sprintf('Cursor %s is invalid', $args['before']), $info->fieldNodes, $info->path);
                }
                $offset = (int) $before - $nbPageItems;
            }
            if (isset($args['last']) && !isset($args['before'])) {
                $offset = $totalItems - $args['last'];
            }
            $offset = 0 > $offset ? 0 : $offset;

            $data = ['totalCount' => 0., 'edges' => [], 'pageInfo' => ['startCursor' => null, 'endCursor' => null, 'hasNextPage' => false, 'hasPreviousPage' => false]];

            if (!$resourceMetadata->getGraphqlAttribute($operationName ?? 'query', 'serialize', true, true)) {
                return $data;
            }

            if ($collection instanceof PaginatorInterface && ($totalItems = $collection->getTotalItems()) > 0) {
                $data['totalCount'] = $totalItems;
                $data['pageInfo']['startCursor'] = base64_encode((string) $offset);
                $data['pageInfo']['endCursor'] = base64_encode((string) ($offset + $nbPageItems - 1));
                $data['pageInfo']['hasNextPage'] = $collection->getCurrentPage() !== $collection->getLastPage() && (float) $nbPageItems === $collection->getItemsPerPage();
                $data['pageInfo']['hasPreviousPage'] = $collection->getCurrentPage() > 1 && (float) $nbPageItems === $collection->getItemsPerPage();
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

    /**
     * @throws ResourceClassNotSupportedException
     *
     * @return iterable|object|null
     */
    private function getSubresource(string $rootClass, array $rootResolvedFields, array $rootIdentifiers, string $rootProperty, string $subresourceClass, bool $isCollection, array $normalizationContext)
    {
        $identifiers = [];
        $resolvedIdentifiers = [];
        foreach ($rootIdentifiers as $rootIdentifier) {
            if (isset($rootResolvedFields[$rootIdentifier])) {
                $identifiers[$rootIdentifier] = $rootResolvedFields[$rootIdentifier];
            }

            $resolvedIdentifiers[] = [$rootIdentifier, $rootClass];
        }

        return $this->subresourceDataProvider->getSubresource($subresourceClass, $identifiers, $normalizationContext + [
            'property' => $rootProperty,
            'identifiers' => $resolvedIdentifiers,
            'collection' => $isCollection,
        ]);
    }

    private function getNormalizedFilters(array $args): array
    {
        $filters = $args;

        foreach ($filters as $name => $value) {
            if (\is_array($value)) {
                if (strpos($name, '_list')) {
                    $name = substr($name, 0, \strlen($name) - \strlen('_list'));
                }
                $filters[$name] = $this->getNormalizedFilters($value);
            }

            if (\is_string($name) && strpos($name, '_')) {
                // Gives a chance to relations/nested fields.
                $filters[str_replace('_', '.', $name)] = $value;
            }
        }

        return $filters;
    }
}
