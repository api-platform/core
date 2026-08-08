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

use ApiPlatform\Meilisearch\Extension\SortExtension;
use ApiPlatform\Meilisearch\Tests\Fixtures\Movie;
use ApiPlatform\Metadata\GetCollection;
use PHPUnit\Framework\TestCase;

class SortExtensionTest extends TestCase
{
    public function testAppliesOperationOrder(): void
    {
        $operation = (new GetCollection())->withClass(Movie::class)->withOrder(['year' => 'desc']);
        $extension = new SortExtension();

        $parameters = $extension->applyToCollection([], Movie::class, $operation);

        self::assertSame(['year:desc'], $parameters['sort']);
    }

    public function testAppliesOperationOrderWithImplicitAscending(): void
    {
        $operation = (new GetCollection())->withClass(Movie::class)->withOrder(['year']);
        $extension = new SortExtension();

        $parameters = $extension->applyToCollection([], Movie::class, $operation);

        self::assertSame(['year:asc'], $parameters['sort']);
    }

    public function testMergesWithExistingSort(): void
    {
        $operation = (new GetCollection())->withClass(Movie::class)->withOrder(['year' => 'desc']);
        $extension = new SortExtension();

        $parameters = $extension->applyToCollection(['sort' => ['title:asc']], Movie::class, $operation);

        self::assertSame(['title:asc', 'year:desc'], $parameters['sort']);
    }

    public function testNoOrderLeavesParametersUntouched(): void
    {
        $operation = (new GetCollection())->withClass(Movie::class);
        $extension = new SortExtension();

        $parameters = $extension->applyToCollection(['q' => 'wars'], Movie::class, $operation);

        self::assertArrayNotHasKey('sort', $parameters);
    }

    public function testDefaultDirectionAppliesToIdentifier(): void
    {
        $operation = (new GetCollection())->withClass(Movie::class);
        $extension = new SortExtension('asc');

        $parameters = $extension->applyToCollection([], Movie::class, $operation);

        self::assertSame(['id:asc'], $parameters['sort']);
    }
}
