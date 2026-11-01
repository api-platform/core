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

use ApiPlatform\Laravel\Eloquent\Filter\EndSearchFilter;
use ApiPlatform\Laravel\Eloquent\Filter\FilterInterface;
use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;
use ApiPlatform\Laravel\Eloquent\Filter\StartSearchFilter;
use ApiPlatform\Metadata\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @author Ali Ben Macha <contact@benmacha.tn>
 */
final class SearchFilterTest extends TestCase
{
    public static function provideNonStringValues(): iterable
    {
        foreach ([PartialSearchFilter::class, StartSearchFilter::class, EndSearchFilter::class] as $filterClass) {
            yield $filterClass.' with a list' => [new $filterClass(), ['a', 'b']];
            yield $filterClass.' with a map' => [new $filterClass(), ['x' => 'a']];
        }
    }

    #[DataProvider('provideNonStringValues')]
    public function testNonStringValuesAreIgnored(FilterInterface $filter, array $values): void
    {
        $builder = $this->createMock(Builder::class);
        $builder->expects($this->never())->method($this->anything());

        $this->assertSame($builder, $filter->apply($builder, $values, new QueryParameter(key: 'name', property: 'name')));
    }
}
