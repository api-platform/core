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

namespace ApiPlatform\ParameterValidator\Tests\Validator;

use ApiPlatform\ParameterValidator\Validator\MultipleOf;
use PHPUnit\Framework\TestCase;

/**
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class MultipleOfTest extends TestCase
{
    public function testNonDefinedFilter(): void
    {
        $filter = new MultipleOf();

        $this->assertEmpty(
            $filter->validate('some_filter', [], [])
        );
    }

    public function testEmptyQueryParameter(): void
    {
        $request = ['some_filter' => ''];
        $filter = new MultipleOf();

        $this->assertEmpty(
            $filter->validate('some_filter', [], $request)
        );
    }

    /**
     * @group legacy
     */
    public function testNonMatchingParameter(): void
    {
        $request = ['some_filter' => '8'];
        $filter = new MultipleOf();

        $filterDefinition = [
            'swagger' => [
                'multipleOf' => 3,
            ],
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" must multiple of 3'],
            $filter->validate('some_filter', $filterDefinition, $request)
        );
    }

    public function testNonMatchingParameterOpenApi(): void
    {
        $request = ['some_filter' => '8'];
        $filter = new MultipleOf();

        $filterDefinition = [
            'openapi' => [
                'multipleOf' => 3,
            ],
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" must multiple of 3'],
            $filter->validate('some_filter', $filterDefinition, $request)
        );
    }

    /**
     * @group legacy
     */
    public function testMatchingParameter(): void
    {
        $request = ['some_filter' => '8'];
        $filter = new MultipleOf();

        $filterDefinition = [
            'swagger' => [
                'multipleOf' => 4,
            ],
        ];

        $this->assertEmpty(
            $filter->validate('some_filter', $filterDefinition, $request)
        );
    }

    public function testMatchingParameterOpenApi(): void
    {
        $request = ['some_filter' => '8'];
        $filter = new MultipleOf();

        $filterDefinition = [
            'openapi' => [
                'multipleOf' => 4,
            ],
        ];

        $this->assertEmpty(
            $filter->validate('some_filter', $filterDefinition, $request)
        );
    }
}
