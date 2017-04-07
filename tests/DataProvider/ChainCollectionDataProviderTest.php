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

use ApiPlatform\Core\DataProvider\ChainCollectionDataProvider;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;

/**
 * Retrieves items from a persistence layer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ChainCollectionDataProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCollection()
    {
        $dummy = new Dummy();
        $dummy->setName('Rosa');
        $dummy2 = new Dummy();
        $dummy2->setName('Parks');

        $firstDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $firstDataProvider->getCollection(Dummy::class, null)->willReturn([$dummy, $dummy2])->willThrow(ResourceClassNotSupportedException::class);

        $secondDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $secondDataProvider->getCollection(Dummy::class, null)->willReturn([$dummy, $dummy2]);

        $thirdDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $thirdDataProvider->getCollection(Dummy::class, null)->willReturn([$dummy]);

        $chainItemDataProvider = new ChainCollectionDataProvider([$firstDataProvider->reveal(), $secondDataProvider->reveal(), $thirdDataProvider->reveal()]);

        $this->assertEquals([$dummy, $dummy2], $chainItemDataProvider->getCollection(Dummy::class));
    }

    public function testGetCollectionExceptions()
    {
        $firstDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $firstDataProvider->getCollection('notfound', 'op')->willThrow(ResourceClassNotSupportedException::class);

        $chainItemDataProvider = new ChainCollectionDataProvider([$firstDataProvider->reveal()]);

        $this->assertEquals('', $chainItemDataProvider->getCollection('notfound', 'op'));
    }
}
