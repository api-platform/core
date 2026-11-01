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

namespace ApiPlatform\Doctrine\Orm\Tests\Filter;

use ApiPlatform\Doctrine\Orm\Filter\EndSearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Filter\StartSearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\WordStartSearchFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LikeSearchFilterNestedArrayValuesTest extends TestCase
{
    public static function provideFilters(): iterable
    {
        yield 'start' => [new StartSearchFilter()];
        yield 'end' => [new EndSearchFilter()];
        yield 'word start' => [new WordStartSearchFilter()];
    }

    #[DataProvider('provideFilters')]
    public function testNestedArrayValuesAreIgnored(FilterInterface $filter): void
    {
        $queryBuilder = $this->apply($filter, [['foo' => 'bar']]);

        $this->assertSame('SELECT o FROM Dummy o', $queryBuilder->getDQL());
        $this->assertCount(0, $queryBuilder->getParameters());
    }

    #[DataProvider('provideFilters')]
    public function testNestedArrayValuesAreSkippedAmongScalarValues(FilterInterface $filter): void
    {
        $queryBuilder = $this->apply($filter, [['foo' => 'bar'], 'baz']);

        $this->assertStringContainsString(' LIKE ', $queryBuilder->getDQL());
        $this->assertNotCount(0, $queryBuilder->getParameters());
    }

    private function apply(FilterInterface $filter, array $values): QueryBuilder
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getExpressionBuilder')->willReturn(new Expr());
        $queryBuilder = new QueryBuilder($entityManager);
        $queryBuilder->select('o')->from('Dummy', 'o');

        $parameter = (new QueryParameter(key: 'name', property: 'name'))->setValue($values);
        $filter->apply($queryBuilder, new QueryNameGenerator(), 'Dummy', null, ['parameter' => $parameter]);

        return $queryBuilder;
    }
}
