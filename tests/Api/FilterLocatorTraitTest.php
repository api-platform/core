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

namespace ApiPlatform\Core\Tests\Api;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\FilterInterface;
use ApiPlatform\Core\Api\FilterLocatorTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class FilterLocatorTraitTest extends TestCase
{
    public function testSetFilterLocator()
    {
        $filterLocator = $this->prophesize(ContainerInterface::class)->reveal();

        $filterLocatorTraitImpl = $this->getFilterLocatorTraitImpl();
        $filterLocatorTraitImpl->setFilterLocator($filterLocator);

        $this->assertEquals($filterLocator, $filterLocatorTraitImpl->getFilterLocator());
    }

    /**
     * @group legacy
     * @expectedDeprecation The ApiPlatform\Core\Api\FilterCollection class is deprecated since version 2.1 and will be removed in 3.0. Provide an implementation of Psr\Container\ContainerInterface instead.
     */
    public function testSetFilterLocatorWithDeprecatedFilterCollection()
    {
        $filterCollection = new FilterCollection();

        $filterLocatorTraitImpl = $this->getFilterLocatorTraitImpl();
        $filterLocatorTraitImpl->setFilterLocator($filterCollection);

        $this->assertEquals($filterCollection, $filterLocatorTraitImpl->getFilterLocator());
    }

    public function testSetFilterLocatorWithNullAndNullAllowed()
    {
        $filterLocatorTraitImpl = $this->getFilterLocatorTraitImpl();
        $filterLocatorTraitImpl->setFilterLocator(null, true);

        $this->assertNull($filterLocatorTraitImpl->getFilterLocator());
    }

    /**
     * @group legacy
     */
    public function testSetFilterLocatorWithNullAndNullNotAllowed()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "$filterLocator" argument is expected to be an implementation of the "Psr\\Container\\ContainerInterface" interface.');

        $filterLocatorTraitImpl = $this->getFilterLocatorTraitImpl();
        $filterLocatorTraitImpl->setFilterLocator(null);
    }

    /**
     * @group legacy
     */
    public function testSetFilterLocatorWithInvalidFilterLocator()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "$filterLocator" argument is expected to be an implementation of the "Psr\\Container\\ContainerInterface" interface or null.');

        $filterLocatorTraitImpl = $this->getFilterLocatorTraitImpl();
        $filterLocatorTraitImpl->setFilterLocator(new \ArrayObject(), true);
    }

    public function testGetFilter()
    {
        $filter = $this->prophesize(FilterInterface::class)->reveal();

        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('foo')->willReturn(true)->shouldBeCalled();
        $filterLocatorProphecy->get('foo')->willReturn($filter)->shouldBeCalled();

        $filterLocatorTraitImpl = $this->getFilterLocatorTraitImpl();
        $filterLocatorTraitImpl->setFilterLocator($filterLocatorProphecy->reveal());

        $returnedFilter = $filterLocatorTraitImpl->getFilter('foo');

        $this->assertInstanceOf(FilterInterface::class, $returnedFilter);
        $this->assertEquals($filter, $returnedFilter);
    }

    public function testGetFilterWithNonexistentFilterId()
    {
        $filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $filterLocatorProphecy->has('foo')->willReturn(false)->shouldBeCalled();

        $filterLocatorTraitImpl = $this->getFilterLocatorTraitImpl();
        $filterLocatorTraitImpl->setFilterLocator($filterLocatorProphecy->reveal());

        $filter = $filterLocatorTraitImpl->getFilter('foo');

        $this->assertNull($filter);
    }

    /**
     * @group legacy
     * @expectedDeprecation The ApiPlatform\Core\Api\FilterCollection class is deprecated since version 2.1 and will be removed in 3.0. Provide an implementation of Psr\Container\ContainerInterface instead.
     */
    public function testGetFilterWithDeprecatedFilterCollection()
    {
        $filter = $this->prophesize(FilterInterface::class)->reveal();

        $filterLocatorTraitImpl = $this->getFilterLocatorTraitImpl();
        $filterLocatorTraitImpl->setFilterLocator(new FilterCollection(['foo' => $filter]));

        $returnedFilter = $filterLocatorTraitImpl->getFilter('foo');

        $this->assertInstanceOf(FilterInterface::class, $returnedFilter);
        $this->assertEquals($filter, $returnedFilter);
    }

    /**
     * @group legacy
     * @expectedDeprecation The ApiPlatform\Core\Api\FilterCollection class is deprecated since version 2.1 and will be removed in 3.0. Provide an implementation of Psr\Container\ContainerInterface instead.
     */
    public function testGetFilterWithNonexistentFilterIdAndDeprecatedFilterCollection()
    {
        $filterLocatorTraitImpl = $this->getFilterLocatorTraitImpl();
        $filterLocatorTraitImpl->setFilterLocator(new FilterCollection());

        $filter = $filterLocatorTraitImpl->getFilter('foo');

        $this->assertNull($filter);
    }

    private function getFilterLocatorTraitImpl()
    {
        return new class() {
            use FilterLocatorTrait {
                FilterLocatorTrait::setFilterLocator as public;
                FilterLocatorTrait::getFilter as public;
            }

            public function getFilterLocator()
            {
                return $this->filterLocator;
            }
        };
    }
}
