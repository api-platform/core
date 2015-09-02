<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Hydra\Serializer;

use Dunglas\ApiBundle\Api\FilterCollection;
use Dunglas\ApiBundle\Api\FilterInterface;
use Dunglas\ApiBundle\Api\ResourceClassResolverInterface;
use Dunglas\ApiBundle\JsonLd\Serializer\ContextTrait;
use Dunglas\ApiBundle\Metadata\Resource\Factory\ItemMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Enhance the result of collection by adding the filters applied on collection.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class CollectionFiltersNormalizer extends SerializerAwareNormalizer implements NormalizerInterface
{
    use ContextTrait;

    /**
     * @var NormalizerInterface
     */
    private $collectionNormalizer;

    /**
     * @var ItemMetadataFactoryInterface
     */
    private $itemMetadataFactory;

    /**
     * @var ResourceClassResolverInterface
     */
    private $resourceClassResolver;

    /**
     * @var FilterCollection
     */
    private $filters;

    public function __construct(NormalizerInterface $collectionNormalizer, ItemMetadataFactoryInterface $itemMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, FilterCollection $filters)
    {
        $this->collectionNormalizer = $collectionNormalizer;
        $this->itemMetadataFactory = $itemMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->filters = $filters;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $data = $this->collectionNormalizer->normalize($object, $format, $context);
        if (isset($context['jsonld_sub_level'])) {
            return $data;
        }

        $resourceClass = $this->getResourceClass($this->resourceClassResolver, $object, $context);
        $itemMetadata = $this->itemMetadataFactory->create($resourceClass);

        $operationName = $context['collection_operation_name'] ?? null;

        if ($operationName) {
            $resourceFilters = $itemMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);
        } else {
            $resourceFilters = $itemMetadata->getAttribute('filters', []);
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

        if ([] !== $currentFilters) {
            $data['hydra:search'] = $this->getSearch($resourceClass, $requestParts, $currentFilters);
        }

        return $data;
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
    private function getSearch(string $resourceClass, array $parts, array $filters) : array
    {
        $variables = [];
        $mapping = [];
        foreach ($filters as $filter) {
            foreach ($filter->getDescription($resourceClass) as $variable => $data) {
                $variables[] = $variable;
                $mapping[] = [
                    '@type' => 'IriTemplateMapping',
                    'variable' => $variable,
                    'property' => $data['property'],
                    'required' => $data['required'],
                ];
            }
        }

        return [
            '@type' => 'hydra:IriTemplate',
            'hydra:template' => sprintf('%s{?%s}', $parts['path'], implode(',', $variables)),
            'hydra:variableRepresentation' => 'BasicRepresentation',
            'hydra:mapping' => $mapping,
        ];
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
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;

        if ($this->collectionNormalizer instanceof SerializerAwareNormalizer) {
            $this->collectionNormalizer->setSerializer($serializer);
        }
    }
}
