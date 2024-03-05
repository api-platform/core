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

namespace ApiPlatform\Tests\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Test\DoctrineOrmFilterTestCase;
use ApiPlatform\Tests\Doctrine\Common\Filter\ExistsFilterTestTrait;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Serializer\NameConverter\CustomConverter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class ExistsFilterTest extends DoctrineOrmFilterTestCase
{
    use ExistsFilterTestTrait;

    protected string $filterClass = ExistsFilter::class;

    public function testGetDescriptionDefaultFields(): void
    {
        $filter = $this->buildFilter();

        $this->assertEquals([
            'exists[id]' => [
                'property' => 'id',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[alias]' => [
                'property' => 'alias',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[description]' => [
                'property' => 'description',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[dummy]' => [
                'property' => 'dummy',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[dummyDate]' => [
                'property' => 'dummyDate',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[dummyFloat]' => [
                'property' => 'dummyFloat',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[dummyPrice]' => [
                'property' => 'dummyPrice',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[jsonData]' => [
                'property' => 'jsonData',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[arrayData]' => [
                'property' => 'arrayData',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[name_converted]' => [
                'property' => 'name_converted',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[dummyBoolean]' => [
                'property' => 'dummyBoolean',
                'type' => 'bool',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public static function provideApplyTestData(): array
    {
        $existsFilterFactory = fn (self $that, ManagerRegistry $managerRegistry, ?array $properties = null): ExistsFilter => new ExistsFilter($managerRegistry, null, $properties, 'exists');
        $customExistsFilterFactory = fn (self $that, ManagerRegistry $managerRegistry, ?array $properties = null): ExistsFilter => new ExistsFilter($managerRegistry, null, $properties, 'customExists');

        return array_merge_recursive(
            self::provideApplyTestArguments(),
            [
                'valid values' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'valid values (empty for true)' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'valid values (1 for true)' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'invalid values' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'negative values' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'negative values (0)' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'multiple values (true and true)' => [
                    sprintf('SELECT o FROM %s o WHERE o.alias IS NOT NULL AND o.description IS NOT NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'multiple values (1 and 0)' => [
                    sprintf('SELECT o FROM %s o WHERE o.alias IS NOT NULL AND o.description IS NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'multiple values (false and 0)' => [
                    sprintf('SELECT o FROM %s o WHERE o.alias IS NULL AND o.description IS NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'custom exists parameter name' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL', Dummy::class),
                    null,
                    $customExistsFilterFactory,
                ],

                'related values' => [
                    sprintf('SELECT o FROM %s o INNER JOIN o.relatedDummy relatedDummy_a1 WHERE o.description IS NOT NULL AND relatedDummy_a1.name IS NOT NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'not nullable values' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'related collection not empty' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL AND o.relatedDummies IS NOT EMPTY', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'related collection empty' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL AND o.relatedDummies IS EMPTY', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'related association exists' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL AND o.relatedDummy IS NOT NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'related association does not exist' => [
                    sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL AND o.relatedDummy IS NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'related owned association does not exist' => [
                    sprintf('SELECT o FROM %s o LEFT JOIN o.relatedOwnedDummy relatedOwnedDummy_a1 WHERE relatedOwnedDummy_a1 IS NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'related owned association exists' => [
                    sprintf('SELECT o FROM %s o LEFT JOIN o.relatedOwnedDummy relatedOwnedDummy_a1 WHERE relatedOwnedDummy_a1 IS NOT NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'related owning association does not exist' => [
                    sprintf('SELECT o FROM %s o WHERE o.relatedOwningDummy IS NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],

                'related owning association exists' => [
                    sprintf('SELECT o FROM %s o WHERE o.relatedOwningDummy IS NOT NULL', Dummy::class),
                    null,
                    $existsFilterFactory,
                ],
            ]
        );
    }

    protected function buildFilter(?array $properties = null)
    {
        return new $this->filterClass($this->managerRegistry, null, $properties, 'exists', new CustomConverter());
    }
}
