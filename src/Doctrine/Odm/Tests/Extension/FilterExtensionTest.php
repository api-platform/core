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

namespace ApiPlatform\Doctrine\Odm\Tests\Extension;

use ApiPlatform\Doctrine\Odm\Extension\FilterExtension;
use ApiPlatform\Doctrine\Odm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document\Dummy;
use ApiPlatform\Metadata\FilterInterface as ApiFilterInterface;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class FilterExtensionTest extends TestCase
{
    use ProphecyTrait;

    public function testApplyToCollectionWithValidFilters(): void
    {
        $aggregationBuilderProphecy = $this->prophesize(Builder::class);

        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $operation = (new GetCollection())->withFilters(['dummyFilter', 'dummyBadFilter']);

        $mongoDbOdmFilterProphecy = $this->prophesize(FilterInterface::class);
        $mongoDbOdmFilterProphecy->apply($aggregationBuilder, Dummy::class, $operation, ['filters' => []])->shouldBeCalled();

        $ordinaryFilterProphecy = $this->prophesize(ApiFilterInterface::class);

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('dummyFilter')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->has('dummyBadFilter')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('dummyFilter')->willReturn($mongoDbOdmFilterProphecy->reveal())->shouldBeCalled();
        $filterLocatorProphecy->get('dummyBadFilter')->willReturn($ordinaryFilterProphecy->reveal())->shouldBeCalled();

        $filterExtensionTest = new FilterExtension($filterLocatorProphecy->reveal());
        $filterExtensionTest->applyToCollection($aggregationBuilder, Dummy::class, $operation);
    }

    public function testApplyToCollectionWithoutFilters(): void
    {
        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);

        $filterLocatorProphecy->has(Argument::cetera())->willReturn(false);
        $filterLocatorProphecy->get(Argument::cetera())->shouldNotBeCalled();

        $filterExtensionTest = new FilterExtension($filterLocatorProphecy->reveal());
        $filterExtensionTest->applyToCollection($this->prophesize(Builder::class)->reveal(), Dummy::class, (new GetCollection())->withFilters(['dummyFilter', 'dummyBadFilter']));
    }
}
