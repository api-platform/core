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
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

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
        return static::FORMAT === $format && is_iterable($data) && isset($context['resource_class']) && !isset($context['api_sub_level']);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @param iterable $object
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class']);
        $context = $this->initContext($resourceClass, $context);
        $data = [];
        $paginationData = $this->getPaginationData($object, $context);

        $metadata = $this->resourceMetadataFactory->create($context['resource_class'] ?? '');
        if (($operation = $context['operation'] ?? null) instanceof CollectionOperationInterface && method_exists($operation, 'getItemUriTemplate') && ($itemUriTemplate = $operation->getItemUriTemplate())) {
            $context['operation'] = $metadata->getOperation($itemUriTemplate);
        } else {
            unset($context['operation']);
        }
        unset($context['operation_type'], $context['operation_name']);
        $itemsData = $this->getItemsData($object, $format, $context);

        return array_merge_recursive($data, $paginationData, $itemsData);
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

    /**
     * Gets the pagination data.
     */
    abstract protected function getPaginationData(iterable $object, array $context = []): array;

    /**
     * Gets items data.
     */
    abstract protected function getItemsData(iterable $object, string $format = null, array $context = []): array;
}
