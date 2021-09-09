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

namespace ApiPlatform\Api\UriVariableTransformer;

use ApiPlatform\Api\UriVariableTransformerInterface;
use Symfony\Component\PropertyInfo\Type;

final class IntegerUriVariableTransformer implements UriVariableTransformerInterface
{
    public function transform($value, array $types, array $context = [])
    {
        return (int) $value;
    }

    public function supportsTransformation($value, array $types, array $context = []): bool
    {
        return Type::BUILTIN_TYPE_INT === $types[0] && \is_string($value);
    }
}
