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

namespace ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction;

use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
final class PropertySchemaCollectionRestriction implements PropertySchemaRestrictionMetadataInterface
{
    /**
     * @var iterable<PropertySchemaRestrictionMetadataInterface>
     */
    private $restrictionsMetadata;

    /**
     * @param iterable<PropertySchemaRestrictionMetadataInterface> $restrictionsMetadata
     */
    public function __construct(iterable $restrictionsMetadata = [])
    {
        $this->restrictionsMetadata = $restrictionsMetadata;
    }

    /**
     * {@inheritdoc}
     *
     * @param Collection $constraint
     */
    public function create(Constraint $constraint, PropertyMetadata $propertyMetadata): array
    {
        $restriction = [
            'type' => 'object',
            'properties' => [],
            'additionalProperties' => $constraint->allowExtraFields,
        ];
        $required = [];

        foreach ($constraint->fields as $field => $baseConstraint) {
            /** @var Required|Optional $baseConstraint */
            if ($baseConstraint instanceof Required) {
                $required[] = $field;
            }

            $restriction['properties'][$field] = $this->mergeConstraintRestrictions($baseConstraint, $propertyMetadata);
        }

        if ($required) {
            $restriction['required'] = $required;
        }

        return $restriction;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Constraint $constraint, PropertyMetadata $propertyMetadata): bool
    {
        return $constraint instanceof Collection;
    }

    /**
     * @param Required|Optional $constraint
     */
    private function mergeConstraintRestrictions(Constraint $constraint, PropertyMetadata $propertyMetadata): array
    {
        $propertyRestrictions = [['type' => 'string']];
        $nestedConstraints = method_exists($constraint, 'getNestedContraints') ? $constraint->getNestedContraints() : $constraint->constraints;

        foreach ($nestedConstraints as $nestedConstraint) {
            if ($nestedConstraint instanceof Type) {
                /** @var string $nestedType */
                $nestedType = $nestedConstraint->type;
                $propertyRestrictions[0]['type'] = $this->getConstraintType($nestedType);
            }

            foreach ($this->restrictionsMetadata as $restrictionMetadata) {
                if ($restrictionMetadata->supports($nestedConstraint, $propertyMetadata) && !empty($nestedConstraintRestriction = $restrictionMetadata->create($nestedConstraint, $propertyMetadata))) {
                    $propertyRestrictions[] = $nestedConstraintRestriction;
                }
            }
        }

        return array_merge([], ...$propertyRestrictions);
    }

    private function getConstraintType(string $type): string
    {
        if (\in_array($type, ['bool', 'boolean'], true)) {
            return 'boolean';
        }

        if (\in_array($type, ['int', 'integer', 'long'], true)) {
            return 'integer';
        }

        if (\in_array($type, ['float', 'double', 'real', 'numeric'], true)) {
            return 'number';
        }

        if (\in_array($type, ['array', 'iterable'], true)) {
            return 'array';
        }

        if ('object' === $type) {
            return 'object';
        }

        return 'string';
    }
}
