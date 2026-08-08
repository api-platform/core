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

use ApiPlatform\Meilisearch\Filter\SearchFilter;
use ApiPlatform\Meilisearch\Tests\Fixtures\Movie;
use PHPUnit\Framework\TestCase;

class SearchFilterTest extends TestCase
{
    public function testSetsQFromConfiguredProperty(): void
    {
        $filter = new SearchFilter(['title']);

        $parameters = $filter->apply([], Movie::class, null, ['filters' => ['title' => 'star wars']]);

        self::assertSame('star wars', $parameters['q']);
    }

    public function testIgnoresUnconfiguredProperty(): void
    {
        $filter = new SearchFilter(['title']);

        $parameters = $filter->apply([], Movie::class, null, ['filters' => ['genre' => 'sci-fi']]);

        self::assertArrayNotHasKey('q', $parameters);
    }

    public function testDoesNotOverrideAnAlreadySetQ(): void
    {
        $filter = new SearchFilter(['title']);

        $parameters = $filter->apply(['q' => 'already set'], Movie::class, null, ['filters' => ['title' => 'star wars']]);

        self::assertSame('already set', $parameters['q']);
    }

    public function testIgnoresEmptyValue(): void
    {
        $filter = new SearchFilter(['title']);

        $parameters = $filter->apply([], Movie::class, null, ['filters' => ['title' => '']]);

        self::assertArrayNotHasKey('q', $parameters);
    }

    public function testNoRestrictionAcceptsAnyProperty(): void
    {
        $filter = new SearchFilter();

        $parameters = $filter->apply([], Movie::class, null, ['filters' => ['title' => 'star wars']]);

        self::assertSame('star wars', $parameters['q']);
    }
}
