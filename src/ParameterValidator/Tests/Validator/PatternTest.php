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

use ApiPlatform\ParameterValidator\Validator\Pattern;
use PHPUnit\Framework\TestCase;

/**
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class PatternTest extends TestCase
{
    public function testNonDefinedFilter(): void
    {
        $filter = new Pattern();

        $this->assertEmpty(
            $filter->validate('some_filter', [], [])
        );
    }

    /**
     * @group legacy
     */
    public function testFilterWithEmptyValue(): void
    {
        $filter = new Pattern();

        $explicitFilterDefinition = [
            'swagger' => [
                'pattern' => '/foo/',
            ],
        ];

        $this->assertEmpty(
            $filter->validate('some_filter', $explicitFilterDefinition, ['some_filter' => ''])
        );

        $weirdParameter = new \stdClass();
        $weirdParameter->foo = 'non string value should not exists';
        $this->assertEmpty(
            $filter->validate('some_filter', $explicitFilterDefinition, ['some_filter' => $weirdParameter])
        );
    }

    public function testFilterWithEmptyValueOpenApi(): void
    {
        $filter = new Pattern();

        $explicitFilterDefinition = [
            'openapi' => [
                'pattern' => '/foo/',
            ],
        ];

        $this->assertEmpty(
            $filter->validate('some_filter', $explicitFilterDefinition, ['some_filter' => ''])
        );

        $weirdParameter = new \stdClass();
        $weirdParameter->foo = 'non string value should not exists';
        $this->assertEmpty(
            $filter->validate('some_filter', $explicitFilterDefinition, ['some_filter' => $weirdParameter])
        );
    }

    /**
     * @group legacy
     */
    public function testFilterWithZeroAsParameter(): void
    {
        $filter = new Pattern();

        $explicitFilterDefinition = [
            'swagger' => [
                'pattern' => '/foo/',
            ],
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" must match pattern /foo/'],
            $filter->validate('some_filter', $explicitFilterDefinition, ['some_filter' => '0'])
        );
    }

    public function testFilterWithZeroAsParameterOpenApi(): void
    {
        $filter = new Pattern();

        $explicitFilterDefinition = [
            'openapi' => [
                'pattern' => '/foo/',
            ],
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" must match pattern /foo/'],
            $filter->validate('some_filter', $explicitFilterDefinition, ['some_filter' => '0'])
        );
    }

    /**
     * @group legacy
     */
    public function testFilterWithNonMatchingValue(): void
    {
        $filter = new Pattern();

        $explicitFilterDefinition = [
            'swagger' => [
                'pattern' => '/foo/',
            ],
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" must match pattern /foo/'],
            $filter->validate('some_filter', $explicitFilterDefinition, ['some_filter' => 'bar'])
        );
    }

    public function testFilterWithNonMatchingValueOpenApi(): void
    {
        $filter = new Pattern();

        $explicitFilterDefinition = [
            'openapi' => [
                'pattern' => '/foo/',
            ],
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" must match pattern /foo/'],
            $filter->validate('some_filter', $explicitFilterDefinition, ['some_filter' => 'bar'])
        );
    }

    /**
     * @group legacy
     */
    public function testFilterWithNonchingValue(): void
    {
        $filter = new Pattern();

        $explicitFilterDefinition = [
            'swagger' => [
                'pattern' => '/foo \d+/',
            ],
        ];

        $this->assertEmpty(
            $filter->validate('some_filter', $explicitFilterDefinition, ['some_filter' => 'this is a foo '.random_int(0, 10).' and it should match'])
        );
    }

    public function testFilterWithNonchingValueOpenApi(): void
    {
        $filter = new Pattern();

        $explicitFilterDefinition = [
            'openapi' => [
                'pattern' => '/foo \d+/',
            ],
        ];

        $this->assertEmpty(
            $filter->validate('some_filter', $explicitFilterDefinition, ['some_filter' => 'this is a foo '.random_int(0, 10).' and it should match'])
        );
    }
}
