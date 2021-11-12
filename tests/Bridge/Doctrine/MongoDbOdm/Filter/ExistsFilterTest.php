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

use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\ExistsFilter;
use ApiPlatform\Core\Test\DoctrineMongoDbOdmFilterTestCase;
use ApiPlatform\Core\Tests\Bridge\Doctrine\Common\Filter\ExistsFilterTestTrait;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @group mongodb
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class ExistsFilterTest extends DoctrineMongoDbOdmFilterTestCase
{
    use ExistsFilterTestTrait;

    protected $filterClass = ExistsFilter::class;

    public function testGetDescriptionDefaultFields()
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
            'exists[nameConverted]' => [
                'property' => 'nameConverted',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[dummyBoolean]' => [
                'property' => 'dummyBoolean',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[relatedDummy]' => [
                'property' => 'relatedDummy',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[relatedDummies]' => [
                'property' => 'relatedDummies',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[relatedOwnedDummy]' => [
                'property' => 'relatedOwnedDummy',
                'type' => 'bool',
                'required' => false,
            ],
            'exists[relatedOwningDummy]' => [
                'property' => 'relatedOwningDummy',
                'type' => 'bool',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function provideApplyTestData(): array
    {
        $existsFilterFactory = function (ManagerRegistry $managerRegistry, array $properties = null): ExistsFilter {
            return new ExistsFilter($managerRegistry, null, $properties, 'exists');
        };
        $customExistsFilterFactory = function (ManagerRegistry $managerRegistry, array $properties = null): ExistsFilter {
            return new ExistsFilter($managerRegistry, null, $properties, 'customExists');
        };

        return array_merge_recursive(
            $this->provideApplyTestArguments(),
            [
                'valid values' => [
                    [
                        [
                            '$match' => [
                                'description' => [
                                    '$ne' => null,
                                ],
                            ],
                        ],
                    ],
                    $existsFilterFactory,
                ],

                'valid values (empty for true)' => [
                    [
                        [
                            '$match' => [
                                'description' => [
                                    '$ne' => null,
                                ],
                            ],
                        ],
                    ],
                    $existsFilterFactory,
                ],

                'valid values (1 for true)' => [
                    [
                        [
                            '$match' => [
                                'description' => [
                                    '$ne' => null,
                                ],
                            ],
                        ],
                    ],
                    $existsFilterFactory,
                ],

                'invalid values' => [
                    [],
                    $existsFilterFactory,
                ],

                'negative values' => [
                    [
                        [
                            '$match' => [
                                'description' => null,
                            ],
                        ],
                    ],
                    $existsFilterFactory,
                ],

                'negative values (0)' => [
                    [
                        [
                            '$match' => [
                                'description' => null,
                            ],
                        ],
                    ],
                    $existsFilterFactory,
                ],

                'multiple values (true and true)' => [
                    [
                        [
                            '$match' => [
                                'alias' => [
                                    '$ne' => null,
                                ],
                            ],
                        ],
                        [
                            '$match' => [
                                'description' => [
                                    '$ne' => null,
                                ],
                            ],
                        ],
                    ],
                    $existsFilterFactory,
                ],

                'multiple values (1 and 0)' => [
                    [
                        [
                            '$match' => [
                                'alias' => [
                                    '$ne' => null,
                                ],
                            ],
                        ],
                        [
                            '$match' => [
                                'description' => null,
                            ],
                        ],
                    ],
                    $existsFilterFactory,
                ],

                'multiple values (false and 0)' => [
                    [
                        [
                            '$match' => [
                                'alias' => null,
                            ],
                        ],
                        [
                            '$match' => [
                                'description' => null,
                            ],
                        ],
                    ],
                    $existsFilterFactory,
                ],

                'custom exists parameter name' => [
                    [
                        [
                            '$match' => [
                                'description' => [
                                    '$ne' => null,
                                ],
                            ],
                        ],
                    ],
                    $customExistsFilterFactory,
                ],

                'related values' => [
                    [
                        [
                            '$match' => [
                                'description' => [
                                    '$ne' => null,
                                ],
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
                            '$match' => [
                                'relatedDummy_lkup.name' => [
                                    '$ne' => null,
                                ],
                            ],
                        ],
                    ],
                    $existsFilterFactory,
                ],

                'not nullable values' => [
                    [
                        [
                            '$match' => [
                                'description' => [
                                    '$ne' => null,
                                ],
                            ],
                        ],
                    ],
                    $existsFilterFactory,
                ],

                'related collection not empty' => [
                    [
                        [
                            '$match' => [
                                'description' => [
                                    '$ne' => null,
                                ],
                            ],
                        ],
                        [
                            '$match' => [
                                'relatedDummies' => [
                                    '$ne' => null,
                                ],
                            ],
                        ],
                    ],
                    $existsFilterFactory,
                ],

                'related collection empty' => [
                    [
                        [
                            '$match' => [
                                'description' => [
                                    '$ne' => null,
                                ],
                            ],
                        ],
                        [
                            '$match' => [
                                'relatedDummies' => null,
                            ],
                        ],
                    ],
                    $existsFilterFactory,
                ],

                'related association exists' => [
                    [
                        [
                            '$match' => [
                                'description' => [
                                    '$ne' => null,
                                ],
                            ],
                        ],
                        [
                            '$match' => [
                                'relatedDummy' => [
                                    '$ne' => null,
                                ],
                            ],
                        ],
                    ],
                    $existsFilterFactory,
                ],

                'related association does not exist' => [
                    [
                        [
                            '$match' => [
                                'description' => [
                                    '$ne' => null,
                                ],
                            ],
                        ],
                        [
                            '$match' => [
                                'relatedDummy' => null,
                            ],
                        ],
                    ],
                    $existsFilterFactory,
                ],

                'related owned association does not exist' => [
                    [
                        [
                            '$match' => [
                                'relatedOwnedDummy' => null,
                            ],
                        ],
                    ],
                    $existsFilterFactory,
                ],

                'related owned association exists' => [
                    [
                        [
                            '$match' => [
                                'relatedOwnedDummy' => [
                                    '$ne' => null,
                                ],
                            ],
                        ],
                    ],
                    $existsFilterFactory,
                ],

                'related owning association does not exist' => [
                    [
                        [
                            '$match' => [
                                'relatedOwningDummy' => null,
                            ],
                        ],
                    ],
                    $existsFilterFactory,
                ],

                'related owning association exists' => [
                    [
                        [
                            '$match' => [
                                'relatedOwningDummy' => [
                                    '$ne' => null,
                                ],
                            ],
                        ],
                    ],
                    $existsFilterFactory,
                ],
            ]
        );
    }

    /**
     * @group legacy
     * @expectedDeprecation The ExistsFilter syntax "description[exists]=true/false" is deprecated since 2.5. Use the syntax "exists[description]=true/false" instead.
     */
    public function testLegacyExistsAfterSyntax()
    {
        $args = [
            [
                'description' => null,
            ],
            [
                'description' => [
                    'exists' => 'true',
                ],
            ],
            [
                [
                    '$match' => [
                        'description' => [
                            '$ne' => null,
                        ],
                    ],
                ],
            ],
            function (ManagerRegistry $managerRegistry, array $properties = null): ExistsFilter {
                return new ExistsFilter($managerRegistry, null, $properties, 'exists');
            },
        ];

        $this->testApply(...$args);
    }

    protected function buildFilter(?array $properties = null)
    {
        return new $this->filterClass($this->managerRegistry, null, $properties, 'exists');
    }
}
