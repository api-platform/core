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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Base collection normalizer.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
abstract class AbstractCollectionNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use ContextTrait {
        initContext as protected;
    }
    use NormalizerAwareTrait;
    use OperationContextTrait;

    /**
     * This constant must be overridden in the child class.
     */
    public const FORMAT = 'to-override';

    public function __construct(protected ResourceClassResolverInterface $resourceClassResolver, protected string $pageParameterName, protected ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return static::FORMAT === $format && is_iterable($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        /*
         * At this point, support anything that is_iterable(), i.e. array|Traversable
         * for non-objects, symfony uses 'native-'.\gettype($data) :
         * https://github.com/tucksaun/symfony/blob/400685a68b00b0932f8ef41096578872b643099c/src/Symfony/Component/Serializer/Serializer.php#L254
         */
        if (static::FORMAT === $format) {
            return [
                'native-array' => true,
                '\Traversable' => true,
            ];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @param iterable $object
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if (!isset($context['resource_class']) || isset($context['api_sub_level'])) {
            return $this->normalizeRawCollection($object, $format, $context);
        }

        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class']);
        $collectionContext = $this->initContext($resourceClass, $context);
        $data = [];
        $paginationData = $this->getPaginationData($object, $collectionContext);

        $childContext = $this->createOperationContext($collectionContext, $resourceClass);
        if (isset($collectionContext['force_resource_class'])) {
            $childContext['force_resource_class'] = $collectionContext['force_resource_class'];
        }

        $itemsData = $this->getItemsData($object, $format, $childContext);

        return array_merge_recursive($data, $paginationData, $itemsData);
    }

    /**
     * Normalizes a raw collection (not API resources).
     */
    protected function normalizeRawCollection(iterable $object, ?string $format = null, array $context = []): array|\ArrayObject
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
    abstract protected function getItemsData(iterable $object, ?string $format = null, array $context = []): array;
}
