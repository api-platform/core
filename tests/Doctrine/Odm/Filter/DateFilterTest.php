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

use ApiPlatform\Doctrine\Odm\Filter\DateFilter;
use ApiPlatform\Test\DoctrineMongoDbOdmFilterTestCase;
use ApiPlatform\Tests\Doctrine\Common\Filter\DateFilterTestTrait;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy;
use MongoDB\BSON\UTCDateTime;

/**
 * @group mongodb
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class DateFilterTest extends DoctrineMongoDbOdmFilterTestCase
{
    use DateFilterTestTrait;

    protected string $filterClass = DateFilter::class;
    protected string $resourceClass = Dummy::class;

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
                                            '$gte' => new UTCDateTime(1_428_192_000_000),
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
                                            '$gt' => new UTCDateTime(1_428_192_000_000),
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
                                            '$gte' => new UTCDateTime(1_428_192_000_000),
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
                                            '$gt' => new UTCDateTime(1_428_192_000_000),
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
                                            '$lte' => new UTCDateTime(1_428_192_000_000),
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
                                            '$lt' => new UTCDateTime(1_428_192_000_000),
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
                                            '$lte' => new UTCDateTime(1_428_192_000_000),
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
                                            '$lt' => new UTCDateTime(1_428_192_000_000),
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
                                            '$lte' => new UTCDateTime(1_428_192_000_000),
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
                                            '$gte' => new UTCDateTime(1_428_192_000_000),
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
                                            '$lt' => new UTCDateTime(1_428_192_000_000),
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
                                            '$gt' => new UTCDateTime(1_428_192_000_000),
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
                                            '$lte' => new UTCDateTime(1_428_192_000_000),
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
                                            '$gte' => new UTCDateTime(1_428_192_000_000),
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
                                            '$lt' => new UTCDateTime(1_428_192_000_000),
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
                                            '$gt' => new UTCDateTime(1_428_192_000_000),
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
                                            '$gte' => new UTCDateTime(1_428_192_000_000),
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
                                            '$gte' => new UTCDateTime(1_428_192_000_000),
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
                                            '$gte' => new UTCDateTime(1_428_192_000_000),
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
                                            '$gte' => new UTCDateTime(1_428_192_000_000),
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
