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

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\XmlResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\XmlResourceNameCollectionFactory;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\XmlExtractor;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;

/**
 * Tests XML resource metadata factory.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class XmlResourceMetadataFactoryTest extends FileConfigurationMetadataFactoryProvider
{
    /**
     * @dataProvider resourceMetadataProvider
     */
    public function testXmlCreateResourceMetadata($expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';

        $resourceMetadataFactory = new XmlResourceMetadataFactory(new XmlExtractor([$configPath]));
        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertInstanceOf(ResourceMetadata::class, $resourceMetadata);
        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\ResourceClassNotFoundException
     * @expectedExceptionMessage Resource "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist" not found.
     */
    public function testXmlDoesNotExistMetadataFactory()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcenotfound.xml';
        $xmlResourceNameCollectionFactory = new XmlResourceNameCollectionFactory(new XmlExtractor([$configPath]));
        $resourceMetadataFactory = new XmlResourceMetadataFactory(new XmlExtractor([$configPath]));

        foreach ($xmlResourceNameCollectionFactory->create() as $resourceName) {
            $resourceMetadataFactory->create($resourceName);
        }
    }

    /**
     * @dataProvider optionalResourceMetadataProvider
     */
    public function testXmlOptionalResourceMetadata($expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesoptional.xml';

        $resourceMetadataFactory = new XmlResourceMetadataFactory(new XmlExtractor([$configPath]));
        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertInstanceOf(ResourceMetadata::class, $resourceMetadata);

        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     */
    public function testInvalidXmlResourceMetadataFactory()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesinvalid.xml';
        $resourceMetadataFactory = new XmlResourceMetadataFactory(new XmlExtractor([$configPath]));

        $resourceMetadataFactory->create(FileConfigDummy::class);
    }

    /**
     * @dataProvider optionalResourceMetadataProvider
     */
    public function testXmlParentResourceMetadataFactory(ResourceMetadata $expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesoptional.xml';

        $decorated = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decorated->create(FileConfigDummy::class)->willReturn(new ResourceMetadata(null, 'test'))->shouldBeCalled();

        $resourceMetadataFactory = new XmlResourceMetadataFactory(new XmlExtractor([$configPath]), $decorated->reveal());

        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);
        $expectedResourceMetadata = $expectedResourceMetadata->withDescription('test');

        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @dataProvider resourceMetadataProvider
     */
    public function testXmlExistingParentResourceMetadataFactory(ResourceMetadata $expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';

        $decorated = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decorated->create(FileConfigDummy::class)->willReturn($expectedResourceMetadata)->shouldBeCalled();

        $resourceMetadataFactory = new XmlResourceMetadataFactory(new XmlExtractor([$configPath]), $decorated->reveal());

        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }
}
