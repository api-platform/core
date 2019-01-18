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
trait OrderFilterTestTrait
{
    public function testGetDescription()
    {
        $filter = $this->buildFilter(['id' => null, 'name' => null, 'foo' => null]);

        $this->assertEquals([
            'order[id]' => [
                'property' => 'id',
                'type' => 'string',
                'required' => false,
            ],
            'order[name]' => [
                'property' => 'name',
                'type' => 'string',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    private function provideApplyTestArguments(): array
    {
        return [
            'valid values' => [
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'order' => [
                        'id' => 'asc',
                        'name' => 'desc',
                    ],
                ],
            ],
            'invalid values' => [
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'order' => [
                        'id' => 'asc',
                        'name' => 'invalid',
                    ],
                ],
            ],
            'valid values (properties not enabled)' => [
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'order' => [
                        'id' => 'asc',
                        'alias' => 'asc',
                    ],
                ],
            ],
            'invalid values (properties not enabled)' => [
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'order' => [
                        'id' => 'invalid',
                        'name' => 'asc',
                        'alias' => 'invalid',
                    ],
                ],
            ],
            'invalid property (property not enabled)' => [
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'order' => [
                        'unknown' => 'asc',
                    ],
                ],
            ],
            'invalid property (property enabled)' => [
                [
                    'id' => null,
                    'name' => null,
                    'unknown' => null,
                ],
                [
                    'order' => [
                        'unknown' => 'asc',
                    ],
                ],
            ],
            'custom order parameter name' => [
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'order' => [
                        'id' => 'asc',
                        'name' => 'asc',
                    ],
                    'customOrder' => [
                        'name' => 'desc',
                    ],
                ],
            ],
            'valid values (all properties enabled)' => [
                null,
                [
                    'order' => [
                        'id' => 'asc',
                        'name' => 'asc',
                    ],
                ],
            ],
            'nested property' => [
                [
                    'id' => null,
                    'name' => null,
                    'relatedDummy.symfony' => null,
                ],
                [
                    'order' => [
                        'id' => 'asc',
                        'name' => 'desc',
                        'relatedDummy.symfony' => 'desc',
                    ],
                ],
            ],
            'empty values with default sort direction' => [
                [
                    'id' => 'asc',
                    'name' => 'desc',
                ],
                [
                    'order' => [
                        'id' => null,
                        'name' => null,
                    ],
                ],
            ],
            'nulls_smallest (asc)' => [
                [
                    'dummyDate' => [
                        'nulls_comparison' => 'nulls_smallest',
                    ],
                    'name' => null,
                ],
                [
                    'order' => [
                        'dummyDate' => 'asc',
                        'name' => 'desc',
                    ],
                ],
            ],
            'nulls_smallest (desc)' => [
                [
                    'dummyDate' => [
                        'nulls_comparison' => 'nulls_smallest',
                    ],
                    'name' => null,
                ],
                [
                    'order' => [
                        'dummyDate' => 'desc',
                        'name' => 'desc',
                    ],
                ],
            ],
            'nulls_largest (asc)' => [
                [
                    'dummyDate' => [
                        'nulls_comparison' => 'nulls_largest',
                    ],
                    'name' => null,
                ],
                [
                    'order' => [
                        'dummyDate' => 'asc',
                        'name' => 'desc',
                    ],
                ],
            ],
            'nulls_largest (desc)' => [
                [
                    'dummyDate' => [
                        'nulls_comparison' => 'nulls_largest',
                    ],
                    'name' => null,
                ],
                [
                    'order' => [
                        'dummyDate' => 'desc',
                        'name' => 'desc',
                    ],
                ],
            ],
            'not having order should not throw a deprecation (select unchanged)' => [
                [
                    'id' => null,
                    'name' => null,
                ],
                [
                    'name' => 'q',
                ],
            ],
            'not nullable relation will be a LEFT JOIN' => [
                [
                    'relatedDummy.name' => 'ASC',
                ],
                [
                    'order' => ['relatedDummy.name' => 'ASC'],
                ],
            ],
        ];
    }
}
