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
            'exists[description]' => [
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
                    'exists' => [
                        'description' => 'true',
                    ],
                ],
            ],

            'valid values (empty for true)' => [
                [
                    'description' => null,
                ],
                [
                    'exists' => [
                        'description' => '',
                    ],
                ],
            ],

            'valid values (1 for true)' => [
                [
                    'description' => null,
                ],
                [
                    'exists' => [
                        'description' => '1',
                    ],
                ],
            ],

            'invalid values' => [
                [
                    'description' => null,
                ],
                [
                    'exists' => [
                        'description' => 'invalid',
                    ],
                ],
            ],

            'negative values' => [
                [
                    'description' => null,
                ],
                [
                    'exists' => [
                        'description' => 'false',
                    ],
                ],
            ],

            'negative values (0)' => [
                [
                    'description' => null,
                ],
                [
                    'exists' => [
                        'description' => '0',
                    ],
                ],
            ],

            'multiple values (true and true)' => [
                [
                    'alias' => null,
                    'description' => null,
                ],
                [
                    'exists' => [
                        'alias' => 'true',
                        'description' => 'true',
                    ],
                ],
            ],

            'multiple values (1 and 0)' => [
                [
                    'alias' => null,
                    'description' => null,
                ],
                [
                    'exists' => [
                        'alias' => '1',
                        'description' => '0',
                    ],
                ],
            ],

            'multiple values (false and 0)' => [
                [
                    'alias' => null,
                    'description' => null,
                ],
                [
                    'exists' => [
                        'alias' => 'false',
                        'description' => '0',
                    ],
                ],
            ],

            'custom exists parameter name' => [
                [
                    'alias' => null,
                    'description' => null,
                ],
                [
                    'exists' => [
                        'alias' => 'true',
                    ],
                    'customExists' => [
                        'description' => 'true',
                    ],
                ],
            ],

            'related values' => [
                [
                    'description' => null,
                    'relatedDummy.name' => null,
                ],
                [
                    'exists' => [
                        'description' => '1',
                        'relatedDummy.name' => '1',
                    ],
                ],
            ],

            'not nullable values' => [
                [
                    'description' => null,
                    'name' => null,
                ],
                [
                    'exists' => [
                        'description' => '1',
                        'name' => '0',
                    ],
                ],
            ],

            'related collection not empty' => [
                [
                    'description' => null,
                    'relatedDummies' => null,
                ],
                [
                    'exists' => [
                        'description' => '1',
                        'relatedDummies' => '1',
                    ],
                ],
            ],

            'related collection empty' => [
                [
                    'description' => null,
                    'relatedDummies' => null,
                ],
                [
                    'exists' => [
                        'description' => '1',
                        'relatedDummies' => '0',
                    ],
                ],
            ],

            'related association exists' => [
                [
                    'description' => null,
                    'relatedDummy' => null,
                ],
                [
                    'exists' => [
                        'description' => '1',
                        'relatedDummy' => '1',
                    ],
                ],
            ],

            'related association does not exist' => [
                [
                    'description' => null,
                    'relatedDummy' => null,
                ],
                [
                    'exists' => [
                        'description' => '1',
                        'relatedDummy' => '0',
                    ],
                ],
            ],

            'related owned association does not exist' => [
                [
                    'relatedOwnedDummy' => null,
                ],
                [
                    'exists' => [
                        'relatedOwnedDummy' => '0',
                    ],
                ],
            ],

            'related owned association exists' => [
                [
                    'relatedOwnedDummy' => null,
                ],
                [
                    'exists' => [
                        'relatedOwnedDummy' => '1',
                    ],
                ],
            ],

            'related owning association does not exist' => [
                [
                    'relatedOwningDummy' => null,
                ],
                [
                    'exists' => [
                        'relatedOwningDummy' => '0',
                    ],
                ],
            ],

            'related owning association exists' => [
                [
                    'relatedOwningDummy' => null,
                ],
                [
                    'exists' => [
                        'relatedOwningDummy' => '1',
                    ],
                ],
            ],
        ];
    }
}
