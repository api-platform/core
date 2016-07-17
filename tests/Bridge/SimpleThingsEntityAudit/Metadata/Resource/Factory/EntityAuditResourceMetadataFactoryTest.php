<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Bridge\SimpleThingsEntityAudit\Metadata\Resource\Factory;

use ApiPlatform\Core\Bridge\SimpleThingsEntityAudit\Metadata\Resource\Factory\EntityAuditResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\EntityAudit\Metadata\MetadataFactory;

/**
 * Creates a resource metadata from EntityAudit Entity.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class EntityAuditResourceMetadataFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testAuditedEntity()
    {
        $resourceMetadata = new ResourceMetadata('Foo', 'My desc');
        $decoratedFactoryInterfaceProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedAuditManagerProphecy = $this->prophesize(AuditManager::class);

        $decoratedFactoryInterfaceProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();
        $decoratedAuditManagerProphecy->getMetadataFactory()->willReturn(new MetadataFactory(['Foo']))->shouldBeCalled();

        $decoratedFactoryInterface = $decoratedFactoryInterfaceProphecy->reveal();
        $decoratedAuditManager = $decoratedAuditManagerProphecy->reveal();
        $factory = new EntityAuditResourceMetadataFactory($decoratedFactoryInterface, $decoratedAuditManager);
        $this->assertSame($resourceMetadata->getShortName(), $factory->create('Foo')->getShortName());
        $this->assertArrayHasKey('audits', $factory->create('Foo')->getItemOperations());
        $this->assertArrayHasKey('audits', $factory->create('Foo')->getCollectionOperations());
    }

    public function testNoAuditedEntity()
    {
        $resourceMetadata = new ResourceMetadata('Bar', 'My desc');
        $decoratedFactoryInterfaceProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedAuditManagerProphecy = $this->prophesize(AuditManager::class);

        $decoratedFactoryInterfaceProphecy->create('Bar')->willReturn($resourceMetadata)->shouldBeCalled();
        $decoratedAuditManagerProphecy->getMetadataFactory()->willReturn(new MetadataFactory(['Foo']))->shouldBeCalled();

        $decoratedFactoryInterface = $decoratedFactoryInterfaceProphecy->reveal();
        $decoratedAuditManager = $decoratedAuditManagerProphecy->reveal();
        $factory = new EntityAuditResourceMetadataFactory($decoratedFactoryInterface, $decoratedAuditManager);
        $this->assertSame($resourceMetadata->getShortName(), $factory->create('Bar')->getShortName());
    }
}
