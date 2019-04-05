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
 * Trait for ordering the collection by given properties.
 *
 * @internal
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
    protected $orderParameterName;

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

        foreach ($properties as $property => $propertyOptions) {
            if (
                !$this->isPropertyEnabled($property, $resourceClass, $context) ||
                !$this->isPropertyMapped($property, $resourceClass)
            ) {
                continue;
            }

            $description[sprintf('%s[%s]', $this->orderParameterName, $property)] = [
                'property' => $property,
                'type' => 'string',
                'required' => false,
            ];
        }

        return $description;
    }

    abstract protected function getProperties(): ?array;

    private function normalizeValue($value, string $property): ?string
    {
        if (empty($value) && null !== $defaultDirection = $this->getProperties()[$property]['default_direction'] ?? null) {
            // fallback to default direction
            $value = $defaultDirection;
        }

        $value = strtoupper($value);
        if (!\in_array($value, [self::DIRECTION_ASC, self::DIRECTION_DESC], true)) {
            return null;
        }

        return $value;
    }
}
