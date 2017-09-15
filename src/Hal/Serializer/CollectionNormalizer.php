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

namespace ApiPlatform\Core\Hal\Serializer;

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\PartialPaginatorInterface;
use ApiPlatform\Core\Serializer\ContextTrait;
use ApiPlatform\Core\Util\IriHelper;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes collections in the HAL format.
 *
 * @author Kevin Dunglas <dunglas@gmail.com>
 * @author Hamza Amrouche <hamza@les-tilleuls.coop>
 */
final class CollectionNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use ContextTrait;
    use NormalizerAwareTrait;

    const FORMAT = 'jsonhal';

    private $resourceClassResolver;
    private $pageParameterName;

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
        return self::FORMAT === $format && (is_array($data) || ($data instanceof \Traversable));
    }

    /**
     * {@inheritdoc}
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

        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class'] ?? null, true);
        $context = $this->initContext($resourceClass, $context);
        $parsed = IriHelper::parseIri($context['request_uri'] ?? '/', $this->pageParameterName);

        $currentPage = $lastPage = $itemsPerPage = $pageTotalItems = null;
        if ($paginated = $isPaginator = $object instanceof PartialPaginatorInterface) {
            if ($object instanceof PaginatorInterface) {
                $paginated = 1. !== $lastPage = $object->getLastPage();
            } else {
                $pageTotalItems = (float) count($object);
            }

            $currentPage = $object->getCurrentPage();
            $itemsPerPage = $object->getItemsPerPage();
        }

        $data = [
            '_links' => [
                'self' => IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $paginated ? $currentPage : null),
            ],
        ];

        if ($paginated) {
            if (null !== $lastPage) {
                $data['_links']['first'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, 1.);
                $data['_links']['last'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $lastPage);
            }

            if (1. !== $currentPage) {
                $data['_links']['prev'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $currentPage - 1.);
            }

            if (null !== $lastPage && $currentPage !== $lastPage || null === $lastPage && $pageTotalItems >= $itemsPerPage) {
                $data['_links']['next'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $currentPage + 1.);
            }
        }

        foreach ($object as $obj) {
            $item = $this->normalizer->normalize($obj, $format, $context);

            $data['_embedded']['item'][] = $item;
            $data['_links']['item'][] = $item['_links']['self'];
        }

        $paginated = null;
        if (
            is_array($object) ||
            ($paginated = $object instanceof PaginatorInterface) ||
            $object instanceof \Countable && !$object instanceof PartialPaginatorInterface
        ) {
            $data['totalItems'] = $paginated ? (int) $object->getTotalItems() : count($object);
        }

        if ($isPaginator) {
            $data['itemsPerPage'] = (int) $itemsPerPage;
        }

        return $data;
    }
}
