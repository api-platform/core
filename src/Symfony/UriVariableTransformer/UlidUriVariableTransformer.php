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

use ApiPlatform\Api\UriVariableTransformerInterface;
use ApiPlatform\Exception\InvalidUriVariableException;
use Symfony\Component\Uid\Ulid;

/**
 * Transforms an ULID string to an instance of Symfony\Component\Uid\Ulid.
 */
final class UlidUriVariableTransformer implements UriVariableTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value, array $types, array $context = [])
    {
        try {
            return Ulid::fromString($value);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidUriVariableException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($value, array $types, array $context = []): bool
    {
        return \is_string($value) && is_a($types[0], Ulid::class, true);
    }
}
