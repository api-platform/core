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
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
final class PropertySchemaGreaterThanOrEqualRestriction implements PropertySchemaRestrictionMetadataInterface
{
    /**
     * {@inheritdoc}
     *
     * @param GreaterThanOrEqual $constraint
     */
    public function create(Constraint $constraint, ApiProperty $propertyMetadata): array
    {
        return [
            'minimum' => $constraint->value,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Constraint $constraint, ApiProperty $propertyMetadata): bool
    {
        return $constraint instanceof GreaterThanOrEqual && is_numeric($constraint->value) && ($type = $propertyMetadata->getBuiltinTypes()[0] ?? null) && \in_array($type->getBuiltinType(), [Type::BUILTIN_TYPE_INT, Type::BUILTIN_TYPE_FLOAT], true);
    }
}
