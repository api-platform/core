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
trait NumericFilterTestTrait
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
            'id' => [
                'property' => 'id',
                'type' => 'int',
                'required' => false,
                'is_collection' => false,
            ],
            'id[]' => [
                'property' => 'id',
                'type' => 'int',
                'required' => false,
                'is_collection' => true,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    public function testGetDescriptionDefaultFields()
    {
        $filter = $this->buildFilter();

        $this->assertEquals([
            'id' => [
                'property' => 'id',
                'type' => 'int',
                'required' => false,
                'is_collection' => false,
            ],
            'id[]' => [
                'property' => 'id',
                'type' => 'int',
                'required' => false,
                'is_collection' => true,
            ],
            'dummyFloat' => [
                'property' => 'dummyFloat',
                'type' => 'float',
                'required' => false,
                'is_collection' => false,
            ],
            'dummyFloat[]' => [
                'property' => 'dummyFloat',
                'type' => 'float',
                'required' => false,
                'is_collection' => true,
            ],
            'dummyPrice' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
                'is_collection' => false,
            ],
            'dummyPrice[]' => [
                'property' => 'dummyPrice',
                'type' => 'string',
                'required' => false,
                'is_collection' => true,
            ],
        ], $filter->getDescription($this->resourceClass));
    }

    private function provideApplyTestArguments(): array
    {
        return [
            'numeric string (positive integer)' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyPrice' => null,
                ],
                [
                    'dummyPrice' => '21',
                ],
            ],
            'multiple numeric string (positive integer)' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyPrice' => null,
                ],
                [
                    'dummyPrice' => ['21', '22'],
                ],
            ],
            'multiple numeric string with one invalid property key' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyPrice' => null,
                ],
                [
                    'dummyPrice' => ['invalid' => '21', '22'],
                ],
            ],
            'multiple numeric string with invalid value keys' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyPrice' => null,
                ],
                [
                    'dummyPrice' => ['invalid' => '21', 'invalid2' => '22'],
                ],
            ],
            'multiple non-numeric' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyPrice' => null,
                ],
                [
                    'dummyPrice' => ['test', 'invalid'],
                ],
            ],
            'numeric string (negative integer)' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyPrice' => null,
                ],
                [
                    'dummyPrice' => '-21',
                ],
            ],
            'non-numeric' => [
                [
                    'id' => null,
                ],
                [
                    'id' => 'toto',
                ],
            ],
            'numeric string ("0")' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyPrice' => null,
                ],
                [
                    'dummyPrice' => 0,
                ],
            ],
            'nested property' => [
                [
                    'id' => null,
                    'name' => null,
                    'relatedDummy.id' => null,
                ],
                [
                    'relatedDummy.id' => 0,
                ],
            ],
            'mixed numeric and non-numeric' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyPrice' => null,
                ],
                [
                    'dummyPrice' => 10,
                    'name' => '15toto',
                ],
            ],
            'mixed numeric, non-numeric and invalid property' => [
                [
                    'id' => null,
                    'name' => null,
                    'dummyPrice' => null,
                ],
                [
                    'toto' => 'toto',
                    'name' => 'gerard',
                    'dummyPrice' => '0',
                ],
            ],
        ];
    }
}
