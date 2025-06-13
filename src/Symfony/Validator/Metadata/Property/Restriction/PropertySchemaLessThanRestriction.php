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
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\LessThan;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
final class PropertySchemaLessThanRestriction implements PropertySchemaRestrictionMetadataInterface
{
    /**
     * {@inheritdoc}
     *
     * @param LessThan $constraint
     */
    public function create(Constraint $constraint, ApiProperty $propertyMetadata): array
    {
        return [
            'maximum' => $constraint->value,
            'exclusiveMaximum' => $constraint->value,
        ];
    }

    public function supports(Constraint $constraint, ApiProperty $propertyMetadata): bool
    {
        if (!$constraint instanceof LessThan || !is_numeric($constraint->value)) {
            return false;
        }

        if (method_exists(PropertyInfoExtractor::class, 'getType')) {
            $type = $propertyMetadata->getExtraProperties()['nested_schema'] ?? false
                ? Type::int()
                : $propertyMetadata->getNativeType();

            return $type->isIdentifiedBy(TypeIdentifier::INT, TypeIdentifier::FLOAT);
        }

        $types = array_map(fn (LegacyType $type) => $type->getBuiltinType(), $propertyMetadata->getBuiltinTypes() ?? []);
        if ($propertyMetadata->getExtraProperties()['nested_schema'] ?? false) {
            $types = [LegacyType::BUILTIN_TYPE_INT];
        }

        return \count($types) > 0 && \count(array_intersect($types, [LegacyType::BUILTIN_TYPE_INT, LegacyType::BUILTIN_TYPE_FLOAT])) > 0;
    }
}
