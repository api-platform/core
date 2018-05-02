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

namespace ApiPlatform\Core\Serializer;

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\PartialPaginatorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Base collection normalizer.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
abstract class AbstractCollectionNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use ContextTrait { initContext as protected; }
    use NormalizerAwareTrait;

    /**
     * This constant must be overridden in the child class.
     */
    const FORMAT = 'to-override';

    protected $resourceClassResolver;
    protected $pageParameterName;

    public function __construct(ResourceClassResolverInterface $resourceClassResolver, string $pageParameterName)
    {
        $this->resourceClassResolver = $resourceClassResolver;
        $this->pageParameterName = $pageParameterName;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return static::FORMAT === $format && (is_array($data) || $data instanceof \Traversable);
    }

    /**
     * {@inheritdoc}
     *
     * @param iterable $object
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $data = [];
        if (isset($context['api_sub_level'])) {
            foreach ($object as $index => $obj) {
                $data[$index] = $this->normalizer->normalize($obj, $format, $context);
            }

            return $data;
        }

        $context = $this->initContext(
            $this->resourceClassResolver->getResourceClass($object, $context['resource_class'] ?? null, true),
            $context
        );

        return array_merge_recursive(
            $data,
            $this->getPaginationData($object, $context),
            $this->getItemsData($object, $format, $context)
        );
    }

    /**
     * Gets the pagination configuration.
     *
     * @param iterable $object
     * @param array    $context
     *
     * @return array
     */
    protected function getPaginationConfig($object, array $context = []): array
    {
        $currentPage = $lastPage = $itemsPerPage = $pageTotalItems = $totalItems = null;

        if ($paginated = $paginator = $object instanceof PartialPaginatorInterface) {
            if ($object instanceof PaginatorInterface) {
                $paginated = 1. !== $lastPage = $object->getLastPage();
                $totalItems = $object->getTotalItems();
            } else {
                $pageTotalItems = (float) count($object);
            }

            $currentPage = $object->getCurrentPage();
            $itemsPerPage = $object->getItemsPerPage();
        } elseif (is_array($object) || $object instanceof \Countable) {
            $totalItems = count($object);
        }

        return [$paginator, $paginated, $currentPage, $itemsPerPage, $lastPage, $pageTotalItems, $totalItems];
    }

    /**
     * Gets the pagination data.
     *
     * @param iterable $object
     * @param array    $context
     *
     * @return array
     */
    abstract protected function getPaginationData($object, array $context = []): array;

    /**
     * Gets items data.
     *
     * @param iterable    $object
     * @param string|null $format
     * @param array       $context
     *
     * @return array
     */
    abstract protected function getItemsData($object, string $format = null, array $context = []): array;
}
