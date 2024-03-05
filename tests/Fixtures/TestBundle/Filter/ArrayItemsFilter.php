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

class ArrayItemsFilter extends AbstractFilter
{
    use PropertyHelperTrait;

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
    }

    // This function is only used to hook in documentation generators (supported by Swagger and Hydra)
    public function getDescription(string $resourceClass): array
    {
        return [
            'csv-min-2' => [
                'property' => 'csv-min-2',
                'type' => 'array',
                'required' => false,
                'swagger' => [
                    'minItems' => 2,
                ],
            ],
            'csv-max-3' => [
                'property' => 'csv-max-3',
                'type' => 'array',
                'required' => false,
                'swagger' => [
                    'maxItems' => 3,
                ],
            ],
            'ssv-min-2' => [
                'property' => 'ssv-min-2',
                'type' => 'array',
                'required' => false,
                'swagger' => [
                    'collectionFormat' => 'ssv',
                    'minItems' => 2,
                ],
            ],
            'tsv-min-2' => [
                'property' => 'tsv-min-2',
                'type' => 'array',
                'required' => false,
                'swagger' => [
                    'collectionFormat' => 'tsv',
                    'minItems' => 2,
                ],
            ],
            'pipes-min-2' => [
                'property' => 'pipes-min-2',
                'type' => 'array',
                'required' => false,
                'swagger' => [
                    'collectionFormat' => 'pipes',
                    'minItems' => 2,
                ],
            ],
            'csv-uniques' => [
                'property' => 'csv-uniques',
                'type' => 'array',
                'required' => false,
                'swagger' => [
                    'uniqueItems' => true,
                ],
            ],
        ];
    }
}
