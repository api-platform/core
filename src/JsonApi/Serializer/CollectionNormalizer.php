<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\JsonApi\Serializer;

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\ContextTrait;
use ApiPlatform\Core\Util\IriHelper;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes collections in the Json Api format.
 *
 * @author Kevin Dunglas <dunglas@gmail.com>
 * @author Hamza Amrouche <hamza@les-tilleuls.coop>
 */
final class CollectionNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use ContextTrait;
    use NormalizerAwareTrait;

    const FORMAT = 'jsonapi';

    private $resourceClassResolver;
    private $pageParameterName;
    private $resourceMetadataFactory;

    public function __construct(ResourceClassResolverInterface $resourceClassResolver, ResourceMetadataFactoryInterface $resourceMetadataFactory, string $pageParameterName)
    {
        $this->resourceClassResolver = $resourceClassResolver;
        $this->pageParameterName = $pageParameterName;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
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
        $data = ['data' => []];
        if (isset($context['api_sub_level'])) {
            foreach ($object as $index => $obj) {
                $data['data'][][$index] = $this->normalizer->normalize($obj, $format, $context);
            }

            return $data;
        }

        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class'] ?? null, true);
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $context = $this->initContext($resourceClass, $context);
        $parsed = IriHelper::parseIri($context['request_uri'] ?? '/', $this->pageParameterName);
        $paginated = $isPaginator = $object instanceof PaginatorInterface;

        if ($isPaginator) {
            $currentPage = $object->getCurrentPage();
            $lastPage = $object->getLastPage();

            $paginated = 1. !== $lastPage;
        }

        $data = [
                'links' => [
                    'self' => IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $paginated ? $currentPage : null),
                ],
        ];

        if ($paginated) {
            $data['links']['first'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, 1.);
            $data['links']['last'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $lastPage);

            if (1. !== $currentPage) {
                $data['links']['prev'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $currentPage - 1.);
            }

            if ($currentPage !== $lastPage) {
                $data['links']['next'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $currentPage + 1.);
            }
        }

        foreach ($object as $obj) {
            $item = $this->normalizer->normalize($obj, $format, $context);
            $relationships = [];

            if (isset($item['relationships'])) {
                $relationships = $item['relationships'];
                unset($item['relationships']);
            }

            $data['data'][] = ['type' => $resourceMetadata->getShortName(), 'id' => '@todo', 'attributes' => $item, 'relationships' => $relationships];

        }

        if (is_array($object) || $object instanceof \Countable) {
            $data['meta']['total-pages'] = $object instanceof PaginatorInterface ? (int) $object->getTotalItems() : count($object);
        }

        return $data;
    }
}
