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

use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use ApiPlatform\Core\Serializer\ContextTrait;
use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use Hateoas\Factory\LinksFactory;
use Hateoas\Factory\EmbeddedsFactory;

/**
 * Converts between objects and array including HAL metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemNormalizer extends AbstractItemNormalizer
{
    use ContextTrait;

    const FORMAT = 'jsonhal';

    private $componentsCache = [];
    private $attributesMetadataCache = [];
    private $linkFactory;
    private $embeddedsFactory;

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return self::FORMAT === $format && parent::supportsNormalization($data, $format);
    }

    public function setLinksFactory(LinksFactory $linkFactory)
    {
        $this->linksFactory = $linkFactory;
    }

    public function setEmbeddedsFactory(EmbeddedsFactory $embeddedsFactory)
    {
        $this->embeddedsFactory =  $embeddedsFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!isset($context['cache_key'])) {
            $context['cache_key'] = $this->getHalCacheKey($format, $context);
        }

        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class'] ?? null, true);
        $context = $this->initContext($resourceClass, $context);
        $context['api_normalize'] = true;

        $rawData = parent::normalize($object, $format, $context);
        if (!\is_array($rawData)) {
            return $rawData;
        }

        $embeddeds = $this->embeddedsFactory->create($object, $context);
        $links     = $this->linksFactory->create($object, $context);

        $merge = function ($originalData, $key, $data) {
            return empty($data)? $originalData:  array_merge($originalData, [$key => $data]);
        };

        $rawData = $merge($rawData, '_embedded', self::serializeEmbeddeds($embeddeds));
        $rawData = $merge($rawData, '_links', self::serializeLinks($links));

        return $rawData;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return false;
    }

    /**
     * Gets the cache key to use.
     *
     *
     * @return bool|string
     */
    private function getHalCacheKey(string $format = null, array $context)
    {
        try {
            return md5($format.serialize($context));
        } catch (\Exception $exception) {
            // The context cannot be serialized, skip the cache
            return false;
        }
    }

     /**
     * {@inheritdoc}
     */
    public static function serializeLinks(array $links)
    {
        $serializedLinks = array();
        foreach ($links as $link) {
            $serializedLink = array_merge(array(
                'href' => $link->getHref(),
            ), $link->getAttributes());

            if (!isset($serializedLinks[$link->getRel()]) && 'curies' !== $link->getRel()) {
                $serializedLinks[$link->getRel()] = $serializedLink;
            } elseif (isset($serializedLinks[$link->getRel()]['href'])) {
                $serializedLinks[$link->getRel()] = array(
                    $serializedLinks[$link->getRel()],
                    $serializedLink
                );
            } else {
                $serializedLinks[$link->getRel()][] = $serializedLink;
            }
        }

        return $serializedLinks;
    }

     /**
     * {@inheritdoc}
     */
    public static function serializeEmbeddeds(array $embeddeds)
    {
        $serializedEmbeddeds = array();
        $multiple = array();
        foreach ($embeddeds as $embedded) {
            if (!isset($serializedEmbeddeds[$embedded->getRel()])) {
                $serializedEmbeddeds[$embedded->getRel()] = $embedded->getData();
            } elseif (!isset($multiple[$embedded->getRel()])) {
                $multiple[$embedded->getRel()] = true;
                $serializedEmbeddeds[$embedded->getRel()] = array(
                    $serializedEmbeddeds[$embedded->getRel()],
                    $embedded->getData(),
                );
            } else {
                $serializedEmbeddeds[$embedded->getRel()][] = $embedded->getData();
            }
        }
        return  $serializedEmbeddeds;
    }
}
