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

use ApiPlatform\Doctrine\Orm\Filter\ExactFilter;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\Dummy;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class ExactFilterTest extends TestCase
{
    private const ALIAS = 'o';
    private const ASSOCIATIVE_DATE_FILTER_VALUE = ['strictly_before' => '2026-07-20'];
    private const DATE_PROPERTY = 'dummyDate';

    public function testExactFilterIgnoresAssociativeArrayValues(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getRootAliases')->willReturn([self::ALIAS]);
        $queryBuilder->expects($this->never())->method('andWhere');
        $queryBuilder->expects($this->never())->method('setParameter');

        $parameter = (new QueryParameter(key: self::DATE_PROPERTY, property: self::DATE_PROPERTY))
            ->setValue(self::ASSOCIATIVE_DATE_FILTER_VALUE);

        (new ExactFilter())->apply($queryBuilder, new QueryNameGenerator(), Dummy::class, context: ['parameter' => $parameter]);
    }
}
