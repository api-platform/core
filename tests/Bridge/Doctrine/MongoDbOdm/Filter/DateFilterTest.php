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
use MongoDB\BSON\UTCDateTime;

/**
 * @group mongodb
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class DateFilterTest extends DoctrineMongoDbOdmFilterTestCase
{
    use DateFilterTestTrait;

    protected $filterClass = DateFilter::class;

    public function provideApplyTestData(): array
    {
        return array_merge_recursive(
            $this->provideApplyTestArguments(),
            [
                'after (all properties enabled)' => [
                    [
                        [
                            '$match' => [
                                '$and' => [
                                    [
                                        'dummyDate' => [
                                            '$gte' => new UTCDateTime(1428192000000),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'after but not equals (all properties enabled)' => [
                    [
                        [
                            '$match' => [
                                '$and' => [
                                    [
                                        'dummyDate' => [
                                            '$gt' => new UTCDateTime(1428192000000),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'after' => [
                    [
                        [
                            '$match' => [
                                '$and' => [
                                    [
                                        'dummyDate' => [
                                            '$gte' => new UTCDateTime(1428192000000),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'after but not equals' => [
                    [
                        [
                            '$match' => [
                                '$and' => [
                                    [
                                        'dummyDate' => [
                                            '$gt' => new UTCDateTime(1428192000000),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'before (all properties enabled)' => [
                    [
                        [
                            '$match' => [
                                '$and' => [
                                    [
                                        'dummyDate' => [
                                            '$lte' => new UTCDateTime(1428192000000),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'before but not equals (all properties enabled)' => [
                    [
                        [
                            '$match' => [
                                '$and' => [
                                    [
                                        'dummyDate' => [
                                            '$lt' => new UTCDateTime(1428192000000),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'before' => [
                    [
                        [
                            '$match' => [
                                '$and' => [
                                    [
                                        'dummyDate' => [
                                            '$lte' => new UTCDateTime(1428192000000),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'before but not equals' => [
                    [
                        [
                            '$match' => [
                                '$and' => [
                                    [
                                        'dummyDate' => [
                                            '$lt' => new UTCDateTime(1428192000000),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'before + after (all properties enabled)' => [
                    [
                        [
                            '$match' => [
                                '$and' => [
                                    [
                                        'dummyDate' => [
                                            '$lte' => new UTCDateTime(1428192000000),
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
                                            '$gte' => new UTCDateTime(1428192000000),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'before but not equals + after but not equals (all properties enabled)' => [
                    [
                        [
                            '$match' => [
                                '$and' => [
                                    [
                                        'dummyDate' => [
                                            '$lt' => new UTCDateTime(1428192000000),
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
                                            '$gt' => new UTCDateTime(1428192000000),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'before + after' => [
                    [
                        [
                            '$match' => [
                                '$and' => [
                                    [
                                        'dummyDate' => [
                                            '$lte' => new UTCDateTime(1428192000000),
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
                                            '$gte' => new UTCDateTime(1428192000000),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'before but not equals + after but not equals' => [
                    [
                        [
                            '$match' => [
                                '$and' => [
                                    [
                                        'dummyDate' => [
                                            '$lt' => new UTCDateTime(1428192000000),
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
                                            '$gt' => new UTCDateTime(1428192000000),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'property not enabled' => [
                    [],
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
                                '$and' => [
                                    [
                                        'relatedDummy_lkup.dummyDate' => [
                                            '$gte' => new UTCDateTime(1428192000000),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'after (exclude_null)' => [
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
                                            '$gte' => new UTCDateTime(1428192000000),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'after (include_null_after)' => [
                    [
                        [
                            '$match' => [
                                '$or' => [
                                    [
                                        'dummyDate' => [
                                            '$gte' => new UTCDateTime(1428192000000),
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
                        [
                            '$match' => [
                                '$or' => [
                                    [
                                        'dummyDate' => [
                                            '$gte' => new UTCDateTime(1428192000000),
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
                    [],
                ],
            ]
        );
    }
}
