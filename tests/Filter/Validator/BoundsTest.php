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

use ApiPlatform\Core\Filter\Validator\Bounds;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class BoundsTest extends TestCase
{
    public function testNonDefinedFilter()
    {
        $request = new Request();
        $filter = new Bounds();

        $this->assertEmpty(
            $filter->validate('some_filter', [], $request)
        );
    }

    public function testEmptyQueryParameter()
    {
        $request = new Request(['some_filter' => '']);
        $filter = new Bounds();

        $this->assertEmpty(
            $filter->validate('some_filter', [], $request)
        );
    }

    public function testNonMatchingMinimum()
    {
        $request = new Request(['some_filter' => '9']);
        $filter = new Bounds();

        $filterDefinition = [
            'swagger' => [
                'minimum' => 10,
            ],
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" must be greater than or equal to 10'],
            $filter->validate('some_filter', $filterDefinition, $request)
        );

        $filterDefinition = [
            'swagger' => [
                'minimum' => 10,
                'exclusiveMinimum' => false,
            ],
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" must be greater than or equal to 10'],
            $filter->validate('some_filter', $filterDefinition, $request)
        );

        $filterDefinition = [
            'swagger' => [
                'minimum' => 9,
                'exclusiveMinimum' => true,
            ],
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" must be greater than 9'],
            $filter->validate('some_filter', $filterDefinition, $request)
        );
    }

    public function testMatchingMinimum()
    {
        $request = new Request(['some_filter' => '10']);
        $filter = new Bounds();

        $filterDefinition = [
            'swagger' => [
                'minimum' => 10,
            ],
        ];

        $this->assertEmpty(
            $filter->validate('some_filter', $filterDefinition, $request)
        );

        $filterDefinition = [
            'swagger' => [
                'minimum' => 9,
                'exclusiveMinimum' => false,
            ],
        ];

        $this->assertEmpty(
            $filter->validate('some_filter', $filterDefinition, $request)
        );
    }

    public function testNonMatchingMaximum()
    {
        $request = new Request(['some_filter' => '11']);
        $filter = new Bounds();

        $filterDefinition = [
            'swagger' => [
                'maximum' => 10,
            ],
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" must be less than or equal to 10'],
            $filter->validate('some_filter', $filterDefinition, $request)
        );

        $filterDefinition = [
            'swagger' => [
                'maximum' => 10,
                'exclusiveMaximum' => false,
            ],
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" must be less than or equal to 10'],
            $filter->validate('some_filter', $filterDefinition, $request)
        );

        $filterDefinition = [
            'swagger' => [
                'maximum' => 9,
                'exclusiveMaximum' => true,
            ],
        ];

        $this->assertEquals(
            ['Query parameter "some_filter" must be less than 9'],
            $filter->validate('some_filter', $filterDefinition, $request)
        );
    }

    public function testMatchingMaximum()
    {
        $request = new Request(['some_filter' => '10']);
        $filter = new Bounds();

        $filterDefinition = [
            'swagger' => [
                'maximum' => 10,
            ],
        ];

        $this->assertEmpty(
            $filter->validate('some_filter', $filterDefinition, $request)
        );

        $filterDefinition = [
            'swagger' => [
                'maximum' => 10,
                'exclusiveMaximum' => false,
            ],
        ];

        $this->assertEmpty(
            $filter->validate('some_filter', $filterDefinition, $request)
        );
    }
}
