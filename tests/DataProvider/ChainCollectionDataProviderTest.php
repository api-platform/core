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
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;

/**
 * Retrieves items from a persistence layer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ChainCollectionDataProviderTest extends TestCase
{
    public function testGetCollection()
    {
        $dummy = new Dummy();
        $dummy->setName('Rosa');
        $dummy2 = new Dummy();
        $dummy2->setName('Parks');

        $firstDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $firstDataProvider->willImplement(RestrictedDataProviderInterface::class);
        $firstDataProvider->supports(Dummy::class, null, [])->willReturn(false);

        $secondDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $secondDataProvider->willImplement(RestrictedDataProviderInterface::class);
        $secondDataProvider->supports(Dummy::class, null, [])->willReturn(true);
        $secondDataProvider->getCollection(Dummy::class, null, [])
            ->willReturn([$dummy, $dummy2]);

        $thirdDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $thirdDataProvider->willImplement(RestrictedDataProviderInterface::class);
        $thirdDataProvider->supports(Dummy::class, null, [])->willReturn(true);
        $thirdDataProvider->getCollection(Dummy::class, null, [])->willReturn([$dummy]);

        $chainItemDataProvider = new ChainCollectionDataProvider([
            $firstDataProvider->reveal(),
            $secondDataProvider->reveal(),
            $thirdDataProvider->reveal(),
        ]);

        $this->assertEquals(
            [$dummy, $dummy2],
            $chainItemDataProvider->getCollection(Dummy::class)
        );
    }

    public function testGetCollectionNotSupported()
    {
        $firstDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $firstDataProvider->willImplement(RestrictedDataProviderInterface::class);
        $firstDataProvider->supports('notfound', 'op', [])->willReturn(false);

        $collection = (new ChainCollectionDataProvider([$firstDataProvider->reveal()]))->getCollection('notfound', 'op');

        $this->assertTrue(is_iterable($collection));
        $this->assertEmpty($collection);
    }

    /**
     * @group legacy
     * @expectedDeprecation Throwing a "ApiPlatform\Core\Exception\ResourceClassNotSupportedException" in a data provider is deprecated in favor of implementing "ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface"
     */
    public function testLegacyGetCollection()
    {
        $dummy = new Dummy();
        $dummy->setName('Rosa');
        $dummy2 = new Dummy();
        $dummy2->setName('Parks');

        $firstDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $firstDataProvider->getCollection(Dummy::class, null, [])->willThrow(ResourceClassNotSupportedException::class);

        $secondDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $secondDataProvider->getCollection(Dummy::class, null, [])->willReturn([$dummy, $dummy2]);

        $thirdDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $thirdDataProvider->getCollection(Dummy::class, null, [])->willReturn([$dummy]);

        $chainItemDataProvider = new ChainCollectionDataProvider([$firstDataProvider->reveal(), $secondDataProvider->reveal(), $thirdDataProvider->reveal()]);

        $this->assertEquals([$dummy, $dummy2], $chainItemDataProvider->getCollection(Dummy::class));
    }

    /**
     * @group legacy
     * @expectedDeprecation Throwing a "ApiPlatform\Core\Exception\ResourceClassNotSupportedException" in a data provider is deprecated in favor of implementing "ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface"
     */
    public function testLegacyGetCollectionExceptions()
    {
        $firstDataProvider = $this->prophesize(CollectionDataProviderInterface::class);
        $firstDataProvider->getCollection('notfound', 'op', [])->willThrow(ResourceClassNotSupportedException::class);

        $collection = (new ChainCollectionDataProvider([$firstDataProvider->reveal()]))->getCollection('notfound', 'op');

        $this->assertTrue(is_iterable($collection));
        $this->assertEmpty($collection);
    }

    public function testGetCollectionWithEmptyDataProviders()
    {
        $collection = (new ChainCollectionDataProvider([]))->getCollection(Dummy::class);

        $this->assertTrue(is_iterable($collection));
        $this->assertEmpty($collection);
    }
}
