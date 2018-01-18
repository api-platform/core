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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;

/**
 * @author Lee Siong Chan <ahlee2326@me.com>
 */
class RangeFilterTest extends AbstractFilterTest
{
    protected $filterClass = RangeFilter::class;

    public function testGetDescription()
    {
        $filter = new RangeFilter($this->managerRegistry);

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
            'name[between]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
            ],
            'name[gt]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
            ],
            'name[gte]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
            ],
            'name[lt]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
            ],
            'name[lte]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
            ],
            'alias[between]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
            ],
            'alias[gt]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
            ],
            'alias[gte]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
            ],
            'alias[lt]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
            ],
            'alias[lte]' => [
                'property' => 'alias',
                'type' => 'string',
                'required' => false,
            ],
            'description[between]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
            ],
            'description[gt]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
            ],
            'description[gte]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
            ],
            'description[lt]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
            ],
            'description[lte]' => [
                'property' => 'description',
                'type' => 'string',
                'required' => false,
            ],
            'dummy[between]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
            ],
            'dummy[gt]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
            ],
            'dummy[gte]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
            ],
            'dummy[lt]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
            ],
            'dummy[lte]' => [
                'property' => 'dummy',
                'type' => 'string',
                'required' => false,
            ],
            'dummyDate[between]' => [
                'property' => 'dummyDate',
                'type' => 'string',
                'required' => false,
            ],
            'dummyDate[gt]' => [
                'property' => 'dummyDate',
                'type' => 'string',
                'required' => false,
            ],
            'dummyDate[gte]' => [
                'property' => 'dummyDate',
                'type' => 'string',
                'required' => false,
            ],
            'dummyDate[lt]' => [
                'property' => 'dummyDate',
                'type' => 'string',
                'required' => false,
            ],
            'dummyDate[lte]' => [
                'property' => 'dummyDate',
                'type' => 'string',
                'required' => false,
            ],
            'dummyFloat[between]' => [
                'property' => 'dummyFloat',
                'type' => 'string',
                'required' => false,
            ],
            'dummyFloat[gt]' => [
                'property' => 'dummyFloat',
                'type' => 'string',
                'required' => false,
            ],
            'dummyFloat[gte]' => [
                'property' => 'dummyFloat',
                'type' => 'string',
                'required' => false,
            ],
            'dummyFloat[lt]' => [
                'property' => 'dummyFloat',
                'type' => 'string',
                'required' => false,
            ],
            'dummyFloat[lte]' => [
                'property' => 'dummyFloat',
                'type' => 'string',
                'required' => false,
            ],
            'dummyPrice[between]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
            ],
            'dummyPrice[gt]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
            ],
            'dummyPrice[gte]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
            ],
            'dummyPrice[lt]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
            ],
            'dummyPrice[lte]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
            ],
            'jsonData[between]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
            ],
            'jsonData[gt]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
            ],
            'jsonData[gte]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
            ],
            'jsonData[lt]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
            ],
            'jsonData[lte]' => [
                'property' => 'jsonData',
                'type' => 'string',
                'required' => false,
            ],
            'nameConverted[between]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
            ],
            'nameConverted[gt]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
            ],
            'nameConverted[gte]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
            ],
            'nameConverted[lt]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
            ],
            'nameConverted[lte]' => [
                'property' => 'nameConverted',
                'type' => 'string',
                'required' => false,
            ],
            'dummyBoolean[between]' => [
                'property' => 'dummyBoolean',
                'type' => 'string',
                'required' => false,
            ],
            'dummyBoolean[gt]' => [
                'property' => 'dummyBoolean',
                'type' => 'string',
                'required' => false,
            ],
            'dummyBoolean[gte]' => [
                'property' => 'dummyBoolean',
                'type' => 'string',
                'required' => false,
            ],
            'dummyBoolean[lt]' => [
                'property' => 'dummyBoolean',
                'type' => 'string',
                'required' => false,
            ],
            'dummyBoolean[lte]' => [
                'property' => 'dummyBoolean',
                'type' => 'string',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function provideApplyTestData(): array
    {
        return [
            'between' => [
                null,
                [
                    'dummyPrice' => [
                        'between' => '9.99..15.99',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyPrice BETWEEN :dummyPrice_p1_1 AND :dummyPrice_p1_2', Dummy::class),
            ],
            'between (too many operands)' => [
                null,
                [
                    'dummyPrice' => [
                        'between' => '9.99..15.99..99.99',
                    ],
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
            ],
            'between (too few operands)' => [
                null,
                [
                    'dummyPrice' => [
                        'between' => '15.99',
                    ],
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
            ],
            'between (non-numeric operands)' => [
                null,
                [
                    'dummyPrice' => [
                        'between' => 'abc..def',
                    ],
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
            ],
            'lt' => [
                null,
                [
                    'dummyPrice' => [
                        'lt' => '9.99',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyPrice < :dummyPrice_p1', Dummy::class),
            ],
            'lt (non-numeric)' => [
                null,
                [
                    'dummyPrice' => [
                        'lt' => '127.0.0.1',
                    ],
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
            ],
            'lte' => [
                null,
                [
                    'dummyPrice' => [
                        'lte' => '9.99',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyPrice <= :dummyPrice_p1', Dummy::class),
            ],
            'lte (non-numeric)' => [
                null,
                [
                    'dummyPrice' => [
                        'lte' => '127.0.0.1',
                    ],
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
            ],
            'gt' => [
                null,
                [
                    'dummyPrice' => [
                        'gt' => '9.99',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyPrice > :dummyPrice_p1', Dummy::class),
            ],
            'gt (non-numeric)' => [
                null,
                [
                    'dummyPrice' => [
                        'gt' => '127.0.0.1',
                    ],
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
            ],
            'gte' => [
                null,
                [
                    'dummyPrice' => [
                        'gte' => '9.99',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyPrice >= :dummyPrice_p1', Dummy::class),
            ],
            'gte (non-numeric)' => [
                null,
                [
                    'dummyPrice' => [
                        'gte' => '127.0.0.1',
                    ],
                ],
                sprintf('SELECT o FROM %s o', Dummy::class),
            ],
            'lte + gte' => [
                null,
                [
                    'dummyPrice' => [
                        'gte' => '9.99',
                        'lte' => '19.99',
                    ],
                ],
                sprintf('SELECT o FROM %s o WHERE o.dummyPrice >= :dummyPrice_p1 AND o.dummyPrice <= :dummyPrice_p2', Dummy::class),
            ],
        ];
    }
}
