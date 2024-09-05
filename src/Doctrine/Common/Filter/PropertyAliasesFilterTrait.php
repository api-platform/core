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

use ApiPlatform\Metadata\Exception\UnMappedPropertyAliasException;

/**
 * Interface for filtering the collection by given properties.
 *
 * @author Christophe Zarebski <christophe.zarebski@gmail.com>
 */
trait PropertyAliasesFilterTrait
{
    protected array $propertyAliases = [];

    protected function getPropertyAliases(): array
    {
        return $this->propertyAliases;
    }

    protected function isAlias(string $alias): bool
    {
        return !empty($this->getPropertyAliases()) && \in_array($alias, $this->getPropertyAliases(), true);
    }

    protected function getAliasForPropertyOrProperty(string $property): string
    {
        return $this->propertyAliases[$property] ?? $property;
    }

    protected function getPropertyFromAlias(string $alias): string
    {
        return array_flip($this->propertyAliases)[$alias] ?? throw new UnMappedPropertyAliasException($alias);
    }
}
