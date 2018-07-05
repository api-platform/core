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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Orm\Util;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class QueryBuilderHelperTest extends TestCase
{
    /**
     * @dataProvider provideAddJoinOnce
     */
    public function testAddJoinOnce(string $originAliasForJoinOnce = null, string $expectedAlias)
    {
        $queryBuilder = new QueryBuilder($this->prophesize(EntityManagerInterface::class)->reveal());
        $queryBuilder->from('foo', 'f');
        $queryBuilder->from('foo', 'f2');
        $queryBuilder->join('f.bar', 'b');
        $queryBuilder->join('f2.bar', 'b2');

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);

        QueryBuilderHelper::addJoinOnce(
            $queryBuilder,
            $queryNameGenerator->reveal(),
            $originAliasForJoinOnce ?? 'f',
            'bar',
            null,
            null,
            null,
            $originAliasForJoinOnce
        );

        $this->assertSame($expectedAlias,
            $queryBuilder->getDQLPart('join')[$originAliasForJoinOnce ?? 'f'][0]->getAlias());
    }

    public function provideAddJoinOnce(): array
    {
        return [
            [
                null,
                'b',
            ],
            [
                'f2',
                'b2',
            ],
        ];
    }
}
