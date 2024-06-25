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

namespace ApiPlatform\Doctrine\Odm\Tests\Filter;

use ApiPlatform\Doctrine\Odm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Odm\Tests\DoctrineMongoDbOdmFilterTestCase;
use ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document\Dummy;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class BooleanFilterTest extends DoctrineMongoDbOdmFilterTestCase
{
    use BooleanFilterTestTrait;

    protected string $filterClass = BooleanFilter::class;
    protected string $resourceClass = Dummy::class;

    public static function provideApplyTestData(): array
    {
        return array_merge_recursive(
            self::provideApplyTestArguments(),
            [
                'string ("true")' => [
                    [
                        [
                            '$match' => ['dummyBoolean' => true],
                        ],
                    ],
                ],
                'string ("false")' => [
                    [
                        [
                            '$match' => ['dummyBoolean' => false],
                        ],
                    ],
                ],
                'non-boolean' => [
                    [],
                ],
                'numeric string ("0")' => [
                    [
                        [
                            '$match' => ['dummyBoolean' => false],
                        ],
                    ],
                ],
                'numeric string ("1")' => [
                    [
                        [
                            '$match' => ['dummyBoolean' => true],
                        ],
                    ],
                ],
                'nested properties' => [
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
                            '$match' => ['relatedDummy_lkup.dummyBoolean' => true],
                        ],
                    ],
                ],
                'numeric string ("1") on non-boolean property' => [
                    [],
                ],
                'numeric string ("0") on non-boolean property' => [
                    [],
                ],
                'string ("true") on non-boolean property' => [
                    [],
                ],
                'string ("false") on non-boolean property' => [
                    [],
                ],
                'mixed boolean, non-boolean and invalid property' => [
                    [
                        [
                            '$match' => ['dummyBoolean' => false],
                        ],
                    ],
                ],
            ]
        );
    }
}
