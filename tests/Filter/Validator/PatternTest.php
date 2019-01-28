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

use ApiPlatform\Core\Filter\Validator\Pattern;
use PHPUnit\Framework\TestCase;

/**
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class PatternTest extends TestCase
{
    public function testNonDefinedFilter()
    {
        $filter = new Pattern();

        $this->assertEmpty(
            $filter->validate('some_filter', [], [])
        );
    }

    public function testFilterWithEmptyValue()
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

    public function testFilterWithZeroAsParameter()
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

    public function testFilterWithNonMatchingValue()
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

    public function testFilterWithNonchingValue()
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
}
