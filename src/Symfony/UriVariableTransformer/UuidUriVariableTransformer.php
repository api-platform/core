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
use Symfony\Component\Uid\Uuid;

/**
 * Transforms an UUID string to an instance of Symfony\Component\Uid\Uuid.
 */
final class UuidUriVariableTransformer implements UriVariableTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value, array $types, array $context = [])
    {
        try {
            return Uuid::fromString($value);
        } catch (\InvalidArgumentException|\ValueError $e) { // catching ValueError will not be necessary anymore when https://github.com/symfony/symfony/pull/39636 will be released
            throw new InvalidUriVariableException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($value, array $types, array $context = []): bool
    {
        return \is_string($value) && is_a($types[0], Uuid::class, true);
    }
}
