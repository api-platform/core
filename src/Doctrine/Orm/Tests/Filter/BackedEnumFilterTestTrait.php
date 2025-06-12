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

namespace ApiPlatform\Doctrine\Orm\Tests\Filter;

/**
 * @author Rémi Marseille <marseille.remi@gmail.com>
 */
trait BackedEnumFilterTestTrait
{
    public function testGetDescription(): void
    {
        $filter = $this->buildFilter([
            'id' => null,
            'name' => null,
            'foo' => null,
            'dummyBackedEnum' => null,
        ]);

        $this->assertEquals([
            'dummyBackedEnum' => [
                'property' => 'dummyBackedEnum',
                'type' => 'string',
                'required' => false,
                'is_collection' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => ['one', 'two'],
                ],
            ],
            'dummyBackedEnum[]' => [
                'property' => 'dummyBackedEnum',
                'type' => 'string',
                'required' => false,
                'is_collection' => true,
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => ['one', 'two'],
                    ],
                ],
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function testGetDescriptionDefaultFields(): void
    {
        $filter = $this->buildFilter();

        $this->assertEquals([
            'dummyBackedEnum' => [
                'property' => 'dummyBackedEnum',
                'type' => 'string',
                'required' => false,
                'is_collection' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => ['one', 'two'],
                ],
            ],
            'dummyBackedEnum[]' => [
                'property' => 'dummyBackedEnum',
                'type' => 'string',
                'required' => false,
                'is_collection' => true,
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => ['one', 'two'],
                    ],
                ],
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    private static function provideApplyTestArguments(): array
    {
        return [
            'valid case' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBackedEnum' => null,
                ],
                [
                    'dummyBackedEnum' => 'one',
                ],
            ],
            'invalid case' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBackedEnum' => null,
                ],
                [
                    'dummyBackedEnum' => 'zero',
                ],
            ],
            'valid case for nested property' => [
                [
                    'id' => null,
                    'name' => null,
                    'relatedDummy.dummyBackedEnum' => null,
                ],
                [
                    'relatedDummy.dummyBackedEnum' => 'two',
                ],
            ],
            'invalid case for nested property' => [
                [
                    'id' => null,
                    'name' => null,
                    'relatedDummy.dummyBackedEnum' => null,
                ],
                [
                    'relatedDummy.dummyBackedEnum' => 'foo',
                ],
            ],
            'valid case (multiple values)' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyBackedEnum' => null,
                ],
                [
                    'dummyBackedEnum' => [
                        'one',
                        'two',
                    ],
                ],
            ],
        ];
    }
}
