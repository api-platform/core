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

namespace ApiPlatform\GraphQl\State\Processor;

use ApiPlatform\GraphQl\Resolver\Util\IdentifierTrait;
use ApiPlatform\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Transforms the data to a GraphQl json format. It uses the Symfony Normalizer then performs changes according to the type of operation.
 */
final class NormalizeProcessor implements ProcessorInterface
{
    use IdentifierTrait;

    public function __construct(private readonly NormalizerInterface $normalizer, private readonly SerializerContextBuilderInterface $serializerContextBuilder, private readonly Pagination $pagination)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array|null
    {
        if (!$operation instanceof GraphQlOperation) {
            return $data;
        }

        return $this->getData($data, $operation, $uriVariables, $context);
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function getData(mixed $itemOrCollection, GraphQlOperation $operation, array $uriVariables = [], array $context = []): ?array
    {
        if (!($operation->canSerialize() ?? true)) {
            if ($operation instanceof CollectionOperationInterface) {
                if ($this->pagination->isGraphQlEnabled($operation, $context)) {
                    return 'cursor' === $this->pagination->getGraphQlPaginationType($operation) ?
                        $this->getDefaultCursorBasedPaginatedData() :
                        $this->getDefaultPageBasedPaginatedData();
                }

                return [];
            }

            if ($operation instanceof Mutation) {
                return $this->getDefaultMutationData($context);
            }

            if ($operation instanceof Subscription) {
                return $this->getDefaultSubscriptionData($context);
            }

            return null;
        }

        $normalizationContext = $this->serializerContextBuilder->create($operation->getClass(), $operation, $context, normalization: true);

        $data = null;
        if (!$operation instanceof CollectionOperationInterface) {
            if ($operation instanceof Mutation && $operation instanceof DeleteOperationInterface) {
                $data = ['id' => $this->getIdentifierFromOperation($operation, $context['args'] ?? [])];
            } else {
                $data = $this->normalizer->normalize($itemOrCollection, ItemNormalizer::FORMAT, $normalizationContext);
            }
        }

        if ($operation instanceof CollectionOperationInterface && is_iterable($itemOrCollection)) {
            if (!$this->pagination->isGraphQlEnabled($operation, $context)) {
                $data = [];
                foreach ($itemOrCollection as $index => $object) {
                    $data[$index] = $this->normalizer->normalize($object, ItemNormalizer::FORMAT, $normalizationContext);
                }
            } else {
                $data = 'cursor' === $this->pagination->getGraphQlPaginationType($operation) ?
                    $this->serializeCursorBasedPaginatedCollection($itemOrCollection, $normalizationContext, $context) :
                    $this->serializePageBasedPaginatedCollection($itemOrCollection, $normalizationContext);
            }
        }

        if (null !== $data && !\is_array($data)) {
            throw new \UnexpectedValueException('Expected serialized data to be a nullable array.');
        }

        $isMutation = $operation instanceof Mutation;
        $isSubscription = $operation instanceof Subscription;
        if ($isMutation || $isSubscription) {
            $wrapFieldName = lcfirst($operation->getShortName());

            return [$wrapFieldName => $data] + ($isMutation ? $this->getDefaultMutationData($context) : $this->getDefaultSubscriptionData($context));
        }

        return $data;
    }

    /**
     * @throws \LogicException
     * @throws \UnexpectedValueException
     */
    private function serializeCursorBasedPaginatedCollection(iterable $collection, array $normalizationContext, array $context): array
    {
        $args = $context['args'];

        if (!($collection instanceof PartialPaginatorInterface)) {
            throw new \LogicException(sprintf('Collection returned by the collection data provider must implement %s or %s.', PaginatorInterface::class, PartialPaginatorInterface::class));
        }

        $offset = 0;
        $totalItems = 1; // For partial pagination, always consider there is at least one item.
        $nbPageItems = $collection->count();
        if (isset($args['after'])) {
            $after = base64_decode($args['after'], true);
            if (false === $after || '' === $args['after']) {
                throw new \UnexpectedValueException('' === $args['after'] ? 'Empty cursor is invalid' : sprintf('Cursor %s is invalid', $args['after']));
            }
            $offset = 1 + (int) $after;
        }

        if ($collection instanceof PaginatorInterface) {
            $totalItems = $collection->getTotalItems();

            if (isset($args['before'])) {
                $before = base64_decode($args['before'], true);
                if (false === $before || '' === $args['before']) {
                    throw new \UnexpectedValueException('' === $args['before'] ? 'Empty cursor is invalid' : sprintf('Cursor %s is invalid', $args['before']));
                }
                $offset = (int) $before - $nbPageItems;
            }
            if (isset($args['last']) && !isset($args['before'])) {
                $offset = $totalItems - $args['last'];
            }
        }

        $offset = 0 > $offset ? 0 : $offset;

        $data = $this->getDefaultCursorBasedPaginatedData();
        if ($totalItems > 0) {
            $data['pageInfo']['startCursor'] = base64_encode((string) $offset);
            $end = $offset + $nbPageItems - 1;
            $data['pageInfo']['endCursor'] = base64_encode((string) ($end >= 0 ? $end : 0));
            $data['pageInfo']['hasPreviousPage'] = $offset > 0;
            if ($collection instanceof PaginatorInterface) {
                $data['totalCount'] = $totalItems;
                $itemsPerPage = $collection->getItemsPerPage();
                $data['pageInfo']['hasNextPage'] = (float) ($itemsPerPage > 0 ? $offset % $itemsPerPage : $offset) + $itemsPerPage * $collection->getCurrentPage() < $totalItems;
            }
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

    /**
     * @throws \LogicException
     */
    private function serializePageBasedPaginatedCollection(iterable $collection, array $normalizationContext): array
    {
        if (!($collection instanceof PaginatorInterface)) {
            throw new \LogicException(sprintf('Collection returned by the collection data provider must implement %s.', PaginatorInterface::class));
        }

        $data = $this->getDefaultPageBasedPaginatedData();
        $data['paginationInfo']['totalCount'] = $collection->getTotalItems();
        $data['paginationInfo']['lastPage'] = $collection->getLastPage();
        $data['paginationInfo']['itemsPerPage'] = $collection->getItemsPerPage();

        foreach ($collection as $object) {
            $data['collection'][] = $this->normalizer->normalize($object, ItemNormalizer::FORMAT, $normalizationContext);
        }

        return $data;
    }

    private function getDefaultCursorBasedPaginatedData(): array
    {
        return ['totalCount' => 0., 'edges' => [], 'pageInfo' => ['startCursor' => null, 'endCursor' => null, 'hasNextPage' => false, 'hasPreviousPage' => false]];
    }

    private function getDefaultPageBasedPaginatedData(): array
    {
        return ['collection' => [], 'paginationInfo' => ['itemsPerPage' => 0., 'totalCount' => 0., 'lastPage' => 0.]];
    }

    private function getDefaultMutationData(array $context): array
    {
        return ['clientMutationId' => $context['args']['input']['clientMutationId'] ?? null];
    }

    private function getDefaultSubscriptionData(array $context): array
    {
        return ['clientSubscriptionId' => $context['args']['input']['clientSubscriptionId'] ?? null];
    }
}
