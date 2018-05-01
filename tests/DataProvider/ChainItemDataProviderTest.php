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
use ApiPlatform\Core\DataProvider\DenormalizedIdentifiersAwareItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CompositePrimitiveItem;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;

/**
 * Retrieves items from a persistence layer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ChainItemDataProviderTest extends TestCase
{
    public function testGetItem()
    {
        $dummy = new Dummy();
        $dummy->setName('Lucie');

        $firstDataProvider = $this->prophesize(DenormalizedIdentifiersAwareItemDataProviderInterface::class);
        $firstDataProvider->willImplement(RestrictedDataProviderInterface::class);
        $firstDataProvider->supports(Dummy::class, null, [])->willReturn(false);

        $secondDataProvider = $this->prophesize(DenormalizedIdentifiersAwareItemDataProviderInterface::class);
        $secondDataProvider->willImplement(RestrictedDataProviderInterface::class);
        $secondDataProvider->supports(Dummy::class, null, [])->willReturn(true);
        $secondDataProvider->getItem(Dummy::class, ['id' => 1], null, [])->willReturn($dummy);

        $thirdDataProvider = $this->prophesize(DenormalizedIdentifiersAwareItemDataProviderInterface::class);
        $thirdDataProvider->willImplement(RestrictedDataProviderInterface::class);
        $thirdDataProvider->supports(Dummy::class, null, [])->willReturn(true);
        $thirdDataProvider->getItem(Dummy::class, ['id' => 1], null, [])->willReturn(new \stdClass());

        $chainItemDataProvider = new ChainItemDataProvider([
            $firstDataProvider->reveal(),
            $secondDataProvider->reveal(),
            $thirdDataProvider->reveal(),
        ]);

        $this->assertEquals($dummy, $chainItemDataProvider->getItem(Dummy::class, ['id' => 1]));
    }

    public function testGetItemWithoutDenormalizedIdentifiers()
    {
        $dummy = new Dummy();
        $dummy->setName('Lucie');

        $firstDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $firstDataProvider->willImplement(RestrictedDataProviderInterface::class);
        $firstDataProvider->supports(Dummy::class, null, [])->willReturn(false);

        $secondDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $secondDataProvider->willImplement(RestrictedDataProviderInterface::class);
        $secondDataProvider->supports(Dummy::class, null, [])->willReturn(true);
        $secondDataProvider->getItem(Dummy::class, '1', null, [])->willReturn($dummy);

        $thirdDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $thirdDataProvider->willImplement(RestrictedDataProviderInterface::class);
        $thirdDataProvider->supports(Dummy::class, null, [])->willReturn(true);
        $thirdDataProvider->getItem(Dummy::class, 1, null, [])->willReturn(new \stdClass());

        $chainItemDataProvider = new ChainItemDataProvider([
            $firstDataProvider->reveal(),
            $secondDataProvider->reveal(),
            $thirdDataProvider->reveal(),
        ]);

        $this->assertEquals($dummy, $chainItemDataProvider->getItem(Dummy::class, ['id' => 1]));
    }

    public function testGetItemExceptions()
    {
        $firstDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $firstDataProvider->willImplement(RestrictedDataProviderInterface::class);
        $firstDataProvider->supports('notfound', null, [])->willReturn(false);

        $chainItemDataProvider = new ChainItemDataProvider([$firstDataProvider->reveal()]);

        $this->assertEquals('', $chainItemDataProvider->getItem('notfound', 1));
    }

    /**
     * @group legacy
     * @expectedDeprecation Throwing a "ApiPlatform\Core\Exception\ResourceClassNotSupportedException" is deprecated in favor of implementing "ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface"
     */
    public function testLegacyGetItem()
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

        $chainItemDataProvider->getItem(Dummy::class, 1);
    }

    /**
     * @group legacy
     * @expectedDeprecation Receiving "$id" as non-array in an item data provider is deprecated in 2.3 in favor of implementing "ApiPlatform\Core\DataProvider\DenormalizedIdentifiersAwareItemDataProviderInterface".
     */
    public function testLegacyGetItemWithoutDenormalizedIdentifiersAndCompositeIdentifier()
    {
        $dummy = new CompositePrimitiveItem('Lucie', 1984);

        $dataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $dataProvider->willImplement(RestrictedDataProviderInterface::class);
        $dataProvider->supports(CompositePrimitiveItem::class, null, [])->willReturn(true);
        $dataProvider->getItem(CompositePrimitiveItem::class, 'name=Lucie;year=1984', null, [])->willReturn($dummy);

        $chainItemDataProvider = new ChainItemDataProvider([
            $dataProvider->reveal(),
        ]);

        $this->assertEquals($dummy, $chainItemDataProvider->getItem(CompositePrimitiveItem::class, ['name' => 'Lucie', 'year' => 1984]));
    }

    /**
     * @group legacy
     * @expectedDeprecation Throwing a "ApiPlatform\Core\Exception\ResourceClassNotSupportedException" is deprecated in favor of implementing "ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface"
     */
    public function testLegacyGetItemExceptions()
    {
        $firstDataProvider = $this->prophesize(ItemDataProviderInterface::class);
        $firstDataProvider->getItem('notfound', 1, null, [])->willThrow(ResourceClassNotSupportedException::class);

        $chainItemDataProvider = new ChainItemDataProvider([$firstDataProvider->reveal()]);

        $this->assertEquals('', $chainItemDataProvider->getItem('notfound', 1));
    }
}
