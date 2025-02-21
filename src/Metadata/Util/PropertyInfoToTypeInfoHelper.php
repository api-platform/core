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
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\IntersectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
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
            // hack to have generic without classname
            // this is required because some tests are using invalid data
            if (null === $class && 'object' === $builtinType) {
                $type = Type::object(\stdClass::class);
            }
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
        // BC layer for "symfony/type-info" < 7.2
        if (method_exists($type, 'asNonNullable')) {
            return (!$type instanceof UnionType) ? $type : $type->asNonNullable();
        }

        if (!$type instanceof NullableType) {
            return $type;
        }

        return $type->getWrappedType();
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

    /**
     * Converts a {@see Type} to what is should have been in the "symfony/property-info" component.
     *
     * @return list<LegacyType>|null
     */
    public static function convertTypeToLegacyTypes(?Type $type): ?array
    {
        if (null === $type) {
            return null;
        }

        if (\in_array((string) $type, ['mixed', 'never'], true)) {
            return null;
        }

        if (\in_array((string) $type, ['null', 'void'], true)) {
            return [new LegacyType('null')];
        }

        $legacyType = self::convertTypeToLegacy($type);

        if (!\is_array($legacyType)) {
            $legacyType = [$legacyType];
        }

        return $legacyType;
    }

    /**
     * Recursive method that converts {@see Type} to its related {@see LegacyType} (or list of {@see @LegacyType}).
     *
     * @return LegacyType|list<LegacyType>
     */
    private static function convertTypeToLegacy(Type $type): LegacyType|array
    {
        $nullable = false;

        if ($type instanceof NullableType) {
            $nullable = true;
            $type = $type->getWrappedType();
        }

        if ($type instanceof UnionType) {
            $unionTypes = [];
            foreach ($type->getTypes() as $t) {
                if ($t instanceof IntersectionType) {
                    throw new \LogicException(\sprintf('DNF types are not supported by "%s".', LegacyType::class));
                }

                if ($nullable) {
                    $t = Type::nullable($t);
                }

                $unionTypes[] = $t;
            }

            /** @var list<LegacyType> $legacyTypes */
            $legacyTypes = array_map(self::convertTypeToLegacy(...), $unionTypes);

            if (1 === \count($legacyTypes)) {
                return $legacyTypes[0];
            }

            return $legacyTypes;
        }

        if ($type instanceof IntersectionType) {
            /** @var list<LegacyType> $legacyTypes */
            $legacyTypes = array_map(self::convertTypeToLegacy(...), $type->getTypes());

            if (1 === \count($legacyTypes)) {
                return $legacyTypes[0];
            }

            return $legacyTypes;
        }

        if ($type instanceof CollectionType) {
            $type = $type->getWrappedType();
            if ($nullable) {
                $type = Type::nullable($type);
            }

            return self::convertTypeToLegacy($type);
        }

        $typeIdentifier = TypeIdentifier::MIXED;
        $className = null;
        $collectionKeyType = $collectionValueType = null;

        if ($type instanceof GenericType) {
            $wrappedType = $type->getWrappedType();

            if ($wrappedType instanceof BuiltinType) {
                $typeIdentifier = $wrappedType->getTypeIdentifier();
            } elseif ($wrappedType instanceof ObjectType) {
                $typeIdentifier = TypeIdentifier::OBJECT;
                $className = $wrappedType->getClassName();
            }

            $variableTypes = $type->getVariableTypes();

            if (2 === \count($variableTypes)) {
                if ('int|string' !== (string) $variableTypes[0]) {
                    $collectionKeyType = self::convertTypeToLegacy($variableTypes[0]);
                }
                $collectionValueType = self::convertTypeToLegacy($variableTypes[1]);
            } elseif (1 === \count($variableTypes)) {
                $collectionValueType = self::convertTypeToLegacy($variableTypes[0]);
            }
        } elseif ($type instanceof ObjectType) {
            $typeIdentifier = TypeIdentifier::OBJECT;
            $className = $type->getClassName();
        } elseif ($type instanceof BuiltinType) {
            $typeIdentifier = $type->getTypeIdentifier();
        }

        if (TypeIdentifier::MIXED === $typeIdentifier) {
            return [
                new LegacyType(LegacyType::BUILTIN_TYPE_INT, true),
                new LegacyType(LegacyType::BUILTIN_TYPE_FLOAT, true),
                new LegacyType(LegacyType::BUILTIN_TYPE_STRING, true),
                new LegacyType(LegacyType::BUILTIN_TYPE_BOOL, true),
                new LegacyType(LegacyType::BUILTIN_TYPE_RESOURCE, true),
                new LegacyType(LegacyType::BUILTIN_TYPE_OBJECT, true),
                new LegacyType(LegacyType::BUILTIN_TYPE_ARRAY, true),
                new LegacyType(LegacyType::BUILTIN_TYPE_NULL, true),
                new LegacyType(LegacyType::BUILTIN_TYPE_CALLABLE, true),
                new LegacyType(LegacyType::BUILTIN_TYPE_ITERABLE, true),
            ];
        }

        return new LegacyType(
            builtinType: $typeIdentifier->value,
            nullable: $nullable,
            class: $className,
            collection: $type instanceof GenericType,
            collectionKeyType: $collectionKeyType,
            collectionValueType: $collectionValueType,
        );
    }
}
