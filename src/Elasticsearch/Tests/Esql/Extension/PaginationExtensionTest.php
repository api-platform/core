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
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\State\Pagination\Pagination;
use Elastic\Elasticsearch\ClientBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class PaginationExtensionTest extends TestCase
{
    public function testApplyToCollectionFetchesOneExtraRow(): void
    {
        $query = new EsqlQuery('foo');
        $extension = $this->createExtension(new Pagination(['items_per_page' => 20]));

        $extension->applyToCollection($query, Foo::class, new GetCollection());

        self::assertSame(21, $query->getLimit());
    }

    public function testApplyToCollectionWithPaginationDisabled(): void
    {
        $query = new EsqlQuery('foo');
        $extension = $this->createExtension(new Pagination(['enabled' => false]));

        $extension->applyToCollection($query, Foo::class, new GetCollection());

        self::assertNull($query->getLimit());
    }

    public function testApplyToCollectionWithPageGreaterThanOne(): void
    {
        $query = new EsqlQuery('foo');
        $extension = $this->createExtension(new Pagination(['items_per_page' => 20]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ES|QL supports partial pagination only: the "page" parameter is not supported.');

        $extension->applyToCollection($query, Foo::class, new GetCollection(), ['filters' => ['page' => 3]]);
    }

    public function testGetResultWithPageGreaterThanOne(): void
    {
        $extension = $this->createExtension(new Pagination(['items_per_page' => 20]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ES|QL supports partial pagination only: the "page" parameter is not supported.');

        $extension->getResult(new EsqlQuery('foo'), Foo::class, new GetCollection(), ['filters' => ['page' => 2]]);
    }

    public function testGetResultWithoutPagination(): void
    {
        $extension = $this->createExtension();

        $this->expectException(\LogicException::class);

        $extension->getResult(new EsqlQuery('foo'), Foo::class, new GetCollection());
    }

    public function testSupportsResult(): void
    {
        $extension = $this->createExtension(new Pagination());

        self::assertTrue($extension->supportsResult(Foo::class, new GetCollection()));
    }

    public function testSupportsResultWithPaginationDisabled(): void
    {
        $extension = $this->createExtension(new Pagination(['enabled' => false]));

        self::assertFalse($extension->supportsResult(Foo::class, new GetCollection()));
    }

    private function createExtension(?Pagination $pagination = null): PaginationExtension
    {
        return new PaginationExtension(
            ClientBuilder::create()->build(),
            $this->createStub(DenormalizerInterface::class),
            $pagination,
        );
    }
}
