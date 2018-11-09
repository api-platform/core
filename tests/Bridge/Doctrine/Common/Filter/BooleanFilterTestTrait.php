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
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
trait BooleanFilterTestTrait
{
    public function testGetDescription()
    {
        $filter = $this->buildFilter([
            'id' => null,
            'name' => null,
            'foo' => null,
            'dummyBoolean' => null,
        ]);

        $this->assertEquals([
            'dummyBoolean' => [
                'property' => 'dummyBoolean',
                'type' => 'bool',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function testGetDescriptionDefaultFields()
    {
        $filter = $this->buildFilter();

        $this->assertEquals([
            'dummyBoolean' => [
                'property' => 'dummyBoolean',
                'type' => 'bool',
                'required' => false,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    private function provideApplyTestArguments(): array
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
            ],
        ];
    }
}
