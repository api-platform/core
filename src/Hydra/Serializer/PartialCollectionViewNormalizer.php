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

namespace ApiPlatform\Hydra\Serializer;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Serializer\CacheableSupportsMethodInterface;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\Util\IriHelper;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface as BaseCacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Adds a view key to the result of a paginated Hydra collection.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class PartialCollectionViewNormalizer implements NormalizerInterface, NormalizerAwareInterface, CacheableSupportsMethodInterface
{
    private readonly PropertyAccessorInterface $propertyAccessor;

    public function __construct(private readonly NormalizerInterface $collectionNormalizer, private readonly string $pageParameterName = 'page', private string $enabledParameterName = 'pagination', private readonly ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null, PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $data = $this->collectionNormalizer->normalize($object, $format, $context);

        if (isset($context['api_sub_level'])) {
            return $data;
        }

        if (!\is_array($data)) {
            throw new UnexpectedValueException('Expected data to be an array');
        }

        $currentPage = $lastPage = $itemsPerPage = $pageTotalItems = null;
        if ($paginated = ($object instanceof PartialPaginatorInterface)) {
            if ($object instanceof PaginatorInterface) {
                $paginated = 1. !== $lastPage = $object->getLastPage();
            } else {
                $itemsPerPage = $object->getItemsPerPage();
                $pageTotalItems = (float) \count($object);
            }

            $currentPage = $object->getCurrentPage();
        }

        // TODO: This needs to be changed as well as I wrote in the CollectionFiltersNormalizer
        // We should not rely on the request_uri but instead rely on the UriTemplate
        // This needs that we implement the RFC and that we do more parsing before calling the serialization (MainController)
        $parsed = IriHelper::parseIri($context['request_uri'] ?? '/', $this->pageParameterName);
        $appliedFilters = $parsed['parameters'];
        unset($appliedFilters[$this->enabledParameterName]);

        if (!$appliedFilters && !$paginated) {
            return $data;
        }

        $isPaginatedWithCursor = false;
        $cursorPaginationAttribute = null;
        if ($this->resourceMetadataFactory && isset($context['resource_class']) && $paginated) {
            /** @var HttpOperation $operation */
            $operation = $this->resourceMetadataFactory->create($context['resource_class'])->getOperation($context['operation_name'] ?? null);
            $isPaginatedWithCursor = [] !== $cursorPaginationAttribute = ($operation->getPaginationViaCursor() ?? []);
        }

        $data['hydra:view'] = ['@id' => null, '@type' => 'hydra:PartialCollectionView'];

        if ($isPaginatedWithCursor) {
            return $this->populateDataWithCursorBasedPagination($data, $parsed, $object, $cursorPaginationAttribute);
        }

        $data['hydra:view']['@id'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $paginated ? $currentPage : null);

        if ($paginated) {
            return $this->populateDataWithPagination($data, $parsed, $currentPage, $lastPage, $itemsPerPage, $pageTotalItems);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $this->collectionNormalizer->supportsNormalization($data, $format, $context);
    }

    public function getSupportedTypes($format): array
    {
        // @deprecated remove condition when support for symfony versions under 6.3 is dropped
        if (!method_exists($this->collectionNormalizer, 'getSupportedTypes')) {
            return [
                '*' => $this->collectionNormalizer instanceof BaseCacheableSupportsMethodInterface && $this->collectionNormalizer->hasCacheableSupportsMethod(),
            ];
        }

        return $this->collectionNormalizer->getSupportedTypes($format);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        if (method_exists(Serializer::class, 'getSupportedTypes')) {
            trigger_deprecation(
                'api-platform/core',
                '3.1',
                'The "%s()" method is deprecated, use "getSupportedTypes()" instead.',
                __METHOD__
            );
        }

        return $this->collectionNormalizer instanceof BaseCacheableSupportsMethodInterface && $this->collectionNormalizer->hasCacheableSupportsMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function setNormalizer(NormalizerInterface $normalizer): void
    {
        if ($this->collectionNormalizer instanceof NormalizerAwareInterface) {
            $this->collectionNormalizer->setNormalizer($normalizer);
        }
    }

    private function cursorPaginationFields(array $fields, int $direction, $object): array
    {
        $paginationFilters = [];

        foreach ($fields as $field) {
            $forwardRangeOperator = 'desc' === strtolower($field['direction']) ? 'lt' : 'gt';
            $backwardRangeOperator = 'gt' === $forwardRangeOperator ? 'lt' : 'gt';

            $operator = $direction > 0 ? $forwardRangeOperator : $backwardRangeOperator;

            $paginationFilters[$field['field']] = [
                $operator => (string) $this->propertyAccessor->getValue($object, $field['field']),
            ];
        }

        return $paginationFilters;
    }

    private function populateDataWithCursorBasedPagination(array $data, array $parsed, \Traversable $object, ?array $cursorPaginationAttribute): array
    {
        $objects = iterator_to_array($object);
        $firstObject = current($objects);
        $lastObject = end($objects);

        $data['hydra:view']['@id'] = IriHelper::createIri($parsed['parts'], $parsed['parameters']);

        if (false !== $lastObject && \is_array($cursorPaginationAttribute)) {
            $data['hydra:view']['hydra:next'] = IriHelper::createIri($parsed['parts'], array_merge($parsed['parameters'], $this->cursorPaginationFields($cursorPaginationAttribute, 1, $lastObject)));
        }

        if (false !== $firstObject && \is_array($cursorPaginationAttribute)) {
            $data['hydra:view']['hydra:previous'] = IriHelper::createIri($parsed['parts'], array_merge($parsed['parameters'], $this->cursorPaginationFields($cursorPaginationAttribute, -1, $firstObject)));
        }

        return $data;
    }

    private function populateDataWithPagination(array $data, array $parsed, ?float $currentPage, ?float $lastPage, ?float $itemsPerPage, ?float $pageTotalItems): array
    {
        if (null !== $lastPage) {
            $data['hydra:view']['hydra:first'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, 1.);
            $data['hydra:view']['hydra:last'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $lastPage);
        }

        if (1. !== $currentPage) {
            $data['hydra:view']['hydra:previous'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $currentPage - 1.);
        }

        if ((null !== $lastPage && $currentPage < $lastPage) || (null === $lastPage && $pageTotalItems >= $itemsPerPage)) {
            $data['hydra:view']['hydra:next'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $currentPage + 1.);
        }

        return $data;
    }
}
