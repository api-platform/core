<?php

namespace Dunglas\ApiBundle\Tests\Behat\TestBundle\Serializer\NameConverter;

use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * Custom converter that will only convert a property named "nameConverted"
 * with the same logic as Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter.
 */
class CustomConverter extends CamelCaseToSnakeCaseNameConverter
{
    public function normalize($propertyName)
    {
        return 'nameConverted' === $propertyName ? parent::normalize($propertyName) : $propertyName;
    }

    public function denormalize($propertyName)
    {
        return 'nameConverted' === $propertyName ? parent::denormalize($propertyName) : $propertyName;
    }
}
