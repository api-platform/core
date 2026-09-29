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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Serializer\Decorator;

use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ValidateOnce;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[AsDecorator('api_platform.jsonld.normalizer.item')]
final class DenormalizingJsonLdItemNormalizerDecorator implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    public function __construct(#[AutowireDecorated] private readonly NormalizerInterface&DenormalizerInterface&SerializerAwareInterface $inner)
    {
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        return $this->inner->normalize($data, $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->inner->supportsNormalization($data, $format, $context);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $object = $this->inner->denormalize($data, $type, $format, $context);

        if ($object instanceof ValidateOnce) {
            $object->name = 'decorated';
        }

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $this->inner->supportsDenormalization($data, $type, $format, $context);
    }

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->inner->setSerializer($serializer);
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->inner->getSupportedTypes($format);
    }
}
