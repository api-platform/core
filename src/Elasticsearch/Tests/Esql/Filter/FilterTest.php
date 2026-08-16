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

namespace ApiPlatform\Elasticsearch\Tests\Esql\Filter;

use ApiPlatform\Elasticsearch\Esql\EsqlQuery;
use ApiPlatform\Elasticsearch\Esql\Filter\ComparisonFilter;
use ApiPlatform\Elasticsearch\Esql\Filter\ExactFilter;
use ApiPlatform\Elasticsearch\Esql\Filter\MatchFilter;
use ApiPlatform\Elasticsearch\Esql\Filter\SortFilter;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\QueryParameter;
use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase
{
    public function testExactFilterWithSingleValue(): void
    {
        $query = new EsqlQuery('book', []);
        $parameter = (new QueryParameter(key: 'genre', property: 'genre'))->setValue('fantasy');

        (new ExactFilter())->apply($query, 'Book', null, ['parameter' => $parameter, 'es_field' => 'genre']);

        self::assertSame([
            'query' => 'FROM book | WHERE ??f1 == ?p2',
            'params' => [['f1' => 'genre'], ['p2' => 'fantasy']],
        ], $query->compile());
    }

    public function testExactFilterWithMultipleValues(): void
    {
        $query = new EsqlQuery('book', []);
        $parameter = (new QueryParameter(key: 'genre', property: 'genre'))->setValue(['a', 'b']);

        (new ExactFilter())->apply($query, 'Book', null, ['parameter' => $parameter, 'es_field' => 'genre']);

        self::assertSame([
            'query' => 'FROM book | WHERE ??f1 IN (?p2, ?p3)',
            'params' => [['f1' => 'genre'], ['p2' => 'a'], ['p3' => 'b']],
        ], $query->compile());
    }

    public function testExactFilterIgnoresEmptyValues(): void
    {
        $query = new EsqlQuery('book', []);
        $parameter = (new QueryParameter(key: 'genre', property: 'genre'))->setValue(null);

        (new ExactFilter())->apply($query, 'Book', null, ['parameter' => $parameter, 'es_field' => 'genre']);

        self::assertSame('FROM book', $query->compile()['query']);
    }

    public function testMatchFilter(): void
    {
        $query = new EsqlQuery('book', []);
        $parameter = (new QueryParameter(key: 'title', property: 'title'))->setValue('hobbit');

        (new MatchFilter())->apply($query, 'Book', null, ['parameter' => $parameter, 'es_field' => 'title']);

        self::assertSame([
            'query' => 'FROM book | WHERE MATCH(??f1, ?p2)',
            'params' => [['f1' => 'title'], ['p2' => 'hobbit']],
        ], $query->compile());
    }

    public function testMatchFilterWithMultipleValuesIsCombinedWithOr(): void
    {
        $query = new EsqlQuery('book', []);
        $parameter = (new QueryParameter(key: 'title', property: 'title'))->setValue(['a', 'b']);

        (new MatchFilter())->apply($query, 'Book', null, ['parameter' => $parameter, 'es_field' => 'title']);

        self::assertSame('FROM book | WHERE MATCH(??f1, ?p2) OR MATCH(??f1, ?p3)', $query->compile()['query']);
    }

    public function testMatchFilterIsCompiledBeforeOtherConditions(): void
    {
        $query = new EsqlQuery('book', []);
        $query->andWhere('rating > 4');
        $parameter = (new QueryParameter(key: 'title', property: 'title'))->setValue('hobbit');

        (new MatchFilter())->apply($query, 'Book', null, ['parameter' => $parameter, 'es_field' => 'title']);

        self::assertSame('FROM book | WHERE (MATCH(??f1, ?p2)) AND (rating > 4)', $query->compile()['query']);
    }

    public function testComparisonFilter(): void
    {
        $query = new EsqlQuery('book', []);
        $parameter = (new QueryParameter(key: 'rating', property: 'rating'))->setValue(['gt' => '4', 'lte' => '5']);

        (new ComparisonFilter())->apply($query, 'Book', null, ['parameter' => $parameter, 'es_field' => 'rating']);

        self::assertSame([
            'query' => 'FROM book | WHERE (??f1 > ?p2) AND (??f3 <= ?p4)',
            'params' => [['f1' => 'rating'], ['p2' => '4'], ['f3' => 'rating'], ['p4' => '5']],
        ], $query->compile());
    }

    public function testComparisonFilterBetween(): void
    {
        $query = new EsqlQuery('book', []);
        $parameter = (new QueryParameter(key: 'rating', property: 'rating'))->setValue(['between' => '2..4']);

        (new ComparisonFilter())->apply($query, 'Book', null, ['parameter' => $parameter, 'es_field' => 'rating']);

        self::assertSame('FROM book | WHERE ??f1 >= ?p2 AND ??f1 <= ?p3', $query->compile()['query']);
    }

    public function testComparisonFilterInvalidBetween(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $query = new EsqlQuery('book', []);
        $parameter = (new QueryParameter(key: 'rating', property: 'rating'))->setValue(['between' => '2']);

        (new ComparisonFilter())->apply($query, 'Book', null, ['parameter' => $parameter, 'es_field' => 'rating']);
    }

    public function testComparisonFilterIgnoresUnknownOperators(): void
    {
        $query = new EsqlQuery('book', []);
        $parameter = (new QueryParameter(key: 'rating', property: 'rating'))->setValue(['like' => 'x']);

        (new ComparisonFilter())->apply($query, 'Book', null, ['parameter' => $parameter, 'es_field' => 'rating']);

        self::assertSame('FROM book', $query->compile()['query']);
    }

    public function testSortFilter(): void
    {
        $query = new EsqlQuery('book', []);
        $parameter = (new QueryParameter(key: 'sort[:property]', property: 'rating'))->setValue('desc');

        (new SortFilter())->apply($query, 'Book', null, ['parameter' => $parameter, 'es_field' => 'rating']);

        self::assertSame('FROM book | SORT rating DESC', $query->compile()['query']);
    }

    public function testSortFilterIgnoresInvalidDirection(): void
    {
        $query = new EsqlQuery('book', []);
        $parameter = (new QueryParameter(key: 'sort[:property]', property: 'rating'))->setValue('sideways');

        (new SortFilter())->apply($query, 'Book', null, ['parameter' => $parameter, 'es_field' => 'rating']);

        self::assertSame('FROM book', $query->compile()['query']);
    }
}
