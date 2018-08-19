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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\AbstractFilter;
use ApiPlatform\Core\Bridge\Doctrine\Common\Util\QueryNameGeneratorInterface;

final class ArrayRequiredFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
    }

    // This function is only used to hook in documentation generators (supported by Swagger and Hydra)
    public function getDescription(string $resourceClass): array
    {
        return [
            'arrayRequired[]' => [
                'property' => 'arrayRequired',
                'type' => 'string',
                'required' => true,
            ],
            'indexedArrayRequired[foo]' => [
                'property' => 'indexedArrayRequired',
                'type' => 'string',
                'required' => true,
            ],
        ];
    }
}
