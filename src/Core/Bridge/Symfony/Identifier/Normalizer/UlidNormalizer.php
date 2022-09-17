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

namespace ApiPlatform\Core\Bridge\Symfony\Identifier\Normalizer;

use ApiPlatform\Exception\InvalidIdentifierException;
use ApiPlatform\Symfony\UriVariableTransformer\UlidUriVariableTransformer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Denormalizes an ULID string to an instance of Symfony\Component\Uid\Ulid.
 */
final class UlidNormalizer implements DenormalizerInterface
{
    public function __construct()
    {
        trigger_deprecation('api-platform/core', '2.7', sprintf('The class "%s" will be replaced by "%s".', self::class, UlidUriVariableTransformer::class));
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        try {
            return Ulid::fromString($data);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidIdentifierException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return \is_string($data) && is_a($type, Ulid::class, true);
    }
}
