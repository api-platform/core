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

namespace ApiPlatform\Tests\Api\QueryParameterValidator\Validator;

use ApiPlatform\Api\QueryParameterValidator\Validator\Enum;
use PHPUnit\Framework\TestCase;

/**
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class EnumTest extends TestCase
{
    public function testNonDefinedFilter(): void
    {
        $filter = new Enum();

        $this->assertEmpty(
            $filter->validate('some_filter', [], [])
        );
    }

    public function testEmptyQueryParameter(): void
    {
        $filter = new Enum();

        $this->assertEmpty(
            $filter->validate('some_filter', [], ['some_filter' => ''])
        );
    }

    public function testNonMatchingParameter(): void
    {
        $filter = new Enum();

        $filterDefinition = [
            'swagger' => [
                'enum' => ['foo', 'bar'],
            ],
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" must be one of "foo, bar"'],
            $filter->validate('some_filter', $filterDefinition, ['some_filter' => 'foobar'])
        );
    }

    public function testMatchingParameter(): void
    {
        $filter = new Enum();

        $filterDefinition = [
            'swagger' => [
                'enum' => ['foo', 'bar'],
            ],
        ];

        $this->assertEmpty(
            $filter->validate('some_filter', $filterDefinition, ['some_filter' => 'foo'])
        );
    }
}
