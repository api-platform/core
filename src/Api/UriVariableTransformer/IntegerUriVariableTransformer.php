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

use ApiPlatform\Api\UriVariableConverterInterface;
use ApiPlatform\Api\UriVariableTransformerInterface;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;

final class IntegerUriVariableTransformer implements UriVariableTransformerInterface, UriVariableConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform(mixed $value, array $types, array $context = []): int
    {
        trigger_deprecation('api-platform/elasticsearch', '3.3', 'The "%s()" method is deprecated, use "TODO mtarld()" instead.', __METHOD__);

        return (int) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation(mixed $value, array $types, array $context = []): bool
    {
        trigger_deprecation('api-platform/elasticsearch', '3.3', 'The "%s()" method is deprecated, use "TODO mtarld()" instead.', __METHOD__);

        return LegacyType::BUILTIN_TYPE_INT === $types[0] && \is_string($value);
    }

    public function convert(mixed $value, Type $type, array $context = []): int
    {
        return (int) $value;
    }

    public function supportsConversion(mixed $value, Type $type, array $context = []): bool
    {
        return $type->isA(TypeIdentifier::INT) && \is_string($value);
    }
}
