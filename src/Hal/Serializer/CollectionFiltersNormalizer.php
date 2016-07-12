<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Hal\Serializer;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\FilterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Serializer\ContextTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Enhance the result of collection by adding the filters applied on collection.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class CollectionFiltersNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    use ContextTrait;
    use SerializerAwareTrait {
        setSerializer as baseSetSerializer;
    }

    private $collectionNormalizer;
    private $resourceMetadataFactory;
    private $resourceClassResolver;
    private $filters;
    private $formats;

    public function __construct(NormalizerInterface $collectionNormalizer, ResourceMetadataFactoryInterface $resourceMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, FilterCollection $filters, array $formats)
    {
        $this->collectionNormalizer = $collectionNormalizer;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->filters = $filters;
        $this->formats = $formats;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $this->collectionNormalizer->supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $data = $this->collectionNormalizer->normalize($object, $format, $context);
        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class'] ?? null, true);

        if (isset($context['jsonld_sub_level'])) {
            return $data;
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        $operationName = $context['collection_operation_name'] ?? null;

        if ($operationName) {
            $resourceFilters = $resourceMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);
        } else {
            $resourceFilters = $resourceMetadata->getAttribute('filters', []);
        }

        if ([] === $resourceFilters) {
            return $data;
        }

        $requestParts = parse_url($context['request_uri']);
        if (!is_array($requestParts)) {
            return $data;
        }

        $currentFilters = [];
        foreach ($this->filters as $filterName => $filter) {
            if (in_array($filterName, $resourceFilters)) {
                $currentFilters[] = $filter;
            }
        }
        $context = $this->initContext($resourceClass, $context, $format);

        if ([] !== $currentFilters) {
            if (isset($context['jsonhal_has_context'])) {
                $data['_links']['self'] = array_merge($data['_links']['self'], $this->getSearch($resourceClass, $requestParts, $currentFilters, $context));
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->baseSetSerializer($serializer);

        if ($this->collectionNormalizer instanceof SerializerAwareInterface) {
            $this->collectionNormalizer->setSerializer($serializer);
        }
    }

    /**
     * Returns the content of the Hydra search property.
     *
     * @param string            $resourceClass
     * @param array             $parts
     * @param FilterInterface[] $filters
     *
     * @return array
     */
    private function getSearch(string $resourceClass, array $parts, array $filters, array $context) : array
    {
        $variables = [];
        foreach ($filters as $filter) {
            foreach ($filter->getDescription($resourceClass) as $variable => $data) {
                $variables[] = $variable;
            }

            if (isset($context['jsonhal_has_context'])) {
                return [
                    'find' => sprintf('%s{?%s}', $parts['path'], implode(',', $variables)),
                ];
            }
        }
    }
}
