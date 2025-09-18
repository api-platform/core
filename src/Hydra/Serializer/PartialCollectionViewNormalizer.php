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

use ApiPlatform\Hydra\State\Util\PaginationHelperTrait;
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
    use PaginationHelperTrait;
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

        $paginated = $object instanceof PartialPaginatorInterface;
        if ($paginated && $object instanceof PaginatorInterface) {
            $paginated = 1. !== $object->getLastPage();
        }

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

        if ($isPaginatedWithCursor) {
            $data[$hydraPrefix.'view'] = ['@id' => null, '@type' => $hydraPrefix.'PartialCollectionView'];

            return $this->populateDataWithCursorBasedPagination($data, $parsed, $object, $cursorPaginationAttribute, $operation?->getUrlGenerationStrategy() ?? $this->urlGenerationStrategy, $hydraPrefix);
        }

        $partialCollectionView = $this->getPartialCollectionView($object, $context['uri'] ?? $context['request_uri'] ?? '/', $this->pageParameterName, $this->enabledParameterName, $operation?->getUrlGenerationStrategy() ?? $this->urlGenerationStrategy);

        $view = [
            '@id' => $partialCollectionView->id,
            '@type' => $hydraPrefix.'PartialCollectionView',
        ];

        if (null !== $partialCollectionView->first) {
            $view[$hydraPrefix.'first'] = $partialCollectionView->first;
            $view[$hydraPrefix.'last'] = $partialCollectionView->last;
        }

        if (null !== $partialCollectionView->previous) {
            $view[$hydraPrefix.'previous'] = $partialCollectionView->previous;
        }

        if (null !== $partialCollectionView->next) {
            $view[$hydraPrefix.'next'] = $partialCollectionView->next;
        }

        $data[$hydraPrefix.'view'] = $view;

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->collectionNormalizer->supportsNormalization($data, $format, $context);
    }

    /**
     * @param string|null $format
     */
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

    /**
     * @param object $object
     */
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
}
