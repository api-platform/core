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

namespace ApiPlatform\Doctrine\Orm\Tests\Extension;

use ApiPlatform\Doctrine\Orm\Extension\FilterExtension;
use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\Dummy;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\FilterInterface as ApiFilterInterface;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class FilterExtensionTest extends TestCase
{
    use ProphecyTrait;

    public function testApplyToCollectionWithValidFilters(): void
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $queryBuilder = $queryBuilderProphecy->reveal();

        $operation = new GetCollection(filters: ['dummyFilter', 'dummyBadFilter']);

        $ormFilterProphecy = $this->prophesize(FilterInterface::class);
        $ormFilterProphecy->apply($queryBuilder, new QueryNameGenerator(), Dummy::class, $operation, ['filters' => []])->shouldBeCalled();

        $ordinaryFilterProphecy = $this->prophesize(ApiFilterInterface::class);

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('dummyFilter')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->has('dummyBadFilter')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummyFilter')->willReturn($ormFilterProphecy->reveal())->shouldBeCalled();
        $filterLocatorProphecy->get('dummyBadFilter')->willReturn($ordinaryFilterProphecy->reveal())->shouldBeCalled();

        $filterExtensionTest = new FilterExtension($filterLocatorProphecy->reveal());
        $filterExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, $operation);
    }

    public function testApplyToCollectionWithoutFilters(): void
    {
        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);

        $filterLocatorProphecy->has(Argument::cetera())->willReturn(false);
        $filterLocatorProphecy->get(Argument::cetera())->shouldNotBeCalled();

        $filterExtensionTest = new FilterExtension($filterLocatorProphecy->reveal());
        $filterExtensionTest->applyToCollection($this->prophesize(QueryBuilder::class)->reveal(), new QueryNameGenerator(), Dummy::class, new GetCollection(filters: ['dummyFilter', 'dummyBadFilter']));
    }
}
