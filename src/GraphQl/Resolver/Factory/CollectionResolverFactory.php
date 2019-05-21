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

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use ApiPlatform\Core\GraphQl\Resolver\FieldsToAttributesTrait;
use ApiPlatform\Core\GraphQl\Resolver\ResourceAccessCheckerTrait;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Security\ResourceAccessCheckerInterface;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
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
    private $normalizer;
    private $identifiersExtractor;
    private $resourceAccessChecker;
    private $requestStack;
    private $paginationEnabled;
    private $resourceMetadataFactory;

    public function __construct(CollectionDataProviderInterface $collectionDataProvider, SubresourceDataProviderInterface $subresourceDataProvider, NormalizerInterface $normalizer, IdentifiersExtractorInterface $identifiersExtractor, ResourceMetadataFactoryInterface $resourceMetadataFactory, ResourceAccessCheckerInterface $resourceAccessChecker = null, RequestStack $requestStack = null, bool $paginationEnabled = false)
    {
        $this->subresourceDataProvider = $subresourceDataProvider;
        $this->collectionDataProvider = $collectionDataProvider;
        $this->normalizer = $normalizer;
        $this->identifiersExtractor = $identifiersExtractor;
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

            if (isset($rootClass, $source[$rootProperty = $info->fieldName], $source[ItemNormalizer::ITEM_KEY])) {
                $rootResolvedFields = $this->identifiersExtractor->getIdentifiersFromItem(unserialize($source[ItemNormalizer::ITEM_KEY]));
                $subresource = $this->getSubresource($rootClass, $rootResolvedFields, array_keys($rootResolvedFields), $rootProperty, $resourceClass, true, $dataProviderContext);
                $collection = $subresource ?? [];
            } else {
                $collection = $this->collectionDataProvider->getCollection($resourceClass, null, $dataProviderContext);
            }

            $this->canAccess($this->resourceAccessChecker, $resourceMetadata, $resourceClass, $info, [
                'object' => $collection,
                'previous_object' => \is_object($collection) ? clone $collection : $collection,
            ], $operationName ?? 'query');

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

            $data = ['totalCount' => 0.0, 'edges' => [], 'pageInfo' => ['endCursor' => null, 'hasNextPage' => false]];
            if ($collection instanceof PaginatorInterface && ($totalItems = $collection->getTotalItems()) > 0) {
                $nbPageItems = $collection->count();
                $data['pageInfo']['endCursor'] = base64_encode((string) ($offset + $nbPageItems - 1));
                $data['pageInfo']['hasNextPage'] = $collection->getCurrentPage() !== $collection->getLastPage() && (float) $nbPageItems === $collection->getItemsPerPage();
                $data['totalCount'] = $totalItems;
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
     * @return object|null
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
