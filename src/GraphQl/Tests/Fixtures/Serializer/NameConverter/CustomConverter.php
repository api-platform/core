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

namespace ApiPlatform\GraphQl\Tests\Fixtures\Serializer\NameConverter;

use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * Custom converter that will only convert a property named "nameConverted"
 * with the same logic as Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter.
 */
class CustomConverter extends CamelCaseToSnakeCaseNameConverter
{
    public function normalize(string $propertyName): string
    {
        return 'nameConverted' === $propertyName ? parent::normalize($propertyName) : $propertyName;
    }

    public function denormalize(string $propertyName): string
    {
        return 'name_converted' === $propertyName ? parent::denormalize($propertyName) : $propertyName;
    }
}
