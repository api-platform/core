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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\AbstractFilter;
use ApiPlatform\Core\Bridge\Doctrine\Common\Util\QueryNameGeneratorInterface;

class DummyFilter extends AbstractFilter
{
    public function doSplitPropertiesWithoutResourceClass($property)
    {
        return $this->splitPropertyParts($property);
    }

    protected function filterProperty(string $property, $values, $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
    }

    public function getDescription(string $resourceClass): array
    {
        return [];
    }
}
