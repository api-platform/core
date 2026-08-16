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

namespace ApiPlatform\Elasticsearch\Tests\Esql;

use ApiPlatform\Elasticsearch\Esql\EsqlQuery;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EsqlQueryTest extends TestCase
{
    public function testCompileMinimal(): void
    {
        $query = new EsqlQuery('book');

        self::assertSame(['query' => 'FROM book METADATA _id', 'params' => []], $query->compile());
    }

    public function testCompileWithoutMetadata(): void
    {
        $query = new EsqlQuery('book', []);

        self::assertSame(['query' => 'FROM book', 'params' => []], $query->compile());
    }

    public function testCompileFullQuery(): void
    {
        $query = new EsqlQuery('book');

        $field = $query->identifier('genre');
        $value = $query->param('fantasy');
        $query->andWhere("{$field} == {$value}");

        $rating = $query->param(4.2);
        $query->andWhere("rating >= {$rating}");

        $title = $query->param('ring');
        $query->fullTextWhere("MATCH(title, {$title})");

        $query->sort('rating', 'desc');
        $query->sort('_id');
        $query->limit(11);

        self::assertSame([
            'query' => 'FROM book METADATA _id | WHERE (MATCH(title, ?p4)) AND ((??f1 == ?p2) AND (rating >= ?p3)) | SORT rating DESC, _id ASC | LIMIT 11',
            'params' => [
                ['f1' => 'genre'],
                ['p2' => 'fantasy'],
                ['p3' => 4.2],
                ['p4' => 'ring'],
            ],
        ], $query->compile());
    }

    public function testOrWhere(): void
    {
        $query = new EsqlQuery('book', []);
        $query->andWhere('a == 1')->orWhere('b == 2')->andWhere('c == 3');

        self::assertSame('FROM book | WHERE ((a == 1) OR (b == 2)) AND (c == 3)', $query->compile()['query']);
    }

    public function testFullTextWhereOrOperator(): void
    {
        $query = new EsqlQuery('book', []);
        $query->fullTextWhere('MATCH(a, ?p1)')->fullTextWhere('MATCH(b, ?p2)', 'or');

        self::assertSame('FROM book | WHERE (MATCH(a, ?p1)) OR (MATCH(b, ?p2))', $query->compile()['query']);
    }

    public function testHasSortAndGetLimit(): void
    {
        $query = new EsqlQuery('book');

        self::assertFalse($query->hasSort());
        self::assertNull($query->getLimit());

        $query->sort('id');
        $query->limit(10);

        self::assertTrue($query->hasSort());
        self::assertSame(10, $query->getLimit());
    }

    public function testParamsAreOrderedAndUnique(): void
    {
        $query = new EsqlQuery('book');

        self::assertSame('?p1', $query->param('a'));
        self::assertSame('??f2', $query->identifier('b'));
        self::assertSame('?p3', $query->param('a'));
    }

    public function testInvalidIndexIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new EsqlQuery('book | DROP something'))->compile();
    }

    public function testInvalidSortFieldIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new EsqlQuery('book'))->sort('rating | LIMIT 1');
    }

    public function testInvalidSortDirectionIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new EsqlQuery('book'))->sort('rating', 'sideways');
    }

    public function testNegativeLimitIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new EsqlQuery('book'))->limit(-1);
    }
}
