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

namespace ApiPlatform\Meilisearch\Tests\Filter;

use ApiPlatform\Meilisearch\Filter\TermFilter;
use ApiPlatform\Meilisearch\Tests\Fixtures\Movie;
use PHPUnit\Framework\TestCase;

class TermFilterTest extends TestCase
{
    public function testSingleValue(): void
    {
        $filter = new TermFilter(['genre']);

        $parameters = $filter->apply([], Movie::class, null, ['filters' => ['genre' => 'sci-fi']]);

        self::assertSame(['genre = "sci-fi"'], $parameters['filterExpressions']);
    }

    public function testMultipleValuesOrTogether(): void
    {
        $filter = new TermFilter(['genre']);

        $parameters = $filter->apply([], Movie::class, null, ['filters' => ['genre' => ['sci-fi', 'comedy']]]);

        self::assertSame(['(genre = "sci-fi" OR genre = "comedy")'], $parameters['filterExpressions']);
    }

    public function testNumericValueIsNotQuoted(): void
    {
        $filter = new TermFilter(['year']);

        $parameters = $filter->apply([], Movie::class, null, ['filters' => ['year' => 1977]]);

        self::assertSame(['year = 1977'], $parameters['filterExpressions']);
    }

    public function testMultiplePropertiesAppendSeparateExpressions(): void
    {
        $filter = new TermFilter(['genre', 'year']);

        $parameters = $filter->apply([], Movie::class, null, ['filters' => ['genre' => 'sci-fi', 'year' => 1977]]);

        self::assertSame(['genre = "sci-fi"', 'year = 1977'], $parameters['filterExpressions']);
    }

    public function testAppendsToExistingFilterExpressions(): void
    {
        $filter = new TermFilter(['genre']);

        $parameters = $filter->apply(['filterExpressions' => ['title = "Star Wars"']], Movie::class, null, ['filters' => ['genre' => 'sci-fi']]);

        self::assertSame(['title = "Star Wars"', 'genre = "sci-fi"'], $parameters['filterExpressions']);
    }

    public function testIgnoresUnconfiguredProperty(): void
    {
        $filter = new TermFilter(['genre']);

        $parameters = $filter->apply([], Movie::class, null, ['filters' => ['title' => 'wars']]);

        self::assertArrayNotHasKey('filterExpressions', $parameters);
    }

    public function testQuoteEscapesEmbeddedQuotes(): void
    {
        $filter = new TermFilter(['title']);

        $parameters = $filter->apply([], Movie::class, null, ['filters' => ['title' => 'A "Great" Movie']]);

        self::assertSame(['title = "A \\"Great\\" Movie"'], $parameters['filterExpressions']);
    }
}
