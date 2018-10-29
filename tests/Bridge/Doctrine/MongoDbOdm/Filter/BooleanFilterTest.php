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

use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\BooleanFilter;
use ApiPlatform\Core\Test\DoctrineMongoDbOdmFilterTestCase;
use ApiPlatform\Core\Tests\Bridge\Doctrine\Common\Filter\BooleanFilterTestTrait;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class BooleanFilterTest extends DoctrineMongoDbOdmFilterTestCase
{
    use BooleanFilterTestTrait;

    protected $filterClass = BooleanFilter::class;

    public function provideApplyTestData(): array
    {
        return [
            'string ("true")' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBoolean' => null,
                ],
                [
                    'dummyBoolean' => 'true',
                ],
                [
                    [
                        '$match' => ['dummyBoolean' => true],
                    ],
                ],
            ],
            'string ("false")' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBoolean' => null,
                ],
                [
                    'dummyBoolean' => 'false',
                ],
                [
                    [
                        '$match' => ['dummyBoolean' => false],
                    ],
                ],
            ],
            'non-boolean' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBoolean' => null,
                ],
                [
                    'dummyBoolean' => 'toto',
                ],
                [],
            ],
            'numeric string ("0")' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBoolean' => null,
                ],
                [
                    'dummyBoolean' => '0',
                ],
                [
                    [
                        '$match' => ['dummyBoolean' => false],
                    ],
                ],
            ],
            'numeric string ("1")' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBoolean' => null,
                ],
                [
                    'dummyBoolean' => '1',
                ],
                [
                    [
                        '$match' => ['dummyBoolean' => true],
                    ],
                ],
            ],
            'nested properties' => [
                [
                    'id' => null,
                    'name' => null,
                    'relatedDummy.dummyBoolean' => null,
                ],
                [
                    'relatedDummy.dummyBoolean' => '1',
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
                        '$match' => ['relatedDummy_lkup.dummyBoolean' => true],
                    ],
                ],
            ],
            'numeric string ("1") on non-boolean property' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBoolean' => null,
                ],
                [
                    'name' => '1',
                ],
                [],
            ],
            'numeric string ("0") on non-boolean property' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBoolean' => null,
                ],
                [
                    'name' => '0',
                ],
                [],
            ],
            'string ("true") on non-boolean property' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBoolean' => null,
                ],
                [
                    'name' => 'true',
                ],
                [],
            ],
            'string ("false") on non-boolean property' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBoolean' => null,
                ],
                [
                    'name' => 'false',
                ],
                [],
            ],
            'mixed boolean, non-boolean and invalid property' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBoolean' => null,
                ],
                [
                    'dummyBoolean' => 'false',
                    'toto' => 'toto',
                    'name' => 'true',
                    'id' => '0',
                ],
                [
                    [
                        '$match' => ['dummyBoolean' => false],
                    ],
                ],
            ],
        ];
    }
}
