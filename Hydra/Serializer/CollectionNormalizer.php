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

use Dunglas\ApiBundle\Api\ResourceResolver;
use Dunglas\ApiBundle\JsonLd\ContextBuilder;
use Dunglas\ApiBundle\JsonLd\Serializer\ContextTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

/**
 * This normalizer handles collections.
 *
 * @author Kevin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
class CollectionNormalizer extends SerializerAwareNormalizer implements NormalizerInterface
{
    const FORMAT = 'jsonld';

    use ContextTrait;

    /**
     * @var string
     */
    const HYDRA_COLLECTION = 'hydra:Collection';

    /**
     * @var ContextBuilder
     */
    private $contextBuilder;
    /**
     * @var ResourceResolver
     */
    private $resourceResolver;

    /**
     * @param ContextBuilder $contextBuilder
     */
    public function __construct(ContextBuilder $contextBuilder, ResourceResolver $resourceResolver)
    {
        $this->contextBuilder = $contextBuilder;
        $this->resourceResolver = $resourceResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return self::FORMAT === $format && (is_array($data) || $data instanceof \Traversable);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $resource = $this->resourceResolver->guessResource($object, $context);

        if (isset($context['jsonld_sub_level'])) {
            $data = [];
            foreach ($object as $index => $obj) {
                $data[$index] = $this->serializer->normalize($obj, $format, $context);
            }
        } else {
            $context = $this->createContext($resource, $context);
            $data = [
                '@context' => $this->contextBuilder->getResourceContext($resource, $context),
                '@id' => $context['request_uri'],
                '@type' => self::HYDRA_COLLECTION,
                'hydra:member' => [],
            ];

            foreach ($object as $obj) {
                $data['hydra:member'][] = $this->serializer->normalize($obj, $format, $context);
            }
        }

        return $data;
    }
}
