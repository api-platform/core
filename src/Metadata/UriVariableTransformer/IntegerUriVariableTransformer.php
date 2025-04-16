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

namespace ApiPlatform\Metadata\UriVariableTransformer;

use ApiPlatform\Metadata\UriVariableTransformerInterface;
use Symfony\Component\TypeInfo\TypeIdentifier;

final class IntegerUriVariableTransformer implements UriVariableTransformerInterface
{
    public function transform(mixed $value, array $types, array $context = []): int
    {
        return (int) $value;
    }

    public function supportsTransformation(mixed $value, array $types, array $context = []): bool
    {
        return TypeIdentifier::INT->value === $types[0] && \is_string($value);
    }
}
