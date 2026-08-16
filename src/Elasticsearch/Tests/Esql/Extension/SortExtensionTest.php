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
use ApiPlatform\Elasticsearch\Esql\Extension\SortExtension;
use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use ApiPlatform\Metadata\GetCollection;
use PHPUnit\Framework\TestCase;

class SortExtensionTest extends TestCase
{
    public function testAppliesOperationOrderAndTiebreaker(): void
    {
        $query = new EsqlQuery('foo', []);

        (new SortExtension())->applyToCollection($query, Foo::class, (new GetCollection())->withOrder(['name' => 'desc', 'bar']));

        self::assertSame('FROM foo | SORT name DESC, bar ASC, _id ASC', $query->compile()['query']);
    }

    public function testAppliesDefaultDirectionWhenNoOrder(): void
    {
        $query = new EsqlQuery('foo', []);

        (new SortExtension(defaultDirection: 'desc'))->applyToCollection($query, Foo::class, new GetCollection());

        self::assertSame('FROM foo | SORT _id DESC', $query->compile()['query']);
    }

    public function testOnlyAddsTiebreakerWhenAlreadySorted(): void
    {
        $query = new EsqlQuery('foo', []);
        $query->sort('rating', 'DESC');

        (new SortExtension())->applyToCollection($query, Foo::class, (new GetCollection())->withOrder(['name']));

        self::assertSame('FROM foo | SORT rating DESC, _id ASC', $query->compile()['query']);
    }

    public function testNoOrderAtAllStillAddsTiebreaker(): void
    {
        $query = new EsqlQuery('foo', []);

        (new SortExtension())->applyToCollection($query, Foo::class, new GetCollection());

        self::assertSame('FROM foo | SORT _id ASC', $query->compile()['query']);
    }
}
