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
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
trait ExistsFilterTestTrait
{
    public function testGetDescription()
    {
        $filter = $filter = $this->buildFilter(['name' => null, 'description' => null]);

        $this->assertEquals([
            'description[exists]' => [
                'property' => 'description',
                'type' => 'bool',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    private function provideApplyTestArguments(): array
    {
        return [
            'valid values' => [
                [
                    'description' => null,
                ],
                [
                    'description' => [
                        'exists' => 'true',
                    ],
                ],
            ],

            'valid values (empty for true)' => [
                [
                    'description' => null,
                ],
                [
                    'description' => [
                        'exists' => '',
                    ],
                ],
            ],

            'valid values (1 for true)' => [
                [
                    'description' => null,
                ],
                [
                    'description' => [
                        'exists' => '1',
                    ],
                ],
            ],

            'invalid values' => [
                [
                    'description' => null,
                ],
                [
                    'description' => [
                        'exists' => 'invalid',
                    ],
                ],
            ],

            'negative values' => [
                [
                    'description' => null,
                ],
                [
                    'description' => [
                        'exists' => 'false',
                    ],
                ],
            ],

            'negative values (0)' => [
                [
                    'description' => null,
                ],
                [
                    'description' => [
                        'exists' => '0',
                    ],
                ],
            ],

            'related values' => [
                [
                    'description' => null,
                    'relatedDummy.name' => null,
                ],
                [
                    'description' => [
                        'exists' => '1',
                    ],
                    'relatedDummy.name' => [
                        'exists' => '1',
                    ],
                ],
            ],

            'not nullable values' => [
                [
                    'description' => null,
                    'name' => null,
                ],
                [
                    'description' => [
                        'exists' => '1',
                    ],
                    'name' => [
                        'exists' => '0',
                    ],
                ],
            ],

            'related collection not empty' => [
                [
                    'description' => null,
                    'relatedDummies' => null,
                ],
                [
                    'description' => [
                        'exists' => '1',
                    ],
                    'relatedDummies' => [
                        'exists' => '1',
                    ],
                ],
            ],

            'related collection empty' => [
                [
                    'description' => null,
                    'relatedDummies' => null,
                ],
                [
                    'description' => [
                        'exists' => '1',
                    ],
                    'relatedDummies' => [
                        'exists' => '0',
                    ],
                ],
            ],

            'related association exists' => [
                [
                    'description' => null,
                    'relatedDummy' => null,
                ],
                [
                    'description' => [
                        'exists' => '1',
                    ],
                    'relatedDummy' => [
                        'exists' => '1',
                    ],
                ],
            ],

            'related association does not exist' => [
                [
                    'description' => null,
                    'relatedDummy' => null,
                ],
                [
                    'description' => [
                        'exists' => '1',
                    ],
                    'relatedDummy' => [
                        'exists' => '0',
                    ],
                ],
            ],

            'related owned association does not exist' => [
                [
                    'relatedOwnedDummy' => null,
                ],
                [
                    'relatedOwnedDummy' => [
                        'exists' => '0',
                    ],
                ],
            ],

            'related owned association exists' => [
                [
                    'relatedOwnedDummy' => null,
                ],
                [
                    'relatedOwnedDummy' => [
                        'exists' => '1',
                    ],
                ],
            ],

            'related owning association does not exist' => [
                [
                    'relatedOwningDummy' => null,
                ],
                [
                    'relatedOwningDummy' => [
                        'exists' => '0',
                    ],
                ],
            ],

            'related owning association exists' => [
                [
                    'relatedOwningDummy' => null,
                ],
                [
                    'relatedOwningDummy' => [
                        'exists' => '1',
                    ],
                ],
            ],
        ];
    }
}
