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

namespace ApiPlatform\Core\Bridge\Doctrine\Common\Filter;

use ApiPlatform\Core\Exception\InvalidArgumentException;

/**
 * Trait for filtering the collection by numeric values.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
trait NumericFilterTrait
{
    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        $properties = $this->properties;
        if (null === $properties) {
            $properties = array_fill_keys($this->getClassMetadata($resourceClass)->getFieldNames(), null);
        }

        foreach ($properties as $property => $unused) {
            if (!$this->isPropertyMapped($property, $resourceClass) || !$this->isNumericField($property, $resourceClass)) {
                continue;
            }

            $filterParameterNames = [$property, $property.'[]'];

            foreach ($filterParameterNames as $filterParameterName) {
                $description[$filterParameterName] = [
                    'property' => $property,
                    'type' => $this->getType((string) $this->getDoctrineFieldType($property, $resourceClass)),
                    'required' => false,
                    'is_collection' => '[]' === substr($filterParameterName, -2),
                ];
            }
        }

        return $description;
    }

    /**
     * Determines whether the given property refers to a numeric field.
     */
    private function isNumericField(string $property, string $resourceClass): bool
    {
        return isset(self::DOCTRINE_NUMERIC_TYPES[(string) $this->getDoctrineFieldType($property, $resourceClass)]);
    }

    private function normalizeValues($value, string $property): ?array
    {
        if (!is_numeric($value) && (!\is_array($value) || !$this->isNumericArray($value))) {
            $this->logger->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('Invalid numeric value for "%s" property', $property)),
            ]);

            return null;
        }

        $values = (array) $value;

        foreach ($values as $key => $val) {
            if (!\is_int($key)) {
                unset($values[$key]);
            }
        }

        if (empty($values)) {
            $this->logger->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('At least one value is required, multiple values should be in "%1$s[]=firstvalue&%1$s[]=secondvalue" format', $property)),
            ]);

            return null;
        }

        return array_values($values);
    }

    private function isNumericArray(array $values): bool
    {
        foreach ($values as $value) {
            if (!is_numeric($value)) {
                return false;
            }
        }

        return true;
    }
}
