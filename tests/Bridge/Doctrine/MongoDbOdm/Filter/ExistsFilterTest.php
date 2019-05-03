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
            'id[exists]' => [
                'property' => 'id',
                'type' => 'bool',
                'required' => false,
            ],
            'alias[exists]' => [
                'property' => 'alias',
                'type' => 'bool',
                'required' => false,
            ],
            'description[exists]' => [
                'property' => 'description',
                'type' => 'bool',
                'required' => false,
            ],
            'dummy[exists]' => [
                'property' => 'dummy',
                'type' => 'bool',
                'required' => false,
            ],
            'dummyDate[exists]' => [
                'property' => 'dummyDate',
                'type' => 'bool',
                'required' => false,
            ],
            'dummyFloat[exists]' => [
                'property' => 'dummyFloat',
                'type' => 'bool',
                'required' => false,
            ],
            'dummyPrice[exists]' => [
                'property' => 'dummyPrice',
                'type' => 'bool',
                'required' => false,
            ],
            'jsonData[exists]' => [
                'property' => 'jsonData',
                'type' => 'bool',
                'required' => false,
            ],
            'arrayData[exists]' => [
                'property' => 'arrayData',
                'type' => 'bool',
                'required' => false,
            ],
            'nameConverted[exists]' => [
                'property' => 'nameConverted',
                'type' => 'bool',
                'required' => false,
            ],
            'dummyBoolean[exists]' => [
                'property' => 'dummyBoolean',
                'type' => 'bool',
                'required' => false,
            ],
            'relatedDummy[exists]' => [
                'property' => 'relatedDummy',
                'type' => 'bool',
                'required' => false,
            ],
            'relatedDummies[exists]' => [
                'property' => 'relatedDummies',
                'type' => 'bool',
                'required' => false,
            ],
            'relatedOwnedDummy[exists]' => [
                'property' => 'relatedOwnedDummy',
                'type' => 'bool',
                'required' => false,
            ],
            'relatedOwningDummy[exists]' => [
                'property' => 'relatedOwningDummy',
                'type' => 'bool',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function provideApplyTestData(): array
    {
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
                ],

                'invalid values' => [
                    [],
                ],

                'negative values' => [
                    [
                        [
                            '$match' => [
                                'description' => null,
                            ],
                        ],
                    ],
                ],

                'negative values (0)' => [
                    [
                        [
                            '$match' => [
                                'description' => null,
                            ],
                        ],
                    ],
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
                ],

                'related owned association does not exist' => [
                    [
                        [
                            '$match' => [
                                'relatedOwnedDummy' => null,
                            ],
                        ],
                    ],
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
                ],

                'related owning association does not exist' => [
                    [
                        [
                            '$match' => [
                                'relatedOwningDummy' => null,
                            ],
                        ],
                    ],
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
                ],
            ]
        );
    }
}
