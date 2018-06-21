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

use ApiPlatform\Core\Filter\Validator\Length;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class LengthTest extends TestCase
{
    public function testNonDefinedFilter()
    {
        $request = new Request();
        $filter = new Length();

        $this->assertEmpty(
            $filter->validate('some_filter', [], $request)
        );
    }

    public function testEmptyQueryParameter()
    {
        $request = new Request(['some_filter' => '']);
        $filter = new Length();

        $this->assertEmpty(
            $filter->validate('some_filter', [], $request)
        );
    }

    public function testNonMatchingParameter()
    {
        $filter = new Length();

        $filterDefinition = [
            'swagger' => [
                'minLength' => 3,
                'maxLength' => 5,
            ],
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" length must be greater than or equal to 3'],
            $filter->validate('some_filter', $filterDefinition, new Request(['some_filter' => 'ab']))
        );

        $this->assertEquals(
            ['Query parameter "some_filter" length must be lower than or equal to 5'],
            $filter->validate('some_filter', $filterDefinition, new Request(['some_filter' => 'abcdef']))
        );
    }

    public function testNonMatchingParameterWithOnlyOneDefinition()
    {
        $filter = new Length();

        $filterDefinition = [
            'swagger' => [
                'minLength' => 3,
            ],
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" length must be greater than or equal to 3'],
            $filter->validate('some_filter', $filterDefinition, new Request(['some_filter' => 'ab']))
        );

        $filterDefinition = [
            'swagger' => [
                'maxLength' => 5,
            ],
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" length must be lower than or equal to 5'],
            $filter->validate('some_filter', $filterDefinition, new Request(['some_filter' => 'abcdef']))
        );
    }

    public function testMatchingParameter()
    {
        $filter = new Length();

        $filterDefinition = [
            'swagger' => [
                'minLength' => 3,
                'maxLength' => 5,
            ],
        ];

        $this->assertEmpty(
            $filter->validate('some_filter', $filterDefinition, new Request(['some_filter' => 'abc']))
        );

        $this->assertEmpty(
            $filter->validate('some_filter', $filterDefinition, new Request(['some_filter' => 'abcd']))
        );

        $this->assertEmpty(
            $filter->validate('some_filter', $filterDefinition, new Request(['some_filter' => 'abcde']))
        );
    }

    public function testMatchingParameterWithOneDefinition()
    {
        $filter = new Length();

        $filterDefinition = [
            'swagger' => [
                'minLength' => 3,
            ],
        ];

        $this->assertEmpty(
            $filter->validate('some_filter', $filterDefinition, new Request(['some_filter' => 'abc']))
        );

        $filterDefinition = [
            'swagger' => [
                'maxLength' => 5,
            ],
        ];

        $this->assertEmpty(
            $filter->validate('some_filter', $filterDefinition, new Request(['some_filter' => 'abcde']))
        );
    }
}
