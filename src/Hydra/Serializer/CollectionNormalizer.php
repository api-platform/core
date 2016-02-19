<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Hydra\Serializer;

use ApiPlatform\Builder\Api\ResourceClassResolverInterface;
use ApiPlatform\Builder\Exception\RuntimeException;
use ApiPlatform\Builder\JsonLd\ContextBuilderInterface;
use ApiPlatform\Builder\JsonLd\Serializer\ContextTrait;
use ApiPlatform\Builder\Metadata\Resource\Factory\ItemMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

/**
 * This normalizer handles collections.
 *
 * @author Kevin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class CollectionNormalizer extends SerializerAwareNormalizer implements NormalizerInterface
{
    use ContextTrait;

    const FORMAT = 'jsonld';
    const HYDRA_COLLECTION = 'hydra:Collection';

    private $itemMetadataFactory;
    private $contextBuilder;
    private $resourceClassResolver;

    public function __construct(ItemMetadataFactoryInterface $itemMetadataFactory, ContextBuilderInterface $contextBuilder, ResourceClassResolverInterface $resourceClassResolver)
    {
        $this->itemMetadataFactory = $itemMetadataFactory;
        $this->contextBuilder = $contextBuilder;
        $this->resourceClassResolver = $resourceClassResolver;
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
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$this->serializer instanceof NormalizerInterface) {
            throw new RuntimeException('The serializer must implement the NormalizerInterface.');
        }

        if (isset($context['jsonld_sub_level'])) {
            $data = [];
            foreach ($object as $index => $obj) {
                $data[$index] = $this->serializer->normalize($obj, $format, $context);
            }

            return $data;
        }

        $resourceClass = $this->getResourceClass($this->resourceClassResolver, $object, $context);
        $resourceItemMetadata = $this->itemMetadataFactory->create($resourceClass);
        $data = $this->addJsonLdContext($this->contextBuilder, $resourceClass, $context);
        $context = $this->createContext($resourceClass, $resourceItemMetadata, $context, true);

        $data['@id'] = $context['request_uri'];
        $data['@type'] = self::HYDRA_COLLECTION;
        $data['hydra:member'] = [];

        foreach ($object as $obj) {
            $data['hydra:member'][] = $this->serializer->normalize($obj, $format, $context);
        }

        return $data;
    }
}
