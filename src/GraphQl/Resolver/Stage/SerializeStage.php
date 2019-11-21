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

namespace ApiPlatform\Core\GraphQl\Resolver\Stage;

use ApiPlatform\Core\DataProvider\Pagination;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Serialize stage of GraphQL resolvers.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class SerializeStage implements SerializeStageInterface
{
    private $resourceMetadataFactory;
    private $normalizer;
    private $serializerContextBuilder;
    private $pagination;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, NormalizerInterface $normalizer, SerializerContextBuilderInterface $serializerContextBuilder, Pagination $pagination)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->normalizer = $normalizer;
        $this->serializerContextBuilder = $serializerContextBuilder;
        $this->pagination = $pagination;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($itemOrCollection, string $resourceClass, string $operationName, array $context): ?array
    {
        $isCollection = $context['is_collection'];
        $isMutation = $context['is_mutation'];

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        if (!$resourceMetadata->getGraphqlAttribute($operationName, 'serialize', true, true)) {
            if ($isCollection) {
                if ($this->pagination->isGraphQlEnabled($resourceClass, $operationName, $context)) {
                    return $this->getDefaultPaginatedData();
                }

                return [];
            }

            if ($isMutation) {
                return $this->getDefaultMutationData($context);
            }

            return null;
        }

        $normalizationContext = $this->serializerContextBuilder->create($resourceClass, $operationName, $context, true);

        $args = $context['args'];
        /** @var ResolveInfo $info */
        $info = $context['info'];

        $data = null;
        if (!$isCollection) {
            if ($isMutation && 'delete' === $operationName) {
                $data = ['id' => $args['input']['id'] ?? null];
            } else {
                $data = $this->normalizer->normalize($itemOrCollection, ItemNormalizer::FORMAT, $normalizationContext);
            }
        }

        if ($isCollection && is_iterable($itemOrCollection)) {
            if (!$this->pagination->isGraphQlEnabled($resourceClass, $operationName, $context)) {
                $data = [];
                foreach ($itemOrCollection as $index => $object) {
                    $data[$index] = $this->normalizer->normalize($object, ItemNormalizer::FORMAT, $normalizationContext);
                }
            } else {
                $data = $this->serializePaginatedCollection($itemOrCollection, $normalizationContext, $context);
            }
        }

        if (null !== $data && !\is_array($data)) {
            throw Error::createLocatedError('Expected serialized data to be a nullable array.', $info->fieldNodes, $info->path);
        }

        if ($isMutation) {
            $wrapFieldName = lcfirst($resourceMetadata->getShortName());

            return [$wrapFieldName => $data] + $this->getDefaultMutationData($context);
        }

        return $data;
    }

    /**
     * @throws Error
     */
    private function serializePaginatedCollection(iterable $collection, array $normalizationContext, array $context): array
    {
        $args = $context['args'];
        /** @var ResolveInfo $info */
        $info = $context['info'];

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

        $data = $this->getDefaultPaginatedData();

        if (($totalItems = $collection->getTotalItems()) > 0) {
            $data['totalCount'] = $totalItems;
            $data['pageInfo']['startCursor'] = base64_encode((string) $offset);
            $end = $offset + $nbPageItems - 1;
            $data['pageInfo']['endCursor'] = base64_encode((string) ($end >= 0 ? $end : 0));
            $itemsPerPage = $collection->getItemsPerPage();
            $data['pageInfo']['hasNextPage'] = (float) ($itemsPerPage > 0 ? $offset % $itemsPerPage : $offset) + $itemsPerPage * $collection->getCurrentPage() < $totalItems;
            $data['pageInfo']['hasPreviousPage'] = $offset > 0;
        }

        $index = 0;
        foreach ($collection as $object) {
            $data['edges'][$index] = [
                'node' => $this->normalizer->normalize($object, ItemNormalizer::FORMAT, $normalizationContext),
                'cursor' => base64_encode((string) ($index + $offset)),
            ];
            ++$index;
        }

        return $data;
    }

    private function getDefaultPaginatedData(): array
    {
        return ['totalCount' => 0., 'edges' => [], 'pageInfo' => ['startCursor' => null, 'endCursor' => null, 'hasNextPage' => false, 'hasPreviousPage' => false]];
    }

    private function getDefaultMutationData(array $context): array
    {
        return ['clientMutationId' => $context['args']['input']['clientMutationId'] ?? null];
    }
}
