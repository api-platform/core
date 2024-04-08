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

namespace ApiPlatform\Symfony\Validator\Metadata\Property\Restriction;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\TypeInfo\Type\IntersectionType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
final class PropertySchemaChoiceRestriction implements PropertySchemaRestrictionMetadataInterface
{
    /**
     * {@inheritdoc}
     *
     * @param Choice $constraint
     */
    public function create(Constraint $constraint, ApiProperty $propertyMetadata): array
    {
        $choices = [];

        if (\is_callable($constraint->callback)) {
            $choices = ($constraint->callback)();
        } elseif (\is_array($constraint->choices)) {
            $choices = $constraint->choices;
        }

        if (!$choices) {
            return [];
        }

        $restriction = [];

        if (!$constraint->multiple) {
            $restriction['enum'] = $choices;

            return $restriction;
        }

        $restriction['type'] = 'array';

        $builtinType = $propertyMetadata->getBuiltinTypes();
        $types = [];

        // BC layer for "symfony/property-info" < 7.1
        if (is_array($builtinType)) {
            $types = array_unique(array_map(fn (LegacyType $type) => 'string' === $type->getBuiltinType() ? 'string' : 'number', $builtinType));
        } elseif ($builtinType instanceof Type) {
            $types = array_values(array_unique(array_map(
                fn (Type $type) => $type->isA(TypeIdentifier::STRING) ? 'string' : 'number',
                $builtinType instanceof UnionType || $builtinType instanceof IntersectionType ? $builtinType->getTypes() : [$builtinType],
            )));
        }

        if ($count = \count($types)) {
            if (1 === $count) {
                $types = $types[0];
            }

            $restriction['items'] = ['type' => $types, 'enum' => $choices];
        }

        if (null !== $constraint->min) {
            $restriction['minItems'] = $constraint->min;
        }

        if (null !== $constraint->max) {
            $restriction['maxItems'] = $constraint->max;
        }

        return $restriction;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Constraint $constraint, ApiProperty $propertyMetadata): bool
    {
        if (!$constraint instanceof Choice) {
            return false;
        }

        $builtinType = $propertyMetadata->getBuiltinTypes();
        if (!$builtinType) {
            return false;
        }

        // BC layer for "symfony/property-info" < 7.1
        if (is_array($builtinType)) {
            $types = array_map(fn (LegacyType $type) => $type->getBuiltinType(), $builtinType);

            return $constraint instanceof Choice && \count($types) && array_intersect($types, [LegacyType::BUILTIN_TYPE_STRING, LegacyType::BUILTIN_TYPE_INT, LegacyType::BUILTIN_TYPE_FLOAT]);
        }

        return $builtinType->isA(TypeIdentifier::STRING) || $builtinType->isA(TypeIdentifier::INT) || $builtinType->isA(TypeIdentifier::FLOAT);
    }
}
