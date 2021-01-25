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

namespace ApiPlatform\Core\Bridge\Elasticsearch\Serializer;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Item normalizer decorator that prevents {@see \ApiPlatform\Core\Serializer\ItemNormalizer}
 * from taking over for the {@see DocumentNormalizer::FORMAT} format because of priorities.
 *
 * @experimental
 */
final class ItemNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface, CacheableSupportsMethodInterface
{
    private $decorated;

    public function __construct(NormalizerInterface $decorated)
    {
        if (!$decorated instanceof DenormalizerInterface) {
            throw new InvalidArgumentException(sprintf('The decorated normalizer must be an instance of "%s".', DenormalizerInterface::class));
        }

        if (!$decorated instanceof SerializerAwareInterface) {
            throw new InvalidArgumentException(sprintf('The decorated normalizer must be an instance of "%s".', SerializerAwareInterface::class));
        }

        if (!$decorated instanceof CacheableSupportsMethodInterface) {
            throw new InvalidArgumentException(sprintf('The decorated normalizer must be an instance of "%s".', CacheableSupportsMethodInterface::class));
        }

        $this->decorated = $decorated;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return $this->decorated->hasCacheableSupportsMethod();
    }

    public function denormalize($data, $type, $format = null, array $context = [])
    {
        return $this->decorated->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return DocumentNormalizer::FORMAT !== $format && $this->decorated->supportsDenormalization($data, $type, $format);
    }

    public function normalize($object, $format = null, array $context = [])
    {
        return $this->decorated->normalize($object, $format, $context);
    }

    public function supportsNormalization($data, $format = null)
    {
        return DocumentNormalizer::FORMAT !== $format && $this->decorated->supportsNormalization($data, $format);
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        $this->decorated->setSerializer($serializer);
    }
}
