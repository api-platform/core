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

use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\DateFilter;
use ApiPlatform\Core\Test\DoctrineMongoDbOdmFilterTestCase;
use ApiPlatform\Core\Tests\Bridge\Doctrine\Common\Filter\DateFilterTestTrait;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class DateFilterTest extends DoctrineMongoDbOdmFilterTestCase
{
    use DateFilterTestTrait;

    protected $filterClass = DateFilter::class;

    public function provideApplyTestData(): array
    {
        return [
            'after (all properties enabled)' => [
                null,
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                    ],
                ],
                [
                    [
                        '$match' => [
                            '$and' => [
                                [
                                    'dummyDate' => [
                                        '$gte' => new \MongoDate(1428192000, 0),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'after but not equals (all properties enabled)' => [
                null,
                [
                    'dummyDate' => [
                        'strictly_after' => '2015-04-05',
                    ],
                ],
                [
                    [
                        '$match' => [
                            '$and' => [
                                [
                                    'dummyDate' => [
                                        '$gt' => new \MongoDate(1428192000, 0),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'after' => [
                [
                    'dummyDate' => null,
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                    ],
                ],
                [
                    [
                        '$match' => [
                            '$and' => [
                                [
                                    'dummyDate' => [
                                        '$gte' => new \MongoDate(1428192000, 0),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'after but not equals' => [
                [
                    'dummyDate' => null,
                ],
                [
                    'dummyDate' => [
                        'strictly_after' => '2015-04-05',
                    ],
                ],
                [
                    [
                        '$match' => [
                            '$and' => [
                                [
                                    'dummyDate' => [
                                        '$gt' => new \MongoDate(1428192000, 0),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'before (all properties enabled)' => [
                null,
                [
                    'dummyDate' => [
                        'before' => '2015-04-05',
                    ],
                ],
                [
                    [
                        '$match' => [
                            '$and' => [
                                [
                                    'dummyDate' => [
                                        '$lte' => new \MongoDate(1428192000, 0),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'before but not equals (all properties enabled)' => [
                null,
                [
                    'dummyDate' => [
                        'strictly_before' => '2015-04-05',
                    ],
                ],
                [
                    [
                        '$match' => [
                            '$and' => [
                                [
                                    'dummyDate' => [
                                        '$lt' => new \MongoDate(1428192000, 0),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'before' => [
                [
                    'dummyDate' => null,
                ],
                [
                    'dummyDate' => [
                        'before' => '2015-04-05',
                    ],
                ],
                [
                    [
                        '$match' => [
                            '$and' => [
                                [
                                    'dummyDate' => [
                                        '$lte' => new \MongoDate(1428192000, 0),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'before but not equals' => [
                [
                    'dummyDate' => null,
                ],
                [
                    'dummyDate' => [
                        'strictly_before' => '2015-04-05',
                    ],
                ],
                [
                    [
                        '$match' => [
                            '$and' => [
                                [
                                    'dummyDate' => [
                                        '$lt' => new \MongoDate(1428192000, 0),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'before + after (all properties enabled)' => [
                null,
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                        'before' => '2015-04-05',
                    ],
                ],
                [
                    [
                        '$match' => [
                            '$and' => [
                                [
                                    'dummyDate' => [
                                        '$lte' => new \MongoDate(1428192000, 0),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        '$match' => [
                            '$and' => [
                                [
                                    'dummyDate' => [
                                        '$gte' => new \MongoDate(1428192000, 0),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'before but not equals + after but not equals (all properties enabled)' => [
                null,
                [
                    'dummyDate' => [
                        'strictly_after' => '2015-04-05',
                        'strictly_before' => '2015-04-05',
                    ],
                ],
                [
                    [
                        '$match' => [
                            '$and' => [
                                [
                                    'dummyDate' => [
                                        '$lt' => new \MongoDate(1428192000, 0),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        '$match' => [
                            '$and' => [
                                [
                                    'dummyDate' => [
                                        '$gt' => new \MongoDate(1428192000, 0),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'before + after' => [
                [
                    'dummyDate' => null,
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                        'before' => '2015-04-05',
                    ],
                ],
                [
                    [
                        '$match' => [
                            '$and' => [
                                [
                                    'dummyDate' => [
                                        '$lte' => new \MongoDate(1428192000, 0),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        '$match' => [
                            '$and' => [
                                [
                                    'dummyDate' => [
                                        '$gte' => new \MongoDate(1428192000, 0),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'before but not equals + after but not equals' => [
                [
                    'dummyDate' => null,
                ],
                [
                    'dummyDate' => [
                        'strictly_after' => '2015-04-05',
                        'strictly_before' => '2015-04-05',
                    ],
                ],
                [
                    [
                        '$match' => [
                            '$and' => [
                                [
                                    'dummyDate' => [
                                        '$lt' => new \MongoDate(1428192000, 0),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        '$match' => [
                            '$and' => [
                                [
                                    'dummyDate' => [
                                        '$gt' => new \MongoDate(1428192000, 0),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'property not enabled' => [
                [
                    'unknown' => null,
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                        'before' => '2015-04-05',
                    ],
                ],
                [],
            ],
            'nested property' => [
                [
                    'relatedDummy.dummyDate' => null,
                ],
                [
                    'relatedDummy.dummyDate' => [
                        'after' => '2015-04-05',
                    ],
                ],
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
                        '$match' => [
                            '$and' => [
                                [
                                    'relatedDummy_lkup.dummyDate' => [
                                        '$gte' => new \MongoDate(1428192000, 0),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'after (exclude_null)' => [
                [
                    'dummyDate' => 'exclude_null',
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                    ],
                ],
                [
                    [
                        '$match' => [
                            'dummyDate' => ['$ne' => null],
                        ],
                    ],
                    [
                        '$match' => [
                            '$and' => [
                                [
                                    'dummyDate' => [
                                        '$gte' => new \MongoDate(1428192000, 0),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'after (include_null_after)' => [
                [
                    'dummyDate' => 'include_null_after',
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                    ],
                ],
                [
                    [
                        '$match' => [
                            '$or' => [
                                [
                                    'dummyDate' => [
                                        '$gte' => new \MongoDate(1428192000, 0),
                                    ],
                                ],
                                [
                                    'dummyDate' => null,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'include null before and after (include_null_before_and_after)' => [
                [
                    'dummyDate' => 'include_null_before_and_after',
                ],
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                    ],
                ],
                [
                    [
                        '$match' => [
                            '$or' => [
                                [
                                    'dummyDate' => [
                                        '$gte' => new \MongoDate(1428192000, 0),
                                    ],
                                ],
                                [
                                    'dummyDate' => null,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'bad date format' => [
                [
                    'dummyDate' => null,
                ],
                [
                    'dummyDate' => [
                        'after' => '1932iur123ufqe',
                    ],
                ],
                [],
            ],
        ];
    }
}
