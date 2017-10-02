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

namespace ApiPlatform\Core\Tests\DataProvider;

use ApiPlatform\Core\DataProvider\ChainItemDataProvider;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;

/**
 * Retrieves items from a persistence layer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ChainItemDataProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetItem()
    {
        $dummy = new Dummy();
        $dummy->setName('Lucie');

        $firstDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $firstDataProvider->getItem(Dummy::class, 1, null, [])->willThrow(ResourceClassNotSupportedException::class);

        $secondDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $secondDataProvider->getItem(Dummy::class, 1, null, [])->willReturn($dummy);

        $thirdDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $thirdDataProvider->getItem(Dummy::class, 1, null, [])->willReturn(new \stdClass());

        $chainItemDataProvider = new ChainItemDataProvider([$firstDataProvider->reveal(), $secondDataProvider->reveal(), $thirdDataProvider->reveal()]);

        $this->assertEquals($dummy, $chainItemDataProvider->getItem(Dummy::class, 1));
    }

    public function testGetItemExeptions()
    {
        $firstDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $firstDataProvider->getItem('notfound', 1, null, [])->willThrow(ResourceClassNotSupportedException::class);

        $chainItemDataProvider = new ChainItemDataProvider([$firstDataProvider->reveal()]);

        $this->assertEquals('', $chainItemDataProvider->getItem('notfound', 1));
    }
}
