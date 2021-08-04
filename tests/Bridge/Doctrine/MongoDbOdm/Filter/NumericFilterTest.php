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

use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\NumericFilter;
use ApiPlatform\Core\Test\DoctrineMongoDbOdmFilterTestCase;
use ApiPlatform\Core\Tests\Bridge\Doctrine\Common\Filter\NumericFilterTestTrait;

/**
 * @group mongodb
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class NumericFilterTest extends DoctrineMongoDbOdmFilterTestCase
{
    use NumericFilterTestTrait;

    protected $filterClass = NumericFilter::class;

    public function testGetDescriptionDefaultFields()
    {
        $filter = $this->buildFilter();

        $this->assertEquals([
            'id' => [
                'property' => 'id',
                'type' => 'int',
                'required' => false,
                'is_collection' => false,
            ],
            'id[]' => [
                'property' => 'id',
                'type' => 'int',
                'required' => false,
                'is_collection' => true,
            ],
            'dummyFloat' => [
                'property' => 'dummyFloat',
                'type' => 'float',
                'required' => false,
                'is_collection' => false,
            ],
            'dummyFloat[]' => [
                'property' => 'dummyFloat',
                'type' => 'float',
                'required' => false,
                'is_collection' => true,
            ],
            'dummyPrice' => [
                'property' => 'dummyPrice',
                'type' => 'float',
                'required' => false,
                'is_collection' => false,
            ],
            'dummyPrice[]' => [
                'property' => 'dummyPrice',
                'type' => 'float',
                'required' => false,
                'is_collection' => true,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function provideApplyTestData(): array
    {
        return array_merge_recursive(
            $this->provideApplyTestArguments(),
            [
                'numeric string (positive integer)' => [
                    [
                        [
                            '$match' => [
                                'dummyPrice' => 21,
                            ],
                        ],
                    ],
                ],
                'multiple numeric string (positive integer)' => [
                    [
                        [
                            '$match' => [
                                'dummyPrice' => [
                                    '$in' => [21, 22],
                                ],
                            ],
                        ],
                    ],
                ],
                'multiple numeric string with one invalid property key' => [
                    [
                        [
                            '$match' => [
                                'dummyPrice' => 22,
                            ],
                        ],
                    ],
                ],
                'multiple numeric string with invalid value keys' => [
                    [],
                ],
                'multiple non-numeric' => [
                    [],
                ],
                'numeric string (negative integer)' => [
                    [
                        [
                            '$match' => [
                                'dummyPrice' => -21,
                            ],
                        ],
                    ],
                ],
                'non-numeric' => [
                    [],
                ],
                'numeric string ("0")' => [
                    [
                        [
                            '$match' => [
                                'dummyPrice' => 0,
                            ],
                        ],
                    ],
                ],
                'nested property' => [
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
                            '$match' => [
                                'relatedDummy_lkup.id' => 0,
                            ],
                        ],
                    ],
                ],
                'mixed numeric and non-numeric' => [
                    [
                        [
                            '$match' => [
                                'dummyPrice' => 10,
                            ],
                        ],
                    ],
                ],
                'mixed numeric, non-numeric and invalid property' => [
                    [
                        [
                            '$match' => [
                                'dummyPrice' => 0,
                            ],
                        ],
                    ],
                ],
            ]
        );
    }
}
