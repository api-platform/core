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

use ApiPlatform\Core\Filter\Validator\MultipleOf;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class MultipleOfTest extends TestCase
{
    public function testNonDefinedFilter()
    {
        $request = new Request();
        $filter = new MultipleOf();

        $this->assertEmpty(
            $filter->validate('some_filter', [], $request)
        );
    }

    public function testEmptyQueryParameter()
    {
        $request = new Request(['some_filter' => '']);
        $filter = new MultipleOf();

        $this->assertEmpty(
            $filter->validate('some_filter', [], $request)
        );
    }

    public function testNonMatchingParameter()
    {
        $request = new Request(['some_filter' => '8']);
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

    public function testMatchingParameter()
    {
        $request = new Request(['some_filter' => '8']);
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
}
