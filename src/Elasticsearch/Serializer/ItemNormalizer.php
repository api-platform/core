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

namespace ApiPlatform\Elasticsearch\Serializer;

use ApiPlatform\Serializer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface as BaseCacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Item normalizer decorator that prevents {@see \ApiPlatform\Serializer\ItemNormalizer}
 * from taking over for the {@see DocumentNormalizer::FORMAT} format because of priorities.
 *
 * @experimental
 */
final class ItemNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface, CacheableSupportsMethodInterface
{
    public const FORMAT = 'elasticsearch';

    public function __construct(private readonly NormalizerInterface $decorated)
    {
    }

    /**
     * @throws LogicException
     */
    public function hasCacheableSupportsMethod(): bool
    {
        if (method_exists(Serializer::class, 'getSupportedTypes')) {
            trigger_deprecation(
                'api-platform/core',
                '3.1',
                'The "%s()" method is deprecated, use "getSupportedTypes()" instead.',
                __METHOD__
            );
        }

        if (!$this->decorated instanceof BaseCacheableSupportsMethodInterface) {
            throw new LogicException(sprintf('The decorated normalizer must be an instance of "%s".', BaseCacheableSupportsMethodInterface::class));
        }

        return $this->decorated->hasCacheableSupportsMethod();
    }

    /**
     * {@inheritdoc}
     *
     * @throws LogicException
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (!$this->decorated instanceof DenormalizerInterface) {
            throw new LogicException(sprintf('The decorated normalizer must be an instance of "%s".', DenormalizerInterface::class));
        }

        return $this->decorated->denormalize($data, $type, $format, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @throws LogicException
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if (!$this->decorated instanceof DenormalizerInterface) {
            throw new LogicException(sprintf('The decorated normalizer must be an instance of "%s".', DenormalizerInterface::class));
        }

        return DocumentNormalizer::FORMAT !== $format && $this->decorated->supportsDenormalization($data, $type, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        return $this->decorated->normalize($object, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return DocumentNormalizer::FORMAT !== $format && $this->decorated->supportsNormalization($data, $format);
    }

    public function getSupportedTypes($format): array
    {
        // @deprecated remove condition when support for symfony versions under 6.3 is dropped
        if (!method_exists($this->decorated, 'getSupportedTypes')) {
            return [
                DocumentNormalizer::FORMAT => null,
                '*' => $this->decorated instanceof BaseCacheableSupportsMethodInterface && $this->decorated->hasCacheableSupportsMethod(),
            ];
        }

        return DocumentNormalizer::FORMAT !== $format ? $this->decorated->getSupportedTypes($format) : [];
    }

    /**
     * {@inheritdoc}
     *
     * @throws LogicException
     */
    public function setSerializer(SerializerInterface $serializer): void
    {
        if (!$this->decorated instanceof SerializerAwareInterface) {
            throw new LogicException(sprintf('The decorated normalizer must be an instance of "%s".', SerializerAwareInterface::class));
        }

        $this->decorated->setSerializer($serializer);
    }
}
