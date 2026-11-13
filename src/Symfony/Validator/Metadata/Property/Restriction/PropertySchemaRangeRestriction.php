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
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Range;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
final class PropertySchemaRangeRestriction implements PropertySchemaRestrictionMetadataInterface
{
    /**
     * {@inheritdoc}
     *
     * @param Range $constraint
     */
    public function create(Constraint $constraint, ApiProperty $propertyMetadata): array
    {
        $restriction = [];

        if (isset($constraint->min) && is_numeric($constraint->min)) {
            $restriction['minimum'] = $constraint->min;
        }

        if (isset($constraint->max) && is_numeric($constraint->max)) {
            $restriction['maximum'] = $constraint->max;
        }

        return $restriction;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Constraint $constraint, ApiProperty $propertyMetadata): bool
    {
        if (!$constraint instanceof Range) {
            return false;
        }

        $type = $propertyMetadata->getExtraProperties()['nested_schema'] ?? false
            ? Type::int()
            : $propertyMetadata->getNativeType();

        return $type->isIdentifiedBy(TypeIdentifier::INT, TypeIdentifier::FLOAT);
    }
}
