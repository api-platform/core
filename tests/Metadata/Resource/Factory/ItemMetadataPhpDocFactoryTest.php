<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\Factory\ItemMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ItemMetadataPhpDocFactory;
use ApiPlatform\Core\Metadata\Resource\ItemMetadata;

class ItemMetadataPhpDocFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testExistingDescription()
    {
        $itemMetadata = new ItemMetadata(null, 'My desc');
        $decoratedProhpecy = $this->prohesize(ItemMetadataFactoryInterface::class);
        $decoratedProhpecy->create('Foo')->willReturn($itemMetadata)->shouldBeCalled();

        $factory = new ItemMetadataPhpDocFactory();
        $this->assertSame($itemMetadata, $factory->create('Foo'));
    }
}
