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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Filter;

use ApiPlatform\Doctrine\Common\PropertyHelperTrait;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class BoundsFilter extends AbstractFilter
{
    use PropertyHelperTrait;

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
    }

    // This function is only used to hook in documentation generators (supported by Swagger and Hydra)
    public function getDescription(string $resourceClass): array
    {
        return [
            'maximum' => [
                'property' => 'maximum',
                'type' => 'float',
                'required' => false,
                'swagger' => [
                    'maximum' => 10,
                ],
            ],
            'exclusiveMaximum' => [
                'property' => 'maximum',
                'type' => 'float',
                'required' => false,
                'swagger' => [
                    'maximum' => 10,
                    'exclusiveMaximum' => true,
                ],
            ],
            'minimum' => [
                'property' => 'minimum',
                'type' => 'float',
                'required' => false,
                'swagger' => [
                    'minimum' => 5,
                ],
            ],
            'exclusiveMinimum' => [
                'property' => 'exclusiveMinimum',
                'type' => 'float',
                'required' => false,
                'swagger' => [
                    'minimum' => 5,
                    'exclusiveMinimum' => true,
                ],
            ],
        ];
    }
}
