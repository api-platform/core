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

namespace ApiPlatform\Core\Tests\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Extractor\XmlExtractor;
use ApiPlatform\Core\Metadata\Extractor\YamlExtractor;
use ApiPlatform\Core\Metadata\Resource\Factory\ExtractorResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ExtractorResourceNameCollectionFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;

/**
 * Tests extractor resource metadata factory.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class ExtractorResourceMetadataFactoryTest extends FileConfigurationMetadataFactoryProvider
{
    /**
     * @dataProvider resourceMetadataProvider
     */
    public function testXmlCreateResourceMetadata($expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new XmlExtractor([$configPath]));
        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);
        $resourceMetadataDummy = $resourceMetadataFactory->create(Dummy::class);

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
        $factory = new ExtractorResourceNameCollectionFactory(new XmlExtractor([$configPath]));
        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new XmlExtractor([$configPath]));

        foreach ($factory->create() as $resourceName) {
            $resourceMetadataFactory->create($resourceName);
        }
    }

    /**
     * @dataProvider optionalResourceMetadataProvider
     */
    public function testXmlOptionalResourceMetadata($expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesoptional.xml';

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new XmlExtractor([$configPath]));
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
        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new XmlExtractor([$configPath]));

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

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new XmlExtractor([$configPath]), $decorated->reveal());

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

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new XmlExtractor([$configPath]), $decorated->reveal());

        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @dataProvider resourceMetadataProvider
     */
    public function testYamlCreateResourceMetadata(ResourceMetadata $expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new YamlExtractor([$configPath]));
        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);
        $resourceMetadataDummy = $resourceMetadataFactory->create(Dummy::class);

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
        $factory = new ExtractorResourceNameCollectionFactory(new YamlExtractor([$configPath]));
        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new YamlExtractor([$configPath]));

        foreach ($factory->create() as $resourceName) {
            $resourceMetadataFactory->create($resourceName);
        }
    }

    /**
     * @dataProvider optionalResourceMetadataProvider
     */
    public function testYamlOptionalResourceMetadata($expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesoptional.yml';

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new YamlExtractor([$configPath]));
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

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new YamlExtractor([$configPath]));
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

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new YamlExtractor([$configPath]), $decorated->reveal());

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

        $resourceMetadataFactory = new ExtractorResourceMetadataFactory(new YamlExtractor([$configPath]), $decorated->reveal());

        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     */
    public function testCreateWithMalformedYaml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/parse_exception.yml';

        (new ExtractorResourceMetadataFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /"ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\Dummy" setting is expected to be null or an array, string given in ".+\/Fixtures\/FileConfigurations\/bad_declaration\.yml"\./
     */
    public function testCreateWithBadDeclaration()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/bad_declaration.yml';

        (new ExtractorResourceMetadataFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class);
    }
}
