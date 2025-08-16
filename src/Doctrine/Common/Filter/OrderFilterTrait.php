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

/**
 * Trait for ordering the collection by given properties.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
trait OrderFilterTrait
{
    use PropertyHelperTrait;

    /**
     * @var string Keyword used to retrieve the value
     */
    protected string $orderParameterName;

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        $properties = $this->getProperties();
        if (null === $properties && $fieldNames = $this->getClassMetadata($resourceClass)->getFieldNames()) {
            $properties = array_fill_keys($fieldNames, null);
        }

        foreach ($properties ?? [] as $property => $propertyOptions) {
            if (!$this->isPropertyMapped($property, $resourceClass)) {
                continue;
            }
            $propertyName = $this->normalizePropertyName($property);
            $description[\sprintf('%s[%s]', $this->orderParameterName, $propertyName)] = [
                'property' => $propertyName,
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'default' => strtolower($propertyOptions['default_direction'] ?? OrderFilterInterface::DIRECTION_ASC),
                    'enum' => [
                        strtolower(OrderFilterInterface::DIRECTION_ASC),
                        strtolower(OrderFilterInterface::DIRECTION_DESC),
                    ],
                ],
            ];
        }

        return $description;
    }

    abstract protected function getProperties(): ?array;

    abstract protected function normalizePropertyName(string $property): string;

    private function normalizeValue(mixed $value, string $property): ?string
    {
        if (empty($value) && null !== $defaultDirection = $this->getProperties()[$property]['default_direction'] ?? null) {
            // fallback to default direction
            $value = $defaultDirection;
        }

        if (!\is_string($value)) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(\sprintf('Invalid string value for "%s" property', $property)),
            ]);

            return null;
        }

        $value = strtoupper($value);
        if (!\in_array($value, [self::DIRECTION_ASC, self::DIRECTION_DESC], true)) {
            return null;
        }

        return $value;
    }
}
