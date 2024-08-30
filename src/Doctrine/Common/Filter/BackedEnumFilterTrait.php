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

namespace ApiPlatform\Doctrine\Common\Filter;

use ApiPlatform\Doctrine\Common\PropertyHelperTrait;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Trait for filtering the collection by backed enum values.
 *
 * Filters collection on equality of backed enum properties.
 *
 * For each property passed, if the resource does not have such property or if
 * the value is not one of cases the property is ignored.
 *
 * @author Rémi Marseille <marseille.remi@gmail.com>
 */
trait BackedEnumFilterTrait
{
    use PropertyHelperTrait;

    /**
     * @var array<string, string>
     */
    private array $enumTypes;

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        $properties = $this->getProperties();
        if (null === $properties) {
            $properties = array_fill_keys($this->getClassMetadata($resourceClass)->getFieldNames(), null);
        }

        foreach ($properties as $property => $unused) {
            if (!$this->isPropertyMapped($property, $resourceClass) || !$this->isBackedEnumField($property, $resourceClass)) {
                continue;
            }
            $propertyName = $this->normalizePropertyName($property);
            $description[$propertyName] = [
                'property' => $propertyName,
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => array_map(fn (\BackedEnum $case) => $case->value, $this->enumTypes[$property]::cases()),
                ],
            ];
        }

        return $description;
    }

    abstract protected function getProperties(): ?array;

    abstract protected function getLogger(): LoggerInterface;

    abstract protected function normalizePropertyName(string $property): string;

    /**
     * Determines whether the given property refers to a backed enum field.
     */
    abstract protected function isBackedEnumField(string $property, string $resourceClass): bool;

    private function normalizeValue($value, string $property): mixed
    {
        $values = array_map(fn (\BackedEnum $case) => $case->value, $this->enumTypes[$property]::cases());

        if (\in_array($value, $values, true)) {
            return $value;
        }

        $this->getLogger()->notice('Invalid filter ignored', [
            'exception' => new InvalidArgumentException(\sprintf('Invalid backed enum value for "%s" property, expected one of ( "%s" )',
                $property,
                implode('" | "', $values)
            )),
        ]);

        return null;
    }
}
