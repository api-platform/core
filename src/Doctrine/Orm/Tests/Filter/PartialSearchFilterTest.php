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

use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @author Ali Ben Macha <contact@benmacha.tn>
 */
final class PartialSearchFilterTest extends TestCase
{
    public function testNestedArrayValuesAreIgnored(): void
    {
        $queryBuilder = $this->apply([['foo' => 'bar']]);

        $this->assertSame('SELECT o FROM Dummy o', $queryBuilder->getDQL());
        $this->assertCount(0, $queryBuilder->getParameters());
    }

    public function testNestedArrayValuesAreSkippedAmongScalarValues(): void
    {
        $queryBuilder = $this->apply([['foo' => 'bar'], 'baz']);

        $this->assertSame(1, substr_count($queryBuilder->getDQL(), ' LIKE '));
        $this->assertSame(['%baz%'], array_map(static fn (Parameter $p) => $p->getValue(), $queryBuilder->getParameters()->toArray()));
    }

    private function apply(array $values): QueryBuilder
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getExpressionBuilder')->willReturn(new Expr());
        $queryBuilder = new QueryBuilder($entityManager);
        $queryBuilder->select('o')->from('Dummy', 'o');

        $parameter = (new QueryParameter(key: 'name', property: 'name'))->setValue($values);
        (new PartialSearchFilter())->apply($queryBuilder, new QueryNameGenerator(), 'Dummy', null, ['parameter' => $parameter]);

        return $queryBuilder;
    }
}
