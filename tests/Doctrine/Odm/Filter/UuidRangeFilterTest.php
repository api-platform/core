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

use ApiPlatform\Doctrine\Odm\Filter\UuidRangeFilter;
use ApiPlatform\Test\DoctrineMongoDbOdmFilterTestCase;
use ApiPlatform\Tests\Doctrine\Common\Filter\UuidRangeFilterTestTrait;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyUuidV6;

/**
 * @group mongodb
 *
 * @author Kai Dederichs <kai.dederichs@protonmail.com>
 */
class UuidRangeFilterTest extends DoctrineMongoDbOdmFilterTestCase
{
    use UuidRangeFilterTestTrait;

    protected $filterClass = UuidRangeFilter::class;
    protected $resourceClass = DummyUuidV6::class;

    public function testGetDescriptionDefaultFields()
    {
        $filter = $this->buildFilter();

        $this->assertEquals([
            'id[between]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'id[gt]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'id[gte]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'id[lt]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'id[lte]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function provideApplyTestData(): array
    {
        return array_merge_recursive(
            $this->provideApplyTestArguments(),
            [
                'between' => [
                    [
                        [
                            '$match' => [
                                '_id' => [
                                    '$gte' => '1ec5c128-f3d2-643a-8b17-68fef707f0bd',
                                    '$lte' => '1ec5c128-f3d4-6514-8d2b-68fef707f0bd',
                                ],
                            ],
                        ],
                    ],
                ],
                'between (same values)' => [
                    [
                        [
                            '$match' => [
                                '_id' => '1ec5c128-f3d2-643a-8b17-68fef707f0bd',
                            ],
                        ],
                    ],
                ],
                'between (too many operands)' => [
                    [],
                ],
                'between (too few operands)' => [
                    [],
                ],
                'between (non-uuid operands)' => [
                    [],
                ],
                'lt' => [
                    [
                        [
                            '$match' => [
                                '_id' => [
                                    '$lt' => '1ec5c128-f3d2-643a-8b17-68fef707f0bd',
                                ],
                            ],
                        ],
                    ],
                ],
                'lt (non-uuid)' => [
                    [],
                ],
                'lte' => [
                    [
                        [
                            '$match' => [
                                '_id' => [
                                    '$lte' => '1ec5c128-f3d2-643a-8b17-68fef707f0bd',
                                ],
                            ],
                        ],
                    ],
                ],
                'lte (non-uuid)' => [
                    [],
                ],
                'gt' => [
                    [
                        [
                            '$match' => [
                                '_id' => [
                                    '$gt' => '1ec5c128-f3d2-643a-8b17-68fef707f0bd',
                                ],
                            ],
                        ],
                    ],
                ],
                'gt (non-uuid)' => [
                    [],
                ],
                'gte' => [
                    [
                        [
                            '$match' => [
                                '_id' => [
                                    '$gte' => '1ec5c128-f3d2-643a-8b17-68fef707f0bd',
                                ],
                            ],
                        ],
                    ],
                ],
                'gte (non-uuid)' => [
                    [],
                ],
                'lte + gte' => [
                    [
                        [
                            '$match' => [
                                '_id' => [
                                    '$gte' => '1ec5c128-f3d2-643a-8b17-68fef707f0bd',
                                ],
                            ],
                        ],
                        [
                            '$match' => [
                                '_id' => [
                                    '$lte' => '1ec5c128-f3d4-6514-8d2b-68fef707f0bd',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
    }
}
