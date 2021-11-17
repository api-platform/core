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
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Count;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
class PropertySchemaCountRestriction implements PropertySchemaRestrictionMetadataInterface
{
    /**
     * {@inheritdoc}
     *
     * @param Count $constraint
     */
    public function create(Constraint $constraint, ApiProperty $propertyMetadata): array
    {
        $restriction = [];

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
        return $constraint instanceof Count;
    }
}
