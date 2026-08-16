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

namespace ApiPlatform\Meilisearch\Tests\Extension;

use ApiPlatform\Meilisearch\Extension\FilterExtension;
use ApiPlatform\Meilisearch\Filter\SearchFilter;
use ApiPlatform\Meilisearch\Filter\TermFilter;
use ApiPlatform\Meilisearch\Tests\Fixtures\Movie;
use ApiPlatform\Metadata\GetCollection;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class FilterExtensionTest extends TestCase
{
    public function testAndJoinsFilterExpressionsIntoASingleFilterString(): void
    {
        $locator = $this->containerFor([
            'movie.term_filter' => new TermFilter(['genre', 'year']),
        ]);

        $operation = (new GetCollection())->withClass(Movie::class)->withFilters(['movie.term_filter']);
        $extension = new FilterExtension($locator);

        $parameters = $extension->applyToCollection([], Movie::class, $operation, [
            'filters' => ['genre' => 'sci-fi', 'year' => 1977],
        ]);

        self::assertSame('genre = "sci-fi" AND year = 1977', $parameters['filter']);
        self::assertArrayNotHasKey('filterExpressions', $parameters);
    }

    public function testCombinesMultipleFilterServices(): void
    {
        $locator = $this->containerFor([
            'movie.search_filter' => new SearchFilter(['title']),
            'movie.term_filter' => new TermFilter(['genre']),
        ]);

        $operation = (new GetCollection())->withClass(Movie::class)->withFilters(['movie.search_filter', 'movie.term_filter']);
        $extension = new FilterExtension($locator);

        $parameters = $extension->applyToCollection([], Movie::class, $operation, [
            'filters' => ['title' => 'wars', 'genre' => 'sci-fi'],
        ]);

        self::assertSame('wars', $parameters['q']);
        self::assertSame('genre = "sci-fi"', $parameters['filter']);
    }

    public function testNoFiltersLeavesParametersUntouched(): void
    {
        $operation = (new GetCollection())->withClass(Movie::class);
        $extension = new FilterExtension($this->containerFor([]));

        $parameters = $extension->applyToCollection(['q' => 'wars'], Movie::class, $operation, []);

        self::assertSame(['q' => 'wars'], $parameters);
    }

    public function testUnknownFilterServiceIsIgnored(): void
    {
        $operation = (new GetCollection())->withClass(Movie::class)->withFilters(['does.not.exist']);
        $extension = new FilterExtension($this->containerFor([]));

        $parameters = $extension->applyToCollection([], Movie::class, $operation, []);

        self::assertSame([], $parameters);
    }

    private function containerFor(array $services): ContainerInterface
    {
        return new class($services) implements ContainerInterface {
            public function __construct(private readonly array $services)
            {
            }

            public function get(string $id): mixed
            {
                return $this->services[$id];
            }

            public function has(string $id): bool
            {
                return isset($this->services[$id]);
            }
        };
    }
}
