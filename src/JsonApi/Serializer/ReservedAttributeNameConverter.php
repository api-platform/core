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

namespace ApiPlatform\Core\JsonApi\Serializer;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Reserved attribute name converter.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class ReservedAttributeNameConverter implements NameConverterInterface
{
    const JSON_API_RESERVED_ATTRIBUTES = [
        'id' => '_id',
        'type' => '_type',
        'links' => '_links',
        'relationships' => '_relationships',
    ];

    private $nameConverter;

    public function __construct(NameConverterInterface $nameConverter = null)
    {
        $this->nameConverter = $nameConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($propertyName)
    {
        if (null !== $this->nameConverter) {
            $propertyName = $this->nameConverter->normalize($propertyName);
        }

        if (isset(self::JSON_API_RESERVED_ATTRIBUTES[$propertyName])) {
            $propertyName = self::JSON_API_RESERVED_ATTRIBUTES[$propertyName];
        }

        return $propertyName;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($propertyName)
    {
        if (in_array($propertyName, self::JSON_API_RESERVED_ATTRIBUTES, true)) {
            $propertyName = substr($propertyName, 1);
        }

        if (null !== $this->nameConverter) {
            $propertyName = $this->nameConverter->denormalize($propertyName);
        }

        return $propertyName;
    }
}
