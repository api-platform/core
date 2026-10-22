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

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Reserved attribute name converter.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class ReservedAttributeNameConverter implements NameConverterInterface
{
    public const JSON_API_RESERVED_ATTRIBUTES = [
        'id' => '_id',
        'type' => '_type',
        'links' => '_links',
        'relationships' => '_relationships',
        'included' => '_included',
    ];

    public const STRICT_JSON_API_RESERVED_ATTRIBUTES = [
        'id' => 'resourceId',
        'type' => 'resourceType',
    ];

    public function __construct(private readonly ?NameConverterInterface $nameConverter = null, private readonly bool $strictMemberNames = false)
    {
    }

    /**
     * @return array<string, string>
     */
    public static function getReservedAttributes(bool $strictMemberNames = false): array
    {
        return $strictMemberNames ? self::STRICT_JSON_API_RESERVED_ATTRIBUTES : self::JSON_API_RESERVED_ATTRIBUTES;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
    {
        if (null !== $this->nameConverter) {
            $propertyName = $this->nameConverter->normalize($propertyName, $class, $format, $context);
        }

        if ($class && is_a($class, ProblemExceptionInterface::class, true)) {
            return $propertyName;
        }

        $reserved = self::getReservedAttributes($this->strictMemberNames);
        if (isset($reserved[$propertyName])) {
            $propertyName = $reserved[$propertyName];
        }

        return $propertyName;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
    {
        $original = array_search($propertyName, self::getReservedAttributes($this->strictMemberNames), true);
        if (false !== $original) {
            $propertyName = $original;
        }

        if (null !== $this->nameConverter) {
            $propertyName = $this->nameConverter->denormalize($propertyName, $class, $format, $context);
        }

        return $propertyName;
    }
}
