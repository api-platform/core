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
use ApiPlatform\Symfony\UriVariableTransformer\UuidUriVariableTransformer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Denormalizes an UUID string to an instance of Symfony\Component\Uid\Uuid.
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
     * @return mixed
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        try {
            return Uuid::fromString($data);
        } catch (\InvalidArgumentException|\ValueError $e) { // catching ValueError will not be necessary anymore when https://github.com/symfony/symfony/pull/39636 will be released
            throw new InvalidIdentifierException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return \is_string($data) && is_a($type, Uuid::class, true);
    }
}
