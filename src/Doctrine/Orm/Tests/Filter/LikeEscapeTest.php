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
use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\StartSearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\WordStartSearchFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LikeEscapeTest extends TestCase
{
    #[DataProvider('filterProvider')]
    public function testEscapeCharIsPortable(FilterInterface $filter, int $likeCount, array $expectedValues): void
    {
        $queryBuilder = $this->apply($filter, ['50%_off!\\']);

        $dql = $queryBuilder->getDQL();
        $this->assertSame($likeCount, substr_count($dql, "ESCAPE '!'"), $dql);
        $this->assertStringNotContainsString('\\', $dql);
        $this->assertSame($expectedValues, array_map(static fn (Parameter $p) => $p->getValue(), $queryBuilder->getParameters()->toArray()));
    }

    public static function filterProvider(): \Generator
    {
        yield 'partial' => [new PartialSearchFilter(), 1, ['%50!%!_off!!\\%']];
        yield 'partial sensitive' => [new PartialSearchFilter(true), 1, ['%50!%!_off!!\\%']];
        yield 'start' => [new StartSearchFilter(), 1, ['50!%!_off!!\\%']];
        yield 'end' => [new EndSearchFilter(), 1, ['%50!%!_off!!\\']];
        yield 'word start' => [new WordStartSearchFilter(), 2, ['50!%!_off!!\\%', '% 50!%!_off!!\\%']];
    }

    public function testMultipleValuesBindEveryPlaceholder(): void
    {
        $queryBuilder = $this->apply(new PartialSearchFilter(), ['foo', 'bar']);

        $this->assertSame(2, substr_count($queryBuilder->getDQL(), "ESCAPE '!'"));
        $this->assertCount(2, $queryBuilder->getParameters());
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
