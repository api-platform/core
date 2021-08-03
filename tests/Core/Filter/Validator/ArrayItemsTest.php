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

namespace ApiPlatform\Core\Tests\Filter\Validator;

use ApiPlatform\Core\Filter\Validator\ArrayItems;
use PHPUnit\Framework\TestCase;

/**
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class ArrayItemsTest extends TestCase
{
    public function testNonDefinedFilter()
    {
        $request = [];
        $filter = new ArrayItems();

        $this->assertEmpty(
            $filter->validate('some_filter', [], $request)
        );
    }

    public function testEmptyQueryParameter()
    {
        $request = ['some_filter' => ''];
        $filter = new ArrayItems();

        $this->assertEmpty(
            $filter->validate('some_filter', [], $request)
        );
    }

    public function testNonMatchingParameter()
    {
        $filter = new ArrayItems();

        $filterDefinition = [
            'swagger' => [
                'maxItems' => 3,
                'minItems' => 2,
            ],
        ];

        $request = ['some_filter' => ['foo', 'bar', 'bar', 'foo']];
        $this->assertEquals(
            ['Query parameter "some_filter" must contain less than 3 values'],
            $filter->validate('some_filter', $filterDefinition, $request)
        );

        $request = ['some_filter' => ['foo']];
        $this->assertEquals(
            ['Query parameter "some_filter" must contain more than 2 values'],
            $filter->validate('some_filter', $filterDefinition, $request)
        );
    }

    public function testMatchingParameter()
    {
        $filter = new ArrayItems();

        $filterDefinition = [
            'swagger' => [
                'maxItems' => 3,
                'minItems' => 2,
            ],
        ];

        $request = ['some_filter' => ['foo', 'bar']];
        $this->assertEmpty(
            $filter->validate('some_filter', $filterDefinition, $request)
        );

        $request = ['some_filter' => ['foo', 'bar', 'baz']];
        $this->assertEmpty(
            $filter->validate('some_filter', $filterDefinition, $request)
        );
    }

    public function testNonMatchingUniqueItems()
    {
        $filter = new ArrayItems();

        $filterDefinition = [
            'swagger' => [
                'uniqueItems' => true,
            ],
        ];

        $request = ['some_filter' => ['foo', 'bar', 'bar', 'foo']];
        $this->assertEquals(
            ['Query parameter "some_filter" must contain unique values'],
            $filter->validate('some_filter', $filterDefinition, $request)
        );
    }

    public function testMatchingUniqueItems()
    {
        $filter = new ArrayItems();

        $filterDefinition = [
            'swagger' => [
                'uniqueItems' => true,
            ],
        ];

        $request = ['some_filter' => ['foo', 'bar', 'baz']];
        $this->assertEmpty(
            $filter->validate('some_filter', $filterDefinition, $request)
        );
    }

    public function testSeparators()
    {
        $filter = new ArrayItems();

        $filterDefinition = [
            'swagger' => [
                'maxItems' => 2,
                'uniqueItems' => true,
                'collectionFormat' => 'csv',
            ],
        ];

        $request = ['some_filter' => 'foo,bar,bar'];
        $this->assertEquals(
            [
                'Query parameter "some_filter" must contain less than 2 values',
                'Query parameter "some_filter" must contain unique values',
            ],
            $filter->validate('some_filter', $filterDefinition, $request)
        );

        $filterDefinition['swagger']['collectionFormat'] = 'ssv';
        $this->assertEmpty(
            $filter->validate('some_filter', $filterDefinition, $request)
        );

        $filterDefinition['swagger']['collectionFormat'] = 'ssv';
        $request = ['some_filter' => 'foo bar bar'];
        $this->assertEquals(
            [
                'Query parameter "some_filter" must contain less than 2 values',
                'Query parameter "some_filter" must contain unique values',
            ],
            $filter->validate('some_filter', $filterDefinition, $request)
        );

        $filterDefinition['swagger']['collectionFormat'] = 'tsv';
        $request = ['some_filter' => 'foo\tbar\tbar'];
        $this->assertEquals(
            [
                'Query parameter "some_filter" must contain less than 2 values',
                'Query parameter "some_filter" must contain unique values',
            ],
            $filter->validate('some_filter', $filterDefinition, $request)
        );

        $filterDefinition['swagger']['collectionFormat'] = 'pipes';
        $request = ['some_filter' => 'foo|bar|bar'];
        $this->assertEquals(
            [
                'Query parameter "some_filter" must contain less than 2 values',
                'Query parameter "some_filter" must contain unique values',
            ],
            $filter->validate('some_filter', $filterDefinition, $request)
        );
    }

    public function testSeparatorsUnknownSeparator()
    {
        $filter = new ArrayItems();

        $filterDefinition = [
            'swagger' => [
                'maxItems' => 2,
                'uniqueItems' => true,
                'collectionFormat' => 'unknownFormat',
            ],
        ];
        $request = ['some_filter' => 'foo,bar,bar'];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown collection format unknownFormat');

        $filter->validate('some_filter', $filterDefinition, $request);
    }
}
