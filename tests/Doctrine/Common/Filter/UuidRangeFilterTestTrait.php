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

namespace ApiPlatform\Tests\Doctrine\Common\Filter;

trait UuidRangeFilterTestTrait
{
    private function provideApplyTestArguments(): array
    {
        return [
            'between' => [
                null,
                [
                    'id' => [
                        'between' => '1ec5c128-f3d2-643a-8b17-68fef707f0bd..1ec5c128-f3d4-6514-8d2b-68fef707f0bd',
                    ],
                ],
            ],
            'between (same values)' => [
                null,
                [
                    'id' => [
                        'between' => '1ec5c128-f3d2-643a-8b17-68fef707f0bd..1ec5c128-f3d2-643a-8b17-68fef707f0bd',
                    ],
                ],
            ],
            'between (too many operands)' => [
                null,
                [
                    'id' => [
                        'between' => '1ec5c128-f3d2-643a-8b17-68fef707f0bd..1ec5c128-f3d4-6514-8d2b-68fef707f0bd..1ec5c128-f3d4-63f2-b845-68fef707f0bd',
                    ],
                ],
            ],
            'between (too few operands)' => [
                null,
                [
                    'id' => [
                        'between' => '1ec5c128-f3d4-6514-8d2b-68fef707f0bd',
                    ],
                ],
            ],
            'between (non-uuid operands)' => [
                null,
                [
                    'id' => [
                        'between' => 'abc..def',
                    ],
                ],
            ],
            'lt' => [
                null,
                [
                    'id' => [
                        'lt' => '1ec5c128-f3d2-643a-8b17-68fef707f0bd',
                    ],
                ],
            ],
            'lt (non-uuid)' => [
                null,
                [
                    'id' => [
                        'lt' => '127.0.0.1',
                    ],
                ],
            ],
            'lte' => [
                null,
                [
                    'id' => [
                        'lte' => '1ec5c128-f3d2-643a-8b17-68fef707f0bd',
                    ],
                ],
            ],
            'lte (non-uuid)' => [
                null,
                [
                    'id' => [
                        'lte' => '127.0.0.1',
                    ],
                ],
            ],
            'gt' => [
                null,
                [
                    'id' => [
                        'gt' => '1ec5c128-f3d2-643a-8b17-68fef707f0bd',
                    ],
                ],
            ],
            'gt (non-uuid)' => [
                null,
                [
                    'id' => [
                        'gt' => '127.0.0.1',
                    ],
                ],
            ],
            'gte' => [
                null,
                [
                    'id' => [
                        'gte' => '1ec5c128-f3d2-643a-8b17-68fef707f0bd',
                    ],
                ],
            ],
            'gte (non-uuid)' => [
                null,
                [
                    'id' => [
                        'gte' => '127.0.0.1',
                    ],
                ],
            ],
            'lte + gte' => [
                null,
                [
                    'id' => [
                        'gte' => '1ec5c128-f3d2-643a-8b17-68fef707f0bd',
                        'lte' => '1ec5c128-f3d4-6514-8d2b-68fef707f0bd',
                    ],
                ],
            ],
        ];
    }
}
