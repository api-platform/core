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

namespace ApiPlatform\Core\Bridge\RamseyUuid\Identifier\Normalizer;

use ApiPlatform\Exception\InvalidIdentifierException;
use ApiPlatform\RamseyUuid\UriVariableTransformer\UuidUriVariableTransformer;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Denormalizes an UUID string to an instance of Ramsey\Uuid.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class UuidNormalizer implements DenormalizerInterface
{
    public function __construct()
    {
        trigger_deprecation('api-platform/core', '2.7', sprintf('The class "%s" will be replaced by "%s".', self::class, UuidUriVariableTransformer::class));
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed  $data
     * @param string $class
     * @param null   $format
     *
     * @throws InvalidIdentifierException
     *
     * @return mixed
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        try {
            return Uuid::fromString($data);
        } catch (InvalidUuidStringException $e) {
            throw new InvalidIdentifierException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return \is_string($data) && is_a($type, UuidInterface::class, true);
    }
}
