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

namespace ApiPlatform\Elasticsearch\Tests\Esql\Extension;

use ApiPlatform\Elasticsearch\Esql\EsqlQuery;
use ApiPlatform\Elasticsearch\Esql\Extension\PaginationExtension;
use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\State\Pagination\Pagination;
use Elastic\Elasticsearch\ClientBuilder;
use PHPUnit\Framework\TestCase;

class PaginationExtensionTest extends TestCase
{
    public function testApplyToCollectionFetchesOneExtraRow(): void
    {
        $query = new EsqlQuery('foo');
        $extension = new PaginationExtension(ClientBuilder::create()->build(), null, new Pagination(['items_per_page' => 20]));

        $extension->applyToCollection($query, Foo::class, new GetCollection());

        self::assertSame(21, $query->getLimit());
    }

    public function testApplyToCollectionWithPaginationDisabled(): void
    {
        $query = new EsqlQuery('foo');
        $extension = new PaginationExtension(ClientBuilder::create()->build(), null, new Pagination(['enabled' => false]));

        $extension->applyToCollection($query, Foo::class, new GetCollection());

        self::assertNull($query->getLimit());
    }

    public function testSupportsResult(): void
    {
        $extension = new PaginationExtension(ClientBuilder::create()->build(), null, new Pagination());

        self::assertTrue($extension->supportsResult(Foo::class, new GetCollection()));
    }

    public function testSupportsResultWithPaginationDisabled(): void
    {
        $extension = new PaginationExtension(ClientBuilder::create()->build(), null, new Pagination(['enabled' => false]));

        self::assertFalse($extension->supportsResult(Foo::class, new GetCollection()));
    }
}
