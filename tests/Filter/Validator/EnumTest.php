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
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class EnumTest extends TestCase
{
    public function testNonDefinedFilter()
    {
        $request = new Request();
        $filter = new Enum();

        $this->assertEmpty(
            $filter->validate('some_filter', [], $request)
        );
    }

    public function testEmptyQueryParameter()
    {
        $request = new Request(['some_filter' => '']);
        $filter = new Enum();

        $this->assertEmpty(
            $filter->validate('some_filter', [], $request)
        );
    }

    public function testNonMatchingParameter()
    {
        $request = new Request(['some_filter' => 'foobar']);
        $filter = new Enum();

        $filterDefinition = [
            'swagger' => [
                'enum' => ['foo', 'bar'],
            ],
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" must be one of "foo, bar"'],
            $filter->validate('some_filter', $filterDefinition, $request)
        );
    }

    public function testMatchingParameter()
    {
        $request = new Request(['some_filter' => 'foo']);
        $filter = new Enum();

        $filterDefinition = [
            'swagger' => [
                'enum' => ['foo', 'bar'],
            ],
        ];

        $this->assertEmpty(
            $filter->validate('some_filter', $filterDefinition, $request)
        );
    }
}
