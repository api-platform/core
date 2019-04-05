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
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Trait for filtering the collection by whether a property value exists or not.
 *
 * @internal
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
trait ExistsFilterTrait
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

        foreach ($properties as $property => $unused) {
            if (
                !$this->isPropertyEnabled($property, $resourceClass, $context) ||
                !$this->isPropertyMapped($property, $resourceClass, true) ||
                !$this->isNullableField($property, $resourceClass)
            ) {
                continue;
            }

            $description[sprintf('%s[%s]', $property, self::QUERY_PARAMETER_KEY)] = [
                'property' => $property,
                'type' => 'bool',
                'required' => false,
            ];
        }

        return $description;
    }

    /**
     * Determines whether the given property refers to a nullable field.
     */
    abstract protected function isNullableField(string $property, string $resourceClass): bool;

    abstract protected function getProperties(): ?array;

    abstract protected function getLogger(): LoggerInterface;

    private function normalizeValue($value, string $property): ?bool
    {
        if (\in_array($value[self::QUERY_PARAMETER_KEY], [true, 'true', '1', '', null], true)) {
            return true;
        }

        if (\in_array($value[self::QUERY_PARAMETER_KEY], [false, 'false', '0'], true)) {
            return false;
        }

        $this->getLogger()->notice('Invalid filter ignored', [
            'exception' => new InvalidArgumentException(sprintf('Invalid value for "%s[%s]", expected one of ( "%s" )', $property, self::QUERY_PARAMETER_KEY, implode('" | "', [
                'true',
                'false',
                '1',
                '0',
            ]))),
        ]);

        return null;
    }
}
