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
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GreaterThan;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
final class PropertySchemaGreaterThanRestriction implements PropertySchemaRestrictionMetadataInterface
{
    /**
     * {@inheritdoc}
     *
     * @param GreaterThan $constraint
     */
    public function create(Constraint $constraint, ApiProperty $propertyMetadata): array
    {
        return [
            'exclusiveMinimum' => $constraint->value,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Constraint $constraint, ApiProperty $propertyMetadata): bool
    {
        if (!$constraint instanceof GreaterThan) {
            return false;
        }

        if (!is_numeric($constraint->value)) {
            return false;
        }

        $builtinType = $propertyMetadata->getBuiltinTypes();
        if (!$builtinType) {
            return false;
        }

        // BC layer for "symfony/property-info" < 7.1
        if (is_array($builtinType)) {
            $types = array_map(fn (LegacyType $type): string => $type->getBuiltinType(), $builtinType);

            return \count($types) && array_intersect($types, [LegacyType::BUILTIN_TYPE_INT, LegacyType::BUILTIN_TYPE_FLOAT]);
        }

        return $builtinType->isA(TypeIdentifier::INT) || $builtinType->isA(TypeIdentifier::FLOAT);
    }
}
