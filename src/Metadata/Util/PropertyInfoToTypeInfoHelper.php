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

namespace ApiPlatform\Metadata\Util;

use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\TypeInfo\Exception\InvalidArgumentException;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * A helper about PropertyInfo Type conversion.
 *
 * @see https://github.com/mtarld/symfony/commits/backup/chore/deprecate-property-info-type/
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class PropertyInfoToTypeInfoHelper
{
    /**
     * Converts a {@see LegacyType} to what is should have been in the "symfony/type-info" component.
     *
     * @param list<LegacyType>|null $legacyTypes
     */
    public static function convertLegacyTypesToType(?array $legacyTypes): ?Type
    {
        if (!$legacyTypes) {
            return null;
        }

        $types = [];
        $nullable = false;

        foreach (array_map(self::convertLegacyTypeToType(...), $legacyTypes) as $type) {
            if ($type->isNullable()) {
                $nullable = true;

                if ($type instanceof BuiltinType && TypeIdentifier::NULL === $type->getTypeIdentifier()) {
                    continue;
                }

                $type = self::unwrapNullableType($type);
            }

            if ($type instanceof UnionType) {
                $types = [$types, ...$type->getTypes()];

                continue;
            }

            $types[] = $type;
        }

        if ($nullable && [] === $types) {
            return Type::null();
        }

        $type = \count($types) > 1 ? Type::union(...$types) : $types[0];
        if ($nullable) {
            $type = Type::nullable($type);
        }

        return $type;
    }

    /**
     * @param list<LegacyType> $collectionKeyTypes
     * @param list<LegacyType> $collectionValueTypes
     */
    public static function createTypeFromLegacyValues(string $builtinType, bool $nullable, ?string $class, bool $collection, array $collectionKeyTypes, array $collectionValueTypes): Type
    {
        $variableTypes = [];

        if ($collectionKeyTypes) {
            $collectionKeyTypes = array_unique(array_map(self::convertLegacyTypeToType(...), $collectionKeyTypes));
            $variableTypes[] = \count($collectionKeyTypes) > 1 ? Type::union(...$collectionKeyTypes) : $collectionKeyTypes[0];
        }

        if ($collectionValueTypes) {
            if (!$collectionKeyTypes) {
                $variableTypes[] = \is_array($collectionKeyTypes) ? Type::mixed() : Type::union(Type::int(), Type::string()); // @phpstan-ignore-line
            }

            $collectionValueTypes = array_unique(array_map(self::convertLegacyTypeToType(...), $collectionValueTypes));
            $variableTypes[] = \count($collectionValueTypes) > 1 ? Type::union(...$collectionValueTypes) : $collectionValueTypes[0];
        }

        if ($collectionKeyTypes && !$collectionValueTypes) {
            $variableTypes[] = Type::mixed();
        }

        try {
            $type = null !== $class ? Type::object($class) : Type::builtin(TypeIdentifier::from($builtinType));
        } catch (\ValueError) {
            throw new InvalidArgumentException(\sprintf('"%s" is not a valid PHP type.', $builtinType));
        }

        if (\count($variableTypes)) {
            $type = Type::generic($type, ...$variableTypes);
        }

        if ($collection) {
            $type = Type::collection($type);
        }

        if ($nullable && !$type->isNullable()) {
            $type = Type::nullable($type);
        }

        return $type;
    }

    public static function unwrapNullableType(Type $type): Type
    {
        if (!$type instanceof UnionType) {
            return $type;
        }

        return $type->asNonNullable();
    }

    /**
     * Recursive method that converts {@see LegacyType} to its related {@see Type}.
     */
    private static function convertLegacyTypeToType(LegacyType $legacyType): Type
    {
        return self::createTypeFromLegacyValues(
            $legacyType->getBuiltinType(),
            $legacyType->isNullable(),
            $legacyType->getClassName(),
            $legacyType->isCollection(),
            $legacyType->getCollectionKeyTypes(),
            $legacyType->getCollectionValueTypes(),
        );
    }
}
