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

use ApiPlatform\JsonLd\Serializer\HydraPrefixTrait;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\IriHelper;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Adds a view key to the result of a paginated Hydra collection.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class PartialCollectionViewNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use HydraPrefixTrait;
    private readonly PropertyAccessorInterface $propertyAccessor;

    /**
     * @param array<string, mixed> $defaultContext
     */
    public function __construct(private readonly NormalizerInterface $collectionNormalizer, private readonly string $pageParameterName = 'page', private string $enabledParameterName = 'pagination', private readonly ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null, ?PropertyAccessorInterface $propertyAccessor = null, private readonly int $urlGenerationStrategy = UrlGeneratorInterface::ABS_PATH, private readonly array $defaultContext = [])
    {
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
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
        $parsed = IriHelper::parseIri($context['uri'] ?? $context['request_uri'] ?? '/', $this->pageParameterName);
        $appliedFilters = $parsed['parameters'];
        unset($appliedFilters[$this->enabledParameterName]);

        if (!$appliedFilters && !$paginated) {
            return $data;
        }

        $isPaginatedWithCursor = false;
        $cursorPaginationAttribute = null;
        $operation = $context['operation'] ?? null;
        if (!$operation && $this->resourceMetadataFactory && isset($context['resource_class']) && $paginated) {
            $operation = $this->resourceMetadataFactory->create($context['resource_class'])->getOperation($context['operation_name'] ?? null);
        }

        $cursorPaginationAttribute = $operation instanceof HttpOperation ? $operation->getPaginationViaCursor() : null;
        $isPaginatedWithCursor = (bool) $cursorPaginationAttribute;

        $hydraPrefix = $this->getHydraPrefix($context + $this->defaultContext);
        $data[$hydraPrefix.'view'] = ['@id' => null, '@type' => $hydraPrefix.'PartialCollectionView'];

        if ($isPaginatedWithCursor) {
            return $this->populateDataWithCursorBasedPagination($data, $parsed, $object, $cursorPaginationAttribute, $operation?->getUrlGenerationStrategy() ?? $this->urlGenerationStrategy, $hydraPrefix);
        }

        $data[$hydraPrefix.'view']['@id'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $paginated ? $currentPage : null, $operation?->getUrlGenerationStrategy() ?? $this->urlGenerationStrategy);

        if ($paginated) {
            return $this->populateDataWithPagination($data, $parsed, $currentPage, $lastPage, $itemsPerPage, $pageTotalItems, $operation?->getUrlGenerationStrategy() ?? $this->urlGenerationStrategy, $hydraPrefix);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->collectionNormalizer->supportsNormalization($data, $format, $context);
    }

    public function getSupportedTypes($format): array
    {
        return $this->collectionNormalizer->getSupportedTypes($format);
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

    private function populateDataWithCursorBasedPagination(array $data, array $parsed, \Traversable $object, ?array $cursorPaginationAttribute, ?int $urlGenerationStrategy, string $hydraPrefix): array
    {
        $objects = iterator_to_array($object);
        $firstObject = current($objects);
        $lastObject = end($objects);

        $data[$hydraPrefix.'view']['@id'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], urlGenerationStrategy: $urlGenerationStrategy);

        if (false !== $lastObject && \is_array($cursorPaginationAttribute)) {
            $data[$hydraPrefix.'view'][$hydraPrefix.'next'] = IriHelper::createIri($parsed['parts'], array_merge($parsed['parameters'], $this->cursorPaginationFields($cursorPaginationAttribute, 1, $lastObject)), urlGenerationStrategy: $urlGenerationStrategy);
        }

        if (false !== $firstObject && \is_array($cursorPaginationAttribute)) {
            $data[$hydraPrefix.'view'][$hydraPrefix.'previous'] = IriHelper::createIri($parsed['parts'], array_merge($parsed['parameters'], $this->cursorPaginationFields($cursorPaginationAttribute, -1, $firstObject)), urlGenerationStrategy: $urlGenerationStrategy);
        }

        return $data;
    }

    private function populateDataWithPagination(array $data, array $parsed, ?float $currentPage, ?float $lastPage, ?float $itemsPerPage, ?float $pageTotalItems, ?int $urlGenerationStrategy, string $hydraPrefix): array
    {
        if (null !== $lastPage) {
            $data[$hydraPrefix.'view'][$hydraPrefix.'first'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, 1., $urlGenerationStrategy);
            $data[$hydraPrefix.'view'][$hydraPrefix.'last'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $lastPage, $urlGenerationStrategy);
        }

        if (1. !== $currentPage) {
            $data[$hydraPrefix.'view'][$hydraPrefix.'previous'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $currentPage - 1., $urlGenerationStrategy);
        }

        if ((null !== $lastPage && $currentPage < $lastPage) || (null === $lastPage && $pageTotalItems >= $itemsPerPage)) {
            $data[$hydraPrefix.'view'][$hydraPrefix.'next'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $currentPage + 1., $urlGenerationStrategy);
        }

        return $data;
    }
}
