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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class ExistsFilterTest extends AbstractFilterTest
{
    protected $filterClass = ExistsFilter::class;

    public function testGetDescription()
    {
        $filter = new ExistsFilter($this->managerRegistry, null, null, ['name' => null, 'description' => null]);

        $this->assertEquals([
            'description[exists]' => [
                'property' => 'description',
                'type' => 'bool',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function testGetDescriptionDefaultFields()
    {
        $filter = new ExistsFilter($this->managerRegistry);

        $this->assertEquals([
            'alias[exists]' => [
                'property' => 'alias',
                'type' => 'bool',
                'required' => false,
            ],
            'description[exists]' => [
                'property' => 'description',
                'type' => 'bool',
                'required' => false,
            ],
            'dummy[exists]' => [
                'property' => 'dummy',
                'type' => 'bool',
                'required' => false,
            ],
            'dummyDate[exists]' => [
                'property' => 'dummyDate',
                'type' => 'bool',
                'required' => false,
            ],
            'dummyFloat[exists]' => [
                'property' => 'dummyFloat',
                'type' => 'bool',
                'required' => false,
            ],
            'dummyPrice[exists]' => [
                'property' => 'dummyPrice',
                'type' => 'bool',
                'required' => false,
            ],
            'jsonData[exists]' => [
                'property' => 'jsonData',
                'type' => 'bool',
                'required' => false,
            ],
            'nameConverted[exists]' => [
                'property' => 'nameConverted',
                'type' => 'bool',
                'required' => false,
            ],
            'dummyBoolean[exists]' => [
                'property' => 'dummyBoolean',
                'type' => 'bool',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function provideApplyTestData(): array
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
                sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL', Dummy::class),
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
                sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL', Dummy::class),
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
                sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL', Dummy::class),
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
                sprintf('SELECT o FROM %s o', Dummy::class),
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
                sprintf('SELECT o FROM %s o WHERE o.description IS NULL', Dummy::class),
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
                sprintf('SELECT o FROM %s o WHERE o.description IS NULL', Dummy::class),
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
                sprintf('SELECT o FROM %s o INNER JOIN o.relatedDummy relatedDummy_a1 WHERE o.description IS NOT NULL AND relatedDummy_a1.name IS NOT NULL', Dummy::class),
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
                sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL', Dummy::class),
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
                sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL AND o.relatedDummies IS NOT EMPTY', Dummy::class),
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
                sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL AND o.relatedDummies IS EMPTY', Dummy::class),
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
                sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL AND o.relatedDummy IS NOT NULL', Dummy::class),
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
                sprintf('SELECT o FROM %s o WHERE o.description IS NOT NULL AND o.relatedDummy IS NULL', Dummy::class),
            ],
        ];
    }
}
