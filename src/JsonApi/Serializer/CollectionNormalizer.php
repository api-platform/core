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
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\ContextTrait;
use ApiPlatform\Core\Util\IriHelper;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes collections in the JSON API format.
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
    private $propertyMetadataFactory;

    public function __construct(
        ResourceClassResolverInterface $resourceClassResolver,
        ResourceMetadataFactoryInterface $resourceMetadataFactory,
        PropertyMetadataFactoryInterface $propertyMetadataFactory,
        string $pageParameterName
    ) {
        $this->resourceClassResolver = $resourceClassResolver;
        $this->pageParameterName = $pageParameterName;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
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
        $currentPage = $lastPage = $itemsPerPage = 1;

        // TODO: Document the use of api_sub_level
        // $data = ['data' => []];
        // if (isset($context['api_sub_level'])) {
        //     foreach ($object as $index => $obj) {
        //         $data['data'][][$index] = $this->normalizer->normalize($obj, $format, $context);
        //     }

        //     return $data;
        // }

        $resourceClass = $this->resourceClassResolver->getResourceClass(
            $object,
            $context['resource_class'] ?? null,
            true
        );

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        $context = $this->initContext($resourceClass, $context);

        $parsed = IriHelper::parseIri($context['request_uri'] ?? '/', $this->pageParameterName);
        $paginated = $isPaginator = $object instanceof PaginatorInterface;

        if ($isPaginator) {
            $currentPage = $object->getCurrentPage();
            $lastPage = $object->getLastPage();
            $itemsPerPage = $object->getItemsPerPage();

            $paginated = 1. !== $lastPage;
        }

        $data = [
            'data' => [],
            'links' => [
                // TODO: This should not be an IRI
                'self' => IriHelper::createIri(
                    $parsed['parts'],
                    $parsed['parameters'],
                    $this->pageParameterName,
                    $paginated ? $currentPage : null
                ),
            ],
        ];

        if ($paginated) {
            $data['links']['first'] = IriHelper::createIri(
                $parsed['parts'],
                $parsed['parameters'],
                $this->pageParameterName,
                1.
            );

            $data['links']['last'] = IriHelper::createIri(
                $parsed['parts'],
                $parsed['parameters'],
                $this->pageParameterName,
                $lastPage
            );

            if (1. !== $currentPage) {
                $data['links']['prev'] = IriHelper::createIri(
                    $parsed['parts'],
                    $parsed['parameters'],
                    $this->pageParameterName,
                    $currentPage - 1.
                );
            }

            if ($currentPage !== $lastPage) {
                $data['links']['next'] = IriHelper::createIri(
                    $parsed['parts'],
                    $parsed['parameters'],
                    $this->pageParameterName,
                    $currentPage + 1.
                );
            }
        }

        $identifier = null;
        foreach ($object as $obj) {
            $item = $this->normalizer->normalize($obj, $format, $context)['data']['attributes'];

            $relationships = [];

            if (isset($item['relationships'])) {
                $relationships = $item['relationships'];
                unset($item['relationships']);
            }

            foreach ($item as $property => $value) {
                $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $property);

                if ($propertyMetadata->isIdentifier()) {
                    $identifier = $item[$property];
                }
            }

            $items = [
                'type' => $resourceMetadata->getShortName(),
                // The id attribute must be a string
                // http://jsonapi.org/format/#document-resource-object-identification
                'id' => (string) $identifier ?? '',
                'attributes' => $item,
            ];

            if ($relationships) {
                $items['relationships'] = $relationships;
            }

            $data['data'][] = $items;
        }

        if (is_array($object) || $object instanceof \Countable) {
            $data['meta']['totalItems'] = $object instanceof PaginatorInterface ?
                (int) $object->getTotalItems() :
                count($object);
        }

        if ($isPaginator) {
            $data['meta']['itemsPerPage'] = (int) $itemsPerPage;
            $data['meta']['currentPage'] = (int) $currentPage;
        }

        return $data;
    }
}
