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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Common\Filter;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Vincent CHALAMON <vincentchalamon@gmail.com>
 */
trait DateFilterTestTrait
{
    public function testGetDescription()
    {
        $filter = $this->buildFilter();

        $this->assertEquals([
            'dummyDate[before]' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
            ],
            'dummyDate[strictly_before]' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
            ],
            'dummyDate[after]' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
            ],
            'dummyDate[strictly_after]' => [
                'property' => 'dummyDate',
                'type' => 'DateTimeInterface',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    private function provideApplyTestArguments(): array
    {
        return [
            'after (all properties enabled)' => [
                null,
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
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
            ],
            'before (all properties enabled)' => [
                null,
                [
                    'dummyDate' => [
                        'before' => '2015-04-05',
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
            ],
            'before + after (all properties enabled)' => [
                null,
                [
                    'dummyDate' => [
                        'after' => '2015-04-05',
                        'before' => '2015-04-05',
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
            ],
        ];
    }
}
