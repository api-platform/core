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
use Symfony\Component\Validator\Constraints\AtLeastOneOf;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class PropertySchemaOneOfRestriction implements PropertySchemaRestrictionMetadataInterface
{
    /**
     * @param iterable<PropertySchemaRestrictionMetadataInterface> $restrictionsMetadata
     */
    public function __construct(private readonly iterable $restrictionsMetadata = [])
    {
    }

    /**
     * {@inheritdoc}
     *
     * @param AtLeastOneOf $constraint
     */
    public function create(Constraint $constraint, ApiProperty $propertyMetadata): array
    {
        $oneOfConstraints = $constraint->constraints;
        $oneOfRestrictions = [];

        foreach ($oneOfConstraints as $oneOfConstraint) {
            foreach ($this->restrictionsMetadata as $restrictionMetadata) {
                if ($restrictionMetadata->supports($oneOfConstraint, $propertyMetadata) && !empty($oneOfRestriction = $restrictionMetadata->create($oneOfConstraint, $propertyMetadata))) {
                    $oneOfRestrictions[] = $oneOfRestriction;
                }
            }
        }

        if (!empty($oneOfRestrictions)) {
            return ['oneOf' => $oneOfRestrictions];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Constraint $constraint, ApiProperty $propertyMetadata): bool
    {
        return $constraint instanceof AtLeastOneOf;
    }
}
