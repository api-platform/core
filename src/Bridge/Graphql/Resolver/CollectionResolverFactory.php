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
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Creates a function retrieving a collection to resolve a GraphQL query.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @internal
 */
final class CollectionResolverFactory extends AbstractResolverFactory implements CollectionResolverFactoryInterface
{
    private $collectionDataProvider;
    private $normalizer;
    private $identifiersExtractor;
    private $requestStack;
    private $paginationEnabled;

    public function __construct(CollectionDataProviderInterface $collectionDataProvider, SubresourceDataProviderInterface $subresourceDataProvider, NormalizerInterface $normalizer, IdentifiersExtractorInterface $identifiersExtractor, RequestStack $requestStack = null, bool $paginationEnabled = false)
    {
        parent::__construct($subresourceDataProvider);

        $this->collectionDataProvider = $collectionDataProvider;
        $this->normalizer = $normalizer;
        $this->identifiersExtractor = $identifiersExtractor;
        $this->requestStack = $requestStack;
        $this->paginationEnabled = $paginationEnabled;
    }

    /**
     * @throws \Exception
     */
    public function createCollectionResolver(string $resourceClass, string $rootClass): callable
    {
        return function ($root, $args, $context, ResolveInfo $info) use ($resourceClass, $rootClass) {
            $request = $this->requestStack ? $this->requestStack->getCurrentRequest() : null;
            if (null !== $request) {
                $request->attributes->set(
                    '_graphql_collections_args',
                    [$resourceClass => $args] + $request->attributes->get('_graphql_collections_args', [])
                );
            }

            $rootProperty = $info->fieldName;
            if (isset($root[$rootProperty])) {
                $rootIdentifiers = $this->identifiersExtractor->getIdentifiersFromResourceClass($rootClass);
                $subresource = $this->getSubresource($rootClass, $root, $rootIdentifiers, $rootProperty, $resourceClass, true);
                $collection = $subresource ?? [];
            } else {
                $collection = $this->collectionDataProvider->getCollection($resourceClass);
            }

            if (!$this->paginationEnabled) {
                $data = [];
                foreach ($collection as $index => $object) {
                    $data[$index] = $this->normalizer->normalize($object, null, ['graphql' => true]);
                }

                return $data;
            }

            $offset = 0;
            if (isset($args['after'])) {
                $after = \base64_decode($args['after'], true);
                if (false === $after) {
                    throw Error::createLocatedError(sprintf('Cursor %s is invalid', $args['after']), $info->fieldNodes, $info->path);
                }
                $offset = 1 + (int) $after;
            }

            $data = ['edges' => [], 'pageInfo' => ['endCursor' => null, 'hasNextPage' => false]];
            if ($collection instanceof PaginatorInterface && ($totalItems = $collection->getTotalItems()) > 0) {
                $data['pageInfo']['endCursor'] = \base64_encode((string) ($totalItems - 1));
                $data['pageInfo']['hasNextPage'] = $collection->getCurrentPage() !== $collection->getLastPage() && (float) $collection->count() === $collection->getItemsPerPage();
            }

            foreach ($collection as $index => $object) {
                $data['edges'][$index] = [
                    'node' => $this->normalizer->normalize($object, null, ['graphql' => true]),
                    'cursor' => \base64_encode((string) ($index + $offset)),
                ];
            }

            return $data;
        };
    }
}
