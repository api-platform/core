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

use Dunglas\ApiBundle\Api\Filter\FilterInterface;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Api\ResourceResolver;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Enhance the result of collection by adding the filters applied on collection.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
class CollectionFiltersEnhancer extends SerializerAwareNormalizer implements NormalizerInterface
{
    /**
     * @var CollectionNormalizer
     */
    private $collectionNormalizer;

    /**
     * @var ResourceResolver
     */
    private $resourceResolver;

    /**
     * @param NormalizerInterface $collectionNormalizer
     * @param ResourceResolver    $resourceResolver
     */
    public function __construct(NormalizerInterface $collectionNormalizer, ResourceResolver $resourceResolver)
    {
        $this->collectionNormalizer = $collectionNormalizer;
        $this->resourceResolver = $resourceResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $data = $this->collectionNormalizer->normalize($object, $format, $context);
        $resource = $this->resourceResolver->guessResource($object, $context);

        if (!isset($context['jsonld_sub_level'])) {
            $filters = $resource->getFilters();
            if (!empty($filters)) {
                $requestParts = parse_url($context['request_uri']);
                if (is_array($requestParts)) {
                    $data['hydra:search'] = $this->getSearch($resource, $requestParts, $filters);
                }
            }
        }

        return $data;
    }

    /**
     * Returns the content of the Hydra search property.
     *
     * @param ResourceInterface $resource
     * @param array             $parts
     * @param FilterInterface[] $filters
     *
     * @return array
     */
    private function getSearch(ResourceInterface $resource, array $parts, array $filters)
    {
        $variables = [];
        $mapping = [];
        foreach ($filters as $filter) {
            foreach ($filter->getDescription($resource) as $variable => $data) {
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
