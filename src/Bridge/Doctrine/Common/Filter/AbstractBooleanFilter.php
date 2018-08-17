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
use Doctrine\DBAL\Types\Type as DBALType;

/**
 * Abstract class for filtering the collection by boolean values.
 *
 * Filters collection on equality of boolean properties. The value is specified
 * as one of ( "true" | "false" | "1" | "0" ) in the query.
 *
 * For each property passed, if the resource does not have such property or if
 * the value is not one of ( "true" | "false" | "1" | "0" ) the property is ignored.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
abstract class AbstractBooleanFilter extends AbstractContextAwareFilter
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
            if (!$this->isPropertyMapped($property, $resourceClass) || !$this->isBooleanField($property, $resourceClass)) {
                continue;
            }

            $description[$property] = [
                'property' => $property,
                'type' => 'bool',
                'required' => false,
            ];
        }

        return $description;
    }

    /**
     * Determines whether the given property refers to a boolean field.
     */
    protected function isBooleanField(string $property, string $resourceClass): bool
    {
        $propertyParts = $this->splitPropertyParts($property, $resourceClass);
        $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);

        return DBALType::BOOLEAN === $metadata->getTypeOfField($propertyParts['field']);
    }

    /**
     * @return bool|null
     */
    protected function normalizeValue($value)
    {
        if (\in_array($value, [true, 'true', '1'], true)) {
            $value = true;
        } elseif (\in_array($value, [false, 'false', '0'], true)) {
            $value = false;
        } else {
            $this->logger->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(sprintf('Invalid boolean value for "%s" property, expected one of ( "%s" )', $property, implode('" | "', [
                    'true',
                    'false',
                    '1',
                    '0',
                ]))),
            ]);

            $value = null;
        }

        return $value;
    }
}
