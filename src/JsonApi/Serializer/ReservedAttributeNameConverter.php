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

namespace ApiPlatform\JsonApi\Serializer;

use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Reserved attribute name converter.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class ReservedAttributeNameConverter implements AdvancedNameConverterInterface
{
    public const JSON_API_RESERVED_ATTRIBUTES = [
        'id' => '_id',
        'type' => '_type',
        'links' => '_links',
        'relationships' => '_relationships',
        'included' => '_included',
    ];

    public function __construct(private readonly ?NameConverterInterface $nameConverter = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
    {
        if (null !== $this->nameConverter) {
            $propertyName = $this->nameConverter->normalize($propertyName, $class, $format, $context);
        }

        if (isset(self::JSON_API_RESERVED_ATTRIBUTES[$propertyName])) {
            $propertyName = self::JSON_API_RESERVED_ATTRIBUTES[$propertyName];
        }

        return $propertyName;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
    {
        if (\in_array($propertyName, self::JSON_API_RESERVED_ATTRIBUTES, true)) {
            $propertyName = substr($propertyName, 1);
        }

        if (null !== $this->nameConverter) {
            $propertyName = $this->nameConverter->denormalize($propertyName, $class, $format, $context);
        }

        return $propertyName;
    }
}
