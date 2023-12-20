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

use ApiPlatform\ParameterValidator\Validator\Required;
use PHPUnit\Framework\TestCase;

/**
 * Class RequiredTest.
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class RequiredTest extends TestCase
{
    public function testNonRequiredFilter(): void
    {
        $request = [];
        $filter = new Required();

        $this->assertEmpty(
            $filter->validate('some_filter', [], [])
        );

        $this->assertEmpty(
            $filter->validate('some_filter', ['required' => false], $request)
        );
    }

    public function testRequiredFilterNotInQuery(): void
    {
        $request = [];
        $filter = new Required();

        $this->assertEquals(
            ['Query parameter "some_filter" is required'],
            $filter->validate('some_filter', ['required' => true], $request)
        );
    }

    public function testRequiredFilterIsPresent(): void
    {
        $request = ['some_filter' => 'some_value'];
        $filter = new Required();

        $this->assertEmpty(
            $filter->validate('some_filter', ['required' => true], $request)
        );
    }

    /**
     * @group legacy
     */
    public function testEmptyValueNotAllowed(): void
    {
        $request = ['some_filter' => ''];
        $filter = new Required();

        $explicitFilterDefinition = [
            'required' => true,
            'swagger' => [
                'allowEmptyValue' => false,
            ],
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" does not allow empty value'],
            $filter->validate('some_filter', $explicitFilterDefinition, $request)
        );

        $implicitFilterDefinition = [
            'required' => true,
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" does not allow empty value'],
            $filter->validate('some_filter', $implicitFilterDefinition, $request)
        );
    }

    public function testEmptyValueNotAllowedOpenApi(): void
    {
        $request = ['some_filter' => ''];
        $filter = new Required();

        $explicitFilterDefinition = [
            'required' => true,
            'openapi' => [
                'allowEmptyValue' => false,
            ],
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" does not allow empty value'],
            $filter->validate('some_filter', $explicitFilterDefinition, $request)
        );

        $implicitFilterDefinition = [
            'required' => true,
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" does not allow empty value'],
            $filter->validate('some_filter', $implicitFilterDefinition, $request)
        );
    }

    /**
     * @group legacy
     */
    public function testEmptyValueAllowed(): void
    {
        $request = ['some_filter' => ''];
        $filter = new Required();

        $explicitFilterDefinition = [
            'required' => true,
            'swagger' => [
                'allowEmptyValue' => true,
            ],
        ];

        $this->assertEmpty(
            $filter->validate('some_filter', $explicitFilterDefinition, $request)
        );
    }

    public function testEmptyValueAllowedOpenApi(): void
    {
        $request = ['some_filter' => ''];
        $filter = new Required();

        $explicitFilterDefinition = [
            'required' => true,
            'openapi' => [
                'allowEmptyValue' => true,
            ],
        ];

        $this->assertEmpty(
            $filter->validate('some_filter', $explicitFilterDefinition, $request)
        );
    }

    public function testBracketNotation(): void
    {
        $filter = new Required();

        $request = ['foo' => ['bar' => ['bar']]];

        $requiredFilter = [
            'required' => true,
        ];

        $this->assertEmpty(
            $filter->validate('foo[bar]', $requiredFilter, $request)
        );
    }

    public function testDotNotation(): void
    {
        $request = ['foo.bar' => 'bar'];
        $filter = new Required();

        $requiredFilter = [
            'required' => true,
        ];

        $this->assertEmpty(
            $filter->validate('foo.bar', $requiredFilter, $request)
        );
    }
}
