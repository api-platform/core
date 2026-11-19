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

        $types = array_values(array_unique(array_map(static fn (mixed $choice) => \is_string($choice) ? 'string' : 'number', $choices)));

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

        $nativeType = $propertyMetadata->getExtraProperties()['nested_schema'] ?? false
            ? Type::string()
            : $propertyMetadata->getNativeType();

        $isValidScalarType = static fn (Type $t): bool => $t->isSatisfiedBy(
            static fn (Type $subType): bool => $subType->isIdentifiedBy(TypeIdentifier::STRING, TypeIdentifier::INT, TypeIdentifier::FLOAT)
        );

        if ($isValidScalarType($nativeType)) {
            return true;
        }

        if ($nativeType->isSatisfiedBy(static fn ($t) => $t instanceof CollectionType)) {
            if (null !== ($collectionValueType = TypeHelper::getCollectionValueType($nativeType)) && $isValidScalarType($collectionValueType)) {
                return true;
            }
        }

        return false;
    }
}
