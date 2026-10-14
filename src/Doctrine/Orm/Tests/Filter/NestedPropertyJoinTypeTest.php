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
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\Dummy;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NestedPropertyJoinTypeTest extends TestCase
{
    #[DataProvider('provideWhereClauses')]
    public function testNestedJoinTypeDependsOnWhereClause(string $whereClause, string $expectedJoin, string $unexpectedJoin): void
    {
        $classMetadata = $this->createStub(ClassMetadata::class);
        $classMetadata->method('hasAssociation')->willReturn(true);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getClassMetadata')->willReturn($classMetadata);
        $queryBuilder = (new QueryBuilder($entityManager))->select('o')->from(Dummy::class, 'o');
        $parameter = (new QueryParameter(key: 'relatedDummy', property: 'relatedDummy.name'))
            ->withExtraProperties([
                '_api_values' => 'x',
                'nested_properties_info' => ['relatedDummy.name' => [
                    'relation_classes' => [Dummy::class],
                    'converted_relation_segments' => ['relatedDummy'],
                    'leaf_property' => 'name',
                ]],
            ]);

        (new PartialSearchFilter())->apply($queryBuilder, new QueryNameGenerator(), Dummy::class, null, ['parameter' => $parameter, 'whereClause' => $whereClause]);

        $dql = $queryBuilder->getDQL();
        $this->assertStringContainsString($expectedJoin, $dql);
        $this->assertStringNotContainsString($unexpectedJoin, $dql);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function provideWhereClauses(): iterable
    {
        yield 'or clause keeps rows with a null relation' => ['orWhere', 'LEFT JOIN', 'INNER JOIN'];
        yield 'and clause keeps the inner join' => ['andWhere', 'INNER JOIN', 'LEFT JOIN'];
    }
}
