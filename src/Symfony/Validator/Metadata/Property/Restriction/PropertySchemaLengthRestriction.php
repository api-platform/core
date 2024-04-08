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
use Symfony\Component\Validator\Constraints\Length;

/**
 * Class PropertySchemaLengthRestrictions.
 *
 * @author Andrii Penchuk penja7@gmail.com
 */
class PropertySchemaLengthRestriction implements PropertySchemaRestrictionMetadataInterface
{
    /**
     * {@inheritdoc}
     *
     * @param Length $constraint
     */
    public function create(Constraint $constraint, ApiProperty $propertyMetadata): array
    {
        $restriction = [];

        if (isset($constraint->min)) {
            $restriction['minLength'] = (int) $constraint->min;
        }

        if (isset($constraint->max)) {
            $restriction['maxLength'] = (int) $constraint->max;
        }

        return $restriction;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Constraint $constraint, ApiProperty $propertyMetadata): bool
    {
        if (!$constraint instanceof Length) {
            return false;
        }

        $builtinType = $propertyMetadata->getBuiltinTypes();
        if (!$builtinType) {
            return false;
        }

        // BC layer for "symfony/property-info" < 7.1
        if (is_array($builtinType)) {
            $types = array_map(fn (LegacyType $type): string => $type->getBuiltinType(), $builtinType);

            return \count($types) && in_array(LegacyType::BUILTIN_TYPE_STRING, $types, true);
        }

        return $builtinType->isA(TypeIdentifier::STRING);
    }
}
