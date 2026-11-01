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

namespace ApiPlatform\Laravel\Tests\Eloquent\Filter;

use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Metadata\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\TestCase;

class EqualsFilterTest extends TestCase
{
    public function testMultipleValuesKeepDateObjects(): void
    {
        $first = new \DateTimeImmutable('2024-01-01');
        $second = new \DateTimeImmutable('2024-02-01');

        $builder = $this->createMock(Builder::class);
        $builder->expects($this->once())
            ->method('__call')
            ->with('whereIn', ['date', [$first, $second]])
            ->willReturnSelf();

        $f = new EqualsFilter();
        $this->assertSame($builder, $f->apply($builder, [$first, ['nested'], $second], new QueryParameter(key: 'date', property: 'date')));
    }

    public function testMultipleValuesWithoutValidValueIsIgnored(): void
    {
        $builder = $this->createMock(Builder::class);
        $builder->expects($this->never())->method('__call');

        $f = new EqualsFilter();
        $this->assertSame($builder, $f->apply($builder, [['nested']], new QueryParameter(key: 'date', property: 'date')));
    }
}
