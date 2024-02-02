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

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Test\DoctrineOrmFilterTestCase;
use ApiPlatform\Tests\Doctrine\Common\Filter\OrderFilterTestTrait;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\EmbeddedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Serializer\NameConverter\CustomConverter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 */
class OrderFilterTest extends DoctrineOrmFilterTestCase
{
    use OrderFilterTestTrait;

    protected string $filterClass = OrderFilter::class;

    public function testGetDescriptionDefaultFields(): void
    {
        $filter = $this->buildFilter();

        $this->assertEquals([
            'order[id]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
            'order[name]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
            'order[alias]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
            'order[description]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
            'order[dummy]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
            'order[dummyDate]' => [
                'property' => 'dummyDate',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
            'order[dummyFloat]' => [
                'property' => 'dummyFloat',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
            'order[dummyPrice]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
            'order[jsonData]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
            'order[arrayData]' => [
                'property' => 'arrayData',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
            'order[name_converted]' => [
                'property' => 'name_converted',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
            'order[dummyBoolean]' => [
                'property' => 'dummyBoolean',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
        ], $filter->getDescription($this->resourceClass));

        $this->assertEquals([
            'order[id]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
            'order[name]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
            'order[dummyDate]' => [
                'property' => 'dummyDate',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
            'order[embeddedDummy.dummyName]' => [
                'property' => 'embeddedDummy.dummyName',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
            'order[embeddedDummy.dummyBoolean]' => [
                'property' => 'embeddedDummy.dummyBoolean',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
            'order[embeddedDummy.dummyDate]' => [
                'property' => 'embeddedDummy.dummyDate',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
            'order[embeddedDummy.dummyFloat]' => [
                'property' => 'embeddedDummy.dummyFloat',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
            'order[embeddedDummy.dummyPrice]' => [
                'property' => 'embeddedDummy.dummyPrice',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
            'order[embeddedDummy.symfony]' => [
                'property' => 'embeddedDummy.symfony',
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        'asc',
                        'desc',
                    ],
                ],
            ],
        ], $filter->getDescription(EmbeddedDummy::class));
    }

    public static function provideApplyTestData(): array
    {
        $orderFilterFactory = fn (self $that, ManagerRegistry $managerRegistry, ?array $properties = null): OrderFilter => new OrderFilter($managerRegistry, 'order', null, $properties);
        $customOrderFilterFactory = fn (self $that, ManagerRegistry $managerRegistry, ?array $properties = null): OrderFilter => new OrderFilter($managerRegistry, 'customOrder', null, $properties);

        return array_merge_recursive(
            self::provideApplyTestArguments(),
            [
                'valid values' => [
                    sprintf('SELECT o FROM %s o ORDER BY o.id ASC, o.name DESC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'invalid values' => [
                    sprintf('SELECT o FROM %s o ORDER BY o.id ASC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'valid values (properties not enabled)' => [
                    sprintf('SELECT o FROM %s o ORDER BY o.id ASC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'invalid values (properties not enabled)' => [
                    sprintf('SELECT o FROM %s o ORDER BY o.name ASC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'invalid property (property not enabled)' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'invalid property (property enabled)' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'custom order parameter name' => [
                    sprintf('SELECT o FROM %s o ORDER BY o.name DESC', Dummy::class),
                    null,
                    $customOrderFilterFactory,
                ],
                'valid values (all properties enabled)' => [
                    sprintf('SELECT o FROM %s o ORDER BY o.id ASC, o.name ASC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'nested property' => [
                    sprintf('SELECT o FROM %s o LEFT JOIN o.relatedDummy relatedDummy_a1 ORDER BY o.id ASC, o.name DESC, relatedDummy_a1.symfony DESC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'empty values with default sort direction' => [
                    sprintf('SELECT o FROM %s o ORDER BY o.id ASC, o.name DESC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'nulls_smallest (asc)' => [
                    sprintf('SELECT o, CASE WHEN o.dummyDate IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_dummyDate_null_rank FROM %s o ORDER BY _o_dummyDate_null_rank ASC, o.dummyDate ASC, o.name DESC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'nulls_smallest (desc)' => [
                    sprintf('SELECT o, CASE WHEN o.dummyDate IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_dummyDate_null_rank FROM %s o ORDER BY _o_dummyDate_null_rank DESC, o.dummyDate DESC, o.name DESC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'nulls_largest (asc)' => [
                    sprintf('SELECT o, CASE WHEN o.dummyDate IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_dummyDate_null_rank FROM %s o ORDER BY _o_dummyDate_null_rank DESC, o.dummyDate ASC, o.name DESC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'nulls_largest (desc)' => [
                    sprintf('SELECT o, CASE WHEN o.dummyDate IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_dummyDate_null_rank FROM %s o ORDER BY _o_dummyDate_null_rank ASC, o.dummyDate DESC, o.name DESC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'nulls_always_first (asc)' => [
                    sprintf('SELECT o, CASE WHEN o.dummyDate IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_dummyDate_null_rank FROM %s o ORDER BY _o_dummyDate_null_rank ASC, o.dummyDate ASC, o.name DESC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'nulls_always_first (desc)' => [
                    sprintf('SELECT o, CASE WHEN o.dummyDate IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_dummyDate_null_rank FROM %s o ORDER BY _o_dummyDate_null_rank ASC, o.dummyDate DESC, o.name DESC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'nulls_always_last (asc)' => [
                    sprintf('SELECT o, CASE WHEN o.dummyDate IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_dummyDate_null_rank FROM %s o ORDER BY _o_dummyDate_null_rank DESC, o.dummyDate ASC, o.name DESC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'nulls_always_last (desc)' => [
                    sprintf('SELECT o, CASE WHEN o.dummyDate IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_dummyDate_null_rank FROM %s o ORDER BY _o_dummyDate_null_rank DESC, o.dummyDate DESC, o.name DESC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'not having order should not throw a deprecation (select unchanged)' => [
                    sprintf('SELECT o FROM %s o', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'not nullable relation will be a LEFT JOIN' => [
                    sprintf('SELECT o FROM %s o LEFT JOIN o.relatedDummy relatedDummy_a1 ORDER BY relatedDummy_a1.name ASC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
                'embedded' => [
                    sprintf('SELECT o FROM %s o ORDER BY o.embeddedDummy.dummyName ASC', EmbeddedDummy::class),
                    null,
                    $orderFilterFactory,
                    EmbeddedDummy::class,
                ],
                'embedded with nulls_comparison' => [
                    sprintf('SELECT o, CASE WHEN o.embeddedDummy.dummyName IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_embeddedDummy_dummyName_null_rank FROM %s o ORDER BY _o_embeddedDummy_dummyName_null_rank DESC, o.embeddedDummy.dummyName ASC', EmbeddedDummy::class),
                    null,
                    $orderFilterFactory,
                    EmbeddedDummy::class,
                ],
                'nullable field in relation will be a LEFT JOIN' => [
                    sprintf('SELECT o FROM %s o LEFT JOIN o.relatedDummy relatedDummy_a1 ORDER BY relatedDummy_a1.name ASC', Dummy::class),
                    null,
                    $orderFilterFactory,
                ],
            ]
        );
    }

    protected function buildFilter(?array $properties = null)
    {
        return new $this->filterClass($this->managerRegistry, 'order', null, $properties, new CustomConverter());
    }
}
