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

namespace ApiPlatform\Hal\Serializer;

use ApiPlatform\Api\ResourceClassResolverInterface as LegacyResourceClassResolverInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Serializer\AbstractCollectionNormalizer;
use ApiPlatform\Util\IriHelper;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Normalizes collections in the HAL format.
 *
 * @author Kevin Dunglas <dunglas@gmail.com>
 * @author Hamza Amrouche <hamza@les-tilleuls.coop>
 */
final class CollectionNormalizer extends AbstractCollectionNormalizer
{
    public const FORMAT = 'jsonhal';

    public function __construct(ResourceClassResolverInterface|LegacyResourceClassResolverInterface $resourceClassResolver, string $pageParameterName, ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory)
    {
        parent::__construct($resourceClassResolver, $pageParameterName, $resourceMetadataFactory);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPaginationData(iterable $object, array $context = []): array
    {
        [$paginator, $paginated, $currentPage, $itemsPerPage, $lastPage, $pageTotalItems, $totalItems] = $this->getPaginationConfig($object, $context);
        $parsed = IriHelper::parseIri($context['uri'] ?? '/', $this->pageParameterName);

        $operation = $context['operation'] ?? $this->getOperation($context);
        $urlGenerationStrategy = $operation->getUrlGenerationStrategy();

        $data = [
            '_links' => [
                'self' => ['href' => IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $paginated ? $currentPage : null, $urlGenerationStrategy)],
            ],
        ];

        if ($paginated) {
            if (null !== $lastPage) {
                $data['_links']['first']['href'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, 1., $urlGenerationStrategy);
                $data['_links']['last']['href'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $lastPage, $urlGenerationStrategy);
            }

            if (1. !== $currentPage) {
                $data['_links']['prev']['href'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $currentPage - 1., $urlGenerationStrategy);
            }

            if ((null !== $lastPage && $currentPage !== $lastPage) || (null === $lastPage && $pageTotalItems >= $itemsPerPage)) {
                $data['_links']['next']['href'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $currentPage + 1., $urlGenerationStrategy);
            }
        }

        if (null !== $totalItems) {
            $data['totalItems'] = $totalItems;
        }

        if ($paginator) {
            $data['itemsPerPage'] = (int) $itemsPerPage;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnexpectedValueException
     */
    protected function getItemsData(iterable $object, ?string $format = null, array $context = []): array
    {
        $data = [];

        foreach ($object as $obj) {
            $item = $this->normalizer->normalize($obj, $format, $context);
            if (!\is_array($item)) {
                throw new UnexpectedValueException('Expected item to be an array');
            }
            $data['_embedded']['item'][] = $item;
            $data['_links']['item'][] = $item['_links']['self'] ?? null;
        }

        return $data;
    }
}
