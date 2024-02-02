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

namespace ApiPlatform\Tests\Doctrine\Odm\Filter;

use ApiPlatform\Doctrine\Odm\Filter\OrderFilter;
use ApiPlatform\Test\DoctrineMongoDbOdmFilterTestCase;
use ApiPlatform\Tests\Doctrine\Common\Filter\OrderFilterTestTrait;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\EmbeddedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Serializer\NameConverter\CustomConverter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @group mongodb
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class OrderFilterTest extends DoctrineMongoDbOdmFilterTestCase
{
    use OrderFilterTestTrait;

    protected string $filterClass = OrderFilter::class;
    protected string $resourceClass = Dummy::class;

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
            'order[relatedDummy]' => [
                'property' => 'relatedDummy',
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
            'order[relatedDummies]' => [
                'property' => 'relatedDummies',
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
            'order[relatedOwnedDummy]' => [
                'property' => 'relatedOwnedDummy',
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
            'order[relatedOwningDummy]' => [
                'property' => 'relatedOwningDummy',
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
    }

    public static function provideApplyTestData(): array
    {
        $orderFilterFactory = fn (self $that, ManagerRegistry $managerRegistry, ?array $properties = null): OrderFilter => new OrderFilter($managerRegistry, 'order', null, $properties);
        $customOrderFilterFactory = fn (self $that, ManagerRegistry $managerRegistry, ?array $properties = null): OrderFilter => new OrderFilter($managerRegistry, 'customOrder', null, $properties);

        return array_merge_recursive(
            self::provideApplyTestArguments(),
            [
                'valid values' => [
                    [
                        [
                            '$sort' => [
                                '_id' => 1,
                            ],
                        ],
                        [
                            '$sort' => [
                                '_id' => 1,
                                'name' => -1,
                            ],
                        ],
                    ],
                    $orderFilterFactory,
                ],
                'invalid values' => [
                    [
                        [
                            '$sort' => [
                                '_id' => 1,
                            ],
                        ],
                    ],
                    $orderFilterFactory,
                ],
                'valid values (properties not enabled)' => [
                    [
                        [
                            '$sort' => [
                                '_id' => 1,
                            ],
                        ],
                    ],
                    $orderFilterFactory,
                ],
                'invalid values (properties not enabled)' => [
                    [
                        [
                            '$sort' => [
                                'name' => 1,
                            ],
                        ],
                    ],
                    $orderFilterFactory,
                ],
                'invalid property (property not enabled)' => [
                    [],
                    $orderFilterFactory,
                ],
                'invalid property (property enabled)' => [
                    [],
                    $orderFilterFactory,
                ],
                'custom order parameter name' => [
                    [
                        [
                            '$sort' => [
                                'name' => -1,
                            ],
                        ],
                    ],
                    $customOrderFilterFactory,
                ],
                'valid values (all properties enabled)' => [
                    [
                        [
                            '$sort' => [
                                '_id' => 1,
                            ],
                        ],
                        [
                            '$sort' => [
                                '_id' => 1,
                                'name' => 1,
                            ],
                        ],
                    ],
                    $orderFilterFactory,
                ],
                'nested property' => [
                    [
                        [
                            '$sort' => [
                                '_id' => 1,
                            ],
                        ],
                        [
                            '$sort' => [
                                '_id' => 1,
                                'name' => -1,
                            ],
                        ],
                        [
                            '$lookup' => [
                                'from' => 'RelatedDummy',
                                'localField' => 'relatedDummy',
                                'foreignField' => '_id',
                                'as' => 'relatedDummy_lkup',
                            ],
                        ],
                        [
                            '$unwind' => [
                                'path' => '$relatedDummy_lkup',
                                'preserveNullAndEmptyArrays' => true,
                            ],
                        ],
                        [
                            '$sort' => [
                                '_id' => 1,
                                'name' => -1,
                                'relatedDummy_lkup.symfony' => -1,
                            ],
                        ],
                    ],
                    $orderFilterFactory,
                ],
                'empty values with default sort direction' => [
                    [
                        [
                            '$sort' => [
                                '_id' => 1,
                            ],
                        ],
                        [
                            '$sort' => [
                                '_id' => 1,
                                'name' => -1,
                            ],
                        ],
                    ],
                    $orderFilterFactory,
                ],
                'nulls_smallest (asc)' => [
                    [
                        [
                            '$sort' => [
                                'dummyDate' => 1,
                            ],
                        ],
                        [
                            '$sort' => [
                                'dummyDate' => 1,
                                'name' => -1,
                            ],
                        ],
                    ],
                    $orderFilterFactory,
                ],
                'nulls_smallest (desc)' => [
                    [
                        [
                            '$sort' => [
                                'dummyDate' => -1,
                            ],
                        ],
                        [
                            '$sort' => [
                                'dummyDate' => -1,
                                'name' => -1,
                            ],
                        ],
                    ],
                    $orderFilterFactory,
                ],
                'nulls_largest (asc)' => [
                    [
                        [
                            '$sort' => [
                                'dummyDate' => 1,
                            ],
                        ],
                        [
                            '$sort' => [
                                'dummyDate' => 1,
                                'name' => -1,
                            ],
                        ],
                    ],
                    $orderFilterFactory,
                ],
                'nulls_largest (desc)' => [
                    [
                        [
                            '$sort' => [
                                'dummyDate' => -1,
                            ],
                        ],
                        [
                            '$sort' => [
                                'dummyDate' => -1,
                                'name' => -1,
                            ],
                        ],
                    ],
                    $orderFilterFactory,
                ],
                'nulls_always_first (asc)' => [
                    [
                        [
                            '$sort' => [
                                'dummyDate' => 1,
                            ],
                        ],
                        [
                            '$sort' => [
                                'dummyDate' => 1,
                                'name' => -1,
                            ],
                        ],
                    ],
                    $orderFilterFactory,
                ],
                'nulls_always_first (desc)' => [
                    [
                        [
                            '$sort' => [
                                'dummyDate' => -1,
                            ],
                        ],
                        [
                            '$sort' => [
                                'dummyDate' => -1,
                                'name' => -1,
                            ],
                        ],
                    ],
                    $orderFilterFactory,
                ],
                'nulls_always_last (asc)' => [
                    [
                        [
                            '$sort' => [
                                'dummyDate' => 1,
                            ],
                        ],
                        [
                            '$sort' => [
                                'dummyDate' => 1,
                                'name' => -1,
                            ],
                        ],
                    ],
                    $orderFilterFactory,
                ],
                'nulls_always_last (desc)' => [
                    [
                        [
                            '$sort' => [
                                'dummyDate' => -1,
                            ],
                        ],
                        [
                            '$sort' => [
                                'dummyDate' => -1,
                                'name' => -1,
                            ],
                        ],
                    ],
                    $orderFilterFactory,
                ],
                'not having order should not throw a deprecation (select unchanged)' => [
                    [],
                    $orderFilterFactory,
                ],
                'not nullable relation will be a LEFT JOIN' => [
                    [
                        [
                            '$lookup' => [
                                'from' => 'RelatedDummy',
                                'localField' => 'relatedDummy',
                                'foreignField' => '_id',
                                'as' => 'relatedDummy_lkup',
                            ],
                        ],
                        [
                            '$unwind' => [
                                'path' => '$relatedDummy_lkup',
                                'preserveNullAndEmptyArrays' => true,
                            ],
                        ],
                        [
                            '$sort' => [
                                'relatedDummy_lkup.name' => 1,
                            ],
                        ],
                    ],
                    $orderFilterFactory,
                ],
                'embedded' => [
                    [
                        [
                            '$sort' => [
                                'embeddedDummy.dummyName' => 1,
                            ],
                        ],
                    ],
                    $orderFilterFactory,
                    EmbeddedDummy::class,
                ],
                'embedded with nulls_comparison' => [
                    [
                        [
                            '$sort' => [
                                'embeddedDummy.dummyName' => 1,
                            ],
                        ],
                    ],
                    $orderFilterFactory,
                    EmbeddedDummy::class,
                ],
                'nullable field in relation will be a LEFT JOIN' => [
                    [
                        [
                            '$lookup' => [
                                'from' => 'RelatedDummy',
                                'localField' => 'relatedDummy',
                                'foreignField' => '_id',
                                'as' => 'relatedDummy_lkup',
                            ],
                        ],
                        [
                            '$unwind' => [
                                'path' => '$relatedDummy_lkup',
                                'preserveNullAndEmptyArrays' => true,
                            ],
                        ],
                        [
                            '$sort' => [
                                'relatedDummy_lkup.name' => 1,
                            ],
                        ],
                    ],
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
