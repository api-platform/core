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

namespace ApiPlatform\Symfony\UriVariableTransformer;

use ApiPlatform\Api\UriVariableConverterInterface;
use ApiPlatform\Api\UriVariableTransformerInterface;
use ApiPlatform\Exception\InvalidUriVariableException;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * Transforms an ULID string to an instance of Symfony\Component\Uid\Ulid.
 */
final class UlidUriVariableTransformer implements UriVariableTransformerInterface, UriVariableConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform(mixed $value, array $types, array $context = []): Ulid
    {
        trigger_deprecation('api-platform/elasticsearch', '3.3', 'The "%s()" method is deprecated, use "TODO mtarld()" instead.', __METHOD__);

        try {
            return Ulid::fromString($value);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidUriVariableException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation(mixed $value, array $types, array $context = []): bool
    {
        trigger_deprecation('api-platform/elasticsearch', '3.3', 'The "%s()" method is deprecated, use "TODO mtarld()" instead.', __METHOD__);

        return \is_string($value) && is_a($types[0], Ulid::class, true);
    }

    public function convert(mixed $value, Type $type, array $context = []): Ulid
    {
        try {
            return Ulid::fromString($value);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidUriVariableException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function supportsConversion(mixed $value, Type $type, array $context = []): bool
    {
        if (!\is_string($value)) {
            return false;
        }

        return  && is_a($types[0], Ulid::class, true);
    }
}
