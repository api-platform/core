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
use ApiPlatform\Metadata\Util\TypeHelper;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\TypeIdentifier;
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

        $types = array_values(array_unique(array_map(fn (mixed $choice) => \is_string($choice) ? 'string' : 'number', $choices)));

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

    public function supports(Constraint $constraint, ApiProperty $propertyMetadata): bool
    {
        if (!$constraint instanceof Choice) {
            return false;
        }

        if (method_exists(PropertyInfoExtractor::class, 'getType')) {
            $nativeType = $propertyMetadata->getExtraProperties()['nested_schema'] ?? false
                ? Type::string()
                : $propertyMetadata->getNativeType();

            $isValidScalarType = fn (Type $t): bool => $t->isSatisfiedBy(
                fn (Type $subType): bool => $subType->isIdentifiedBy(TypeIdentifier::STRING, TypeIdentifier::INT, TypeIdentifier::FLOAT)
            );

            if ($isValidScalarType($nativeType)) {
                return true;
            }

            if ($nativeType->isSatisfiedBy(fn ($t) => $t instanceof CollectionType)) {
                if (null !== ($collectionValueType = TypeHelper::getCollectionValueType($nativeType)) && $isValidScalarType($collectionValueType)) {
                    return true;
                }
            }

            return false;
        }

        $types = array_map(static fn (LegacyType $type) => $type->getBuiltinType(), $propertyMetadata->getBuiltinTypes() ?? []);
        if ($propertyMetadata->getExtraProperties()['nested_schema'] ?? false) {
            $types = [LegacyType::BUILTIN_TYPE_STRING];
        }

        if (
            null !== ($builtinType = ($propertyMetadata->getBuiltinTypes()[0] ?? null))
            && $builtinType->isCollection()
            && \count($builtinType->getCollectionValueTypes()) > 0
        ) {
            $types = array_unique(array_merge($types, array_map(static fn (LegacyType $type) => $type->getBuiltinType(), $builtinType->getCollectionValueTypes())));
        }

        return \count($types) > 0 && \count(array_intersect($types, [LegacyType::BUILTIN_TYPE_STRING, LegacyType::BUILTIN_TYPE_INT, LegacyType::BUILTIN_TYPE_FLOAT])) > 0;
    }
}
