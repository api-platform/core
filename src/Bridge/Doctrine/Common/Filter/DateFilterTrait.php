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

use ApiPlatform\Core\Bridge\Doctrine\Common\PropertyHelperTrait;

/**
 * Trait for filtering the collection by date intervals.
 *
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
trait DateFilterTrait
{
    use PropertyHelperTrait;

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass/*, array $context = []*/): array
    {
        if (\func_num_args() < 2 && __CLASS__ !== \get_class($this) && __CLASS__ !== (new \ReflectionMethod($this, __FUNCTION__))->getDeclaringClass()->getName()) {
            @trigger_error(sprintf('Method %s() will have a second `$context` argument in version API Platform 3.0. Not defining it is deprecated since API Platform 2.4.', __FUNCTION__), E_USER_DEPRECATED);
        }

        $context = 1 < \func_num_args() ? (array) func_get_arg(1) : [];
        $description = [];

        $properties = $this->getProperties();
        if (null === $properties) {
            $properties = array_fill_keys($this->getClassMetadata($resourceClass)->getFieldNames(), null);
        }

        foreach ($properties as $property => $nullManagement) {
            if (
                !$this->isPropertyEnabled($property, $resourceClass, $context) ||
                !$this->isPropertyMapped($property, $resourceClass) ||
                !$this->isDateField($property, $resourceClass)
            ) {
                continue;
            }

            $description += $this->getFilterDescription($property, self::PARAMETER_BEFORE);
            $description += $this->getFilterDescription($property, self::PARAMETER_STRICTLY_BEFORE);
            $description += $this->getFilterDescription($property, self::PARAMETER_AFTER);
            $description += $this->getFilterDescription($property, self::PARAMETER_STRICTLY_AFTER);
        }

        return $description;
    }

    abstract protected function getProperties(): ?array;

    /**
     * Determines whether the given property refers to a date field.
     */
    protected function isDateField(string $property, string $resourceClass): bool
    {
        return isset(self::DOCTRINE_DATE_TYPES[(string) $this->getDoctrineFieldType($property, $resourceClass)]);
    }

    /**
     * Gets filter description.
     */
    protected function getFilterDescription(string $property, string $period): array
    {
        return [
            sprintf('%s[%s]', $property, $period) => [
                'property' => $property,
                'type' => \DateTimeInterface::class,
                'required' => false,
            ],
        ];
    }
}
