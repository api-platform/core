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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\MongoDbOdm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\OrderFilter;
use ApiPlatform\Core\Test\DoctrineMongoDbOdmFilterTestCase;
use ApiPlatform\Core\Tests\Bridge\Doctrine\Common\Filter\OrderFilterTestTrait;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @group mongodb
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class OrderFilterTest extends DoctrineMongoDbOdmFilterTestCase
{
    use OrderFilterTestTrait;

    protected $filterClass = OrderFilter::class;

    public function testGetDescriptionDefaultFields()
    {
        $filter = $this->buildFilter();

        $this->assertEquals([
            'order[id]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'order[name]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
            ],
            'order[alias]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
            ],
            'order[description]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
            ],
            'order[dummy]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
            ],
            'order[dummyDate]' => [
                'property' => 'dummyDate',
                'type' => 'string',
                'required' => false,
            ],
            'order[dummyFloat]' => [
                'property' => 'dummyFloat',
                'type' => 'string',
                'required' => false,
            ],
            'order[dummyPrice]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
            ],
            'order[jsonData]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
            ],
            'order[arrayData]' => [
                'property' => 'arrayData',
                'type' => 'string',
                'required' => false,
            ],
            'order[nameConverted]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
            ],
            'order[dummyBoolean]' => [
                'property' => 'dummyBoolean',
                'type' => 'string',
                'required' => false,
            ],
            'order[relatedDummy]' => [
                'property' => 'relatedDummy',
                'type' => 'string',
                'required' => false,
            ],
            'order[relatedDummies]' => [
                'property' => 'relatedDummies',
                'type' => 'string',
                'required' => false,
            ],
            'order[relatedOwnedDummy]' => [
                'property' => 'relatedOwnedDummy',
                'type' => 'string',
                'required' => false,
            ],
            'order[relatedOwningDummy]' => [
                'property' => 'relatedOwningDummy',
                'type' => 'string',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function provideApplyTestData(): array
    {
        $orderFilterFactory = function (ManagerRegistry $managerRegistry, array $properties = null): OrderFilter {
            return new OrderFilter($managerRegistry, 'order', null, $properties);
        };
        $customOrderFilterFactory = function (ManagerRegistry $managerRegistry, array $properties = null): OrderFilter {
            return new OrderFilter($managerRegistry, 'customOrder', null, $properties);
        };

        return array_merge_recursive(
            $this->provideApplyTestArguments(),
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
                            '$unwind' => '$relatedDummy_lkup',
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
                            '$unwind' => '$relatedDummy_lkup',
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
        return new $this->filterClass($this->managerRegistry, 'order', null, $properties);
    }
}
