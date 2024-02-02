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

namespace ApiPlatform\Elasticsearch\Serializer\NameConverter;

use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Converts inner fields with a inner name converter.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class InnerFieldsNameConverter implements AdvancedNameConverterInterface
{
    public function __construct(private readonly NameConverterInterface $inner = new CamelCaseToSnakeCaseNameConverter())
    {
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
    {
        return $this->convertInnerFields($propertyName, true, $class, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
    {
        return $this->convertInnerFields($propertyName, false, $class, $format, $context);
    }

    private function convertInnerFields(string $propertyName, bool $normalization, ?string $class = null, ?string $format = null, array $context = []): string
    {
        $convertedProperties = [];

        foreach (explode('.', $propertyName) as $decomposedProperty) {
            $convertedProperties[] = $this->inner->{$normalization ? 'normalize' : 'denormalize'}($decomposedProperty, $class, $format, $context);
        }

        return implode('.', $convertedProperties);
    }
}
