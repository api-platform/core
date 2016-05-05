<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Hydra\Serializer;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\FilterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\JsonLd\Serializer\ContextTrait;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
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

    public function __construct(NormalizerInterface $collectionNormalizer, ResourceMetadataFactoryInterface $resourceMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, FilterCollection $filters)
    {
        $this->collectionNormalizer = $collectionNormalizer;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->filters = $filters;
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
        if (isset($context['jsonld_sub_level'])) {
            return $data;
        }

        $resourceClass = $this->getResourceClass($this->resourceClassResolver, $object, $context);
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

        if ([] !== $currentFilters) {
            $data['hydra:search'] = $this->getSearch($resourceClass, $requestParts, $currentFilters);
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
}
