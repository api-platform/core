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
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RequiredTest.
 *
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class RequiredTest extends TestCase
{
    public function testNonRequiredFilter()
    {
        $request = new Request();
        $filter = new Required();

        $this->assertEmpty(
            $filter->validate('some_filter', [], $request)
        );

        $this->assertEmpty(
            $filter->validate('some_filter', ['required' => false], $request)
        );
    }

    public function testRequiredFilterNotInQuery()
    {
        $request = new Request();
        $filter = new Required();

        $this->assertEquals(
            ['Query parameter "some_filter" is required'],
            $filter->validate('some_filter', ['required' => true], $request)
        );
    }

    public function testRequiredFilterIsPresent()
    {
        $request = new Request(['some_filter' => 'some_value']);
        $filter = new Required();

        $this->assertEmpty(
            $filter->validate('some_filter', ['required' => true], $request)
        );
    }

    public function testEmptyValueNotAllowed()
    {
        $request = new Request(['some_filter' => '']);
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
        $request = new Request(['some_filter' => '']);
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
