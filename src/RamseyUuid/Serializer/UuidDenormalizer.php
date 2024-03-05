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

namespace ApiPlatform\RamseyUuid\Serializer;

use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UuidDenormalizer implements DenormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): UuidInterface
    {
        try {
            return Uuid::fromString($data);
        } catch (InvalidUuidStringException $e) {
            throw NotNormalizableValueException::createForUnexpectedDataType($e->getMessage(), $data, ['uuid'], $context['deserialization_path'] ?? null, true, $e->getCode(), $e);
        }
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return \is_string($data) && is_a($type, UuidInterface::class, true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            UuidInterface::class => true,
        ];
    }
}
