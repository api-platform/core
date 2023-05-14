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

namespace ApiPlatform\Serializer;

use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Base collection normalizer.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
abstract class AbstractCollectionNormalizer implements NormalizerInterface, NormalizerAwareInterface, CacheableSupportsMethodInterface
{
    use ContextTrait {
        initContext as protected;
    }
    use NormalizerAwareTrait;

    /**
     * This constant must be overridden in the child class.
     */
    // @noRector \Rector\Php81\Rector\ClassConst\FinalizePublicClassConstantRector
    public const FORMAT = 'to-override';

    public function __construct(protected ResourceClassResolverInterface $resourceClassResolver, protected string $pageParameterName, protected ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return static::FORMAT === $format && is_iterable($data);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param iterable $object
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if (!isset($context['resource_class']) || isset($context['api_sub_level'])) {
            return $this->normalizeRawCollection($object, $format, $context);
        }

        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class']);
        $context = $this->initContext($resourceClass, $context);
        $data = [];
        $paginationData = $this->getPaginationData($object, $context);

        if (($operation = $context['operation'] ?? null) && method_exists($operation, 'getItemUriTemplate')) {
            $context['item_uri_template'] = $operation->getItemUriTemplate();
        }

        // We need to keep this operation for serialization groups for later
        if (isset($context['operation'])) {
            $context['root_operation'] = $context['operation'];
        }

        if (isset($context['operation_name'])) {
            $context['root_operation_name'] = $context['operation_name'];
        }

        unset($context['operation']);
        unset($context['operation_type'], $context['operation_name']);

        $itemsData = $this->getItemsData($object, $format, $context);

        return array_merge_recursive($data, $paginationData, $itemsData);
    }

    /**
     * Normalizes a raw collection (not API resources).
     */
    protected function normalizeRawCollection(iterable $object, string $format = null, array $context = []): array|\ArrayObject
    {
        if (!$object && ($context[Serializer::EMPTY_ARRAY_AS_OBJECT] ?? false) && \is_array($object)) {
            return new \ArrayObject();
        }

        $data = [];
        foreach ($object as $index => $obj) {
            $data[$index] = $this->normalizer->normalize($obj, $format, $context);
        }

        return $data;
    }

    /**
     * Gets the pagination configuration.
     */
    protected function getPaginationConfig(iterable $object, array $context = []): array
    {
        $currentPage = $lastPage = $itemsPerPage = $pageTotalItems = $totalItems = null;
        $paginated = $paginator = false;

        if ($object instanceof PartialPaginatorInterface) {
            $paginated = $paginator = true;
            if ($object instanceof PaginatorInterface) {
                $paginated = 1. !== $lastPage = $object->getLastPage();
                $totalItems = $object->getTotalItems();
            } else {
                $pageTotalItems = (float) \count($object);
            }

            $currentPage = $object->getCurrentPage();
            $itemsPerPage = $object->getItemsPerPage();
        } elseif (is_countable($object)) {
            $totalItems = \count($object);
        }

        return [$paginator, $paginated, $currentPage, $itemsPerPage, $lastPage, $pageTotalItems, $totalItems];
    }

    protected function getOperation(array $context = []): Operation
    {
        $metadata = $this->resourceMetadataFactory->create($context['resource_class'] ?? '');

        return $metadata->getOperation($context['operation_name'] ?? null);
    }

    /**
     * Gets the pagination data.
     */
    abstract protected function getPaginationData(iterable $object, array $context = []): array;

    /**
     * Gets items data.
     */
    abstract protected function getItemsData(iterable $object, string $format = null, array $context = []): array;
}
