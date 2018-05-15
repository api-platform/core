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

namespace ApiPlatform\Core\Identifier\Normalizer;

use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class IntegerDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = []): int
    {
        return (int) $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return Type::BUILTIN_TYPE_INT === $type && \is_string($data);
    }
}
