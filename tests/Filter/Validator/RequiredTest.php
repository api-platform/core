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

use ApiPlatform\Core\Filter\Validator\Required;
use PHPUnit\Framework\TestCase;

/**
 * Class RequiredTest.
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class RequiredTest extends TestCase
{
    public function testNonRequiredFilter()
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

    public function testRequiredFilterNotInQuery()
    {
        $request = [];
        $filter = new Required();

        $this->assertEquals(
            ['Query parameter "some_filter" is required'],
            $filter->validate('some_filter', ['required' => true], $request)
        );
    }

    public function testRequiredFilterIsPresent()
    {
        $request = ['some_filter' => 'some_value'];
        $filter = new Required();

        $this->assertEmpty(
            $filter->validate('some_filter', ['required' => true], $request)
        );
    }

    public function testEmptyValueNotAllowed()
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

    public function testEmptyValueAllowed()
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
}
