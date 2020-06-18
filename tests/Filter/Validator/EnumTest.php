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

use ApiPlatform\Core\Filter\Validator\Enum;
use PHPUnit\Framework\TestCase;

/**
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class EnumTest extends TestCase
{
    public function testNonDefinedFilter()
    {
        $filter = new Enum();

        $this->assertEmpty(
            $filter->validate('some_filter', [], [])
        );
    }

    public function testEmptyQueryParameter()
    {
        $filter = new Enum();

        $this->assertEmpty(
            $filter->validate('some_filter', [], ['some_filter' => ''])
        );
    }

    public function testNonMatchingParameter()
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

    public function testMatchingParameter()
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
