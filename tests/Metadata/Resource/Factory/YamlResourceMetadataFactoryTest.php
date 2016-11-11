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
use ApiPlatform\Core\Metadata\Resource\Factory\YamlResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\YamlResourceNameCollectionFactory;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\YamlExtractor;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;

/**
 * Tests yaml resource metadata factory.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class YamlResourceMetadataFactoryTest extends FileConfigurationMetadataFactoryProvider
{
    /**
     * @dataProvider resourceMetadataProvider
     */
    public function testYamlCreateResourceMetadata(ResourceMetadata $expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';

        $resourceMetadataFactory = new YamlResourceMetadataFactory(new YamlExtractor([$configPath]));
        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertInstanceOf(ResourceMetadata::class, $resourceMetadata);
        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\ResourceClassNotFoundException
     * @expectedExceptionMessage Resource "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist" not found.
     */
    public function testYamlDoesNotExistMetadataFactory()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcenotfound.yml';
        $yamlResourceNameCollectionFactory = new YamlResourceNameCollectionFactory(new YamlExtractor([$configPath]));
        $resourceMetadataFactory = new YamlResourceMetadataFactory(new YamlExtractor([$configPath]));

        foreach ($yamlResourceNameCollectionFactory->create() as $resourceName) {
            $resourceMetadataFactory->create($resourceName);
        }
    }

    /**
     * @dataProvider optionalResourceMetadataProvider
     */
    public function testYamlOptionalResourceMetadata($expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesoptional.yml';

        $resourceMetadataFactory = new YamlResourceMetadataFactory(new YamlExtractor([$configPath]));
        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertInstanceOf(ResourceMetadata::class, $resourceMetadata);
        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @dataProvider resourceMetadataProvider
     */
    public function testYamlSingleResourceMetadata(ResourceMetadata $expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/single_resource.yml';

        $resourceMetadataFactory = new YamlResourceMetadataFactory(new YamlExtractor([$configPath]));
        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertInstanceOf(ResourceMetadata::class, $resourceMetadata);
        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @dataProvider optionalResourceMetadataProvider
     */
    public function testYamlParentResourceMetadataFactory(ResourceMetadata $expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesoptional.yml';

        $decorated = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decorated->create(FileConfigDummy::class)->willReturn(new ResourceMetadata(null, 'test'))->shouldBeCalled();

        $resourceMetadataFactory = new YamlResourceMetadataFactory(new YamlExtractor([$configPath]), $decorated->reveal());

        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);
        $expectedResourceMetadata = $expectedResourceMetadata->withDescription('test');

        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @dataProvider resourceMetadataProvider
     */
    public function testYamlExistingParentResourceMetadataFactory(ResourceMetadata $expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';

        $decorated = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decorated->create(FileConfigDummy::class)->willReturn($expectedResourceMetadata)->shouldBeCalled();

        $resourceMetadataFactory = new YamlResourceMetadataFactory(new YamlExtractor([$configPath]), $decorated->reveal());

        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @expectedException \Symfony\Component\Yaml\Exception\ParseException
     */
    public function testCreateWithMalformedYaml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/parse_exception.yml';

        (new YamlResourceMetadataFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class);
    }
}
