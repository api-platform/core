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

namespace ApiPlatform\Core\Tests\Metadata\Property\Factory;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Metadata\Extractor\XmlExtractor;
use ApiPlatform\Core\Metadata\Extractor\YamlExtractor;
use ApiPlatform\Core\Metadata\Property\Factory\ExtractorPropertyMetadataFactory;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\SubresourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\DummyResourceInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ExtractorPropertyMetadataFactoryTest extends FileConfigurationMetadataFactoryProvider
{
    /**
     * @dataProvider propertyMetadataProvider
     */
    public function testCreateXml(PropertyMetadata $expectedPropertyMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';

        $propertyMetadataFactory = new ExtractorPropertyMetadataFactory(new XmlExtractor([$configPath]));
        $propertyMetadata = $propertyMetadataFactory->create(FileConfigDummy::class, 'foo');

        $this->assertEquals($expectedPropertyMetadata, $propertyMetadata);
    }

    /**
     * @dataProvider decoratedPropertyMetadataProvider
     */
    public function testCreateWithParentPropertyMetadataFactoryXml(PropertyMetadata $expectedPropertyMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';

        $decorated = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decorated
            ->create(FileConfigDummy::class, 'foo', [])
            ->willReturn(new PropertyMetadata(null, null, null, null, true, null, null, false, null, null, ['Foo'], new SubresourceMetadata('Foo', false)))
            ->shouldBeCalled();

        $propertyMetadataFactory = new ExtractorPropertyMetadataFactory(new XmlExtractor([$configPath]), $decorated->reveal());
        $propertyMetadata = $propertyMetadataFactory->create(FileConfigDummy::class, 'foo');

        $this->assertEquals($expectedPropertyMetadata, $propertyMetadata);
    }

    public function testCreateWithNonexistentResourceXml()
    {
        $this->expectException(PropertyNotFoundException::class);
        $this->expectExceptionMessage('Property "foo" of the resource class "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\ThisDoesNotExist" not found.');

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcenotfound.xml';

        (new ExtractorPropertyMetadataFactory(new XmlExtractor([$configPath])))->create('ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist', 'foo');
    }

    public function testCreateWithNonexistentPropertyXml()
    {
        $this->expectException(PropertyNotFoundException::class);
        $this->expectExceptionMessage('Property "bar" of the resource class "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\FileConfigDummy" not found.');

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';

        (new ExtractorPropertyMetadataFactory(new XmlExtractor([$configPath])))->create(FileConfigDummy::class, 'bar');
    }

    public function testCreateWithInvalidXml()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('#.+Element \'\\{https://api-platform.com/schema/metadata\\}foo\': This element is not expected\\..+#');

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/propertyinvalid.xml';

        (new ExtractorPropertyMetadataFactory(new XmlExtractor([$configPath])))->create(FileConfigDummy::class, 'foo');
    }

    /**
     * @dataProvider propertyMetadataProvider
     */
    public function testCreateYaml(PropertyMetadata $expectedPropertyMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';

        $propertyMetadataFactory = new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath]));
        $propertyMetadata = $propertyMetadataFactory->create(FileConfigDummy::class, 'foo');

        $this->assertEquals($expectedPropertyMetadata, $propertyMetadata);
    }

    /**
     * @dataProvider decoratedPropertyMetadataProvider
     */
    public function testCreateWithParentPropertyMetadataFactoryYaml(PropertyMetadata $expectedPropertyMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';

        $decorated = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decorated
            ->create(FileConfigDummy::class, 'foo', [])
            ->willReturn(new PropertyMetadata(null, null, null, null, true, null, null, false, null, null, ['Foo'], new SubresourceMetadata('Foo', false)))
            ->shouldBeCalled();

        $propertyMetadataFactory = new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath]), $decorated->reveal());
        $propertyMetadata = $propertyMetadataFactory->create(FileConfigDummy::class, 'foo');

        $this->assertEquals($expectedPropertyMetadata, $propertyMetadata);
    }

    /**
     * @dataProvider decoratedPropertyMetadataProvider
     */
    public function testCreateWithCollectionTypedParentPropertyMetadataFactoryYaml(PropertyMetadata $expectedPropertyMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';

        $collectionType = new Type(Type::BUILTIN_TYPE_OBJECT,
                    false,
                    ArrayCollection::class,
                    true,
                    new Type(Type::BUILTIN_TYPE_INT),
                    new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)
                );

        $expectedPropertyMetadata = $expectedPropertyMetadata->withType($collectionType);
        $expectedPropertyMetadata = $expectedPropertyMetadata->withSubresource(new SubresourceMetadata(RelatedDummy::class, true, 1));

        $decorated = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decorated
            ->create(FileConfigDummy::class, 'foo', [])
            ->willReturn(new PropertyMetadata($collectionType, null, null, null, true, null, null, false, null, null, ['Foo'], null))
            ->shouldBeCalled();

        $propertyMetadataFactory = new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath]), $decorated->reveal());
        $propertyMetadata = $propertyMetadataFactory->create(FileConfigDummy::class, 'foo');

        $this->assertEquals($expectedPropertyMetadata, $propertyMetadata);
    }

    /**
     * @dataProvider decoratedPropertyMetadataProvider
     */
    public function testCreateWithTypedParentPropertyMetadataFactoryYaml(PropertyMetadata $expectedPropertyMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';

        $type = new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class);

        $expectedPropertyMetadata = $expectedPropertyMetadata->withType($type);
        $expectedPropertyMetadata = $expectedPropertyMetadata->withSubresource(new SubresourceMetadata(RelatedDummy::class, false, 1));

        $decorated = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decorated
            ->create(FileConfigDummy::class, 'foo', [])
            ->willReturn(new PropertyMetadata($type, null, null, null, true, null, null, false, null, null, ['Foo'], null))
            ->shouldBeCalled();

        $propertyMetadataFactory = new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath]), $decorated->reveal());
        $propertyMetadata = $propertyMetadataFactory->create(FileConfigDummy::class, 'foo');

        $this->assertEquals($expectedPropertyMetadata, $propertyMetadata);
    }

    public function testCreateWithNonexistentResourceYaml()
    {
        $this->expectException(PropertyNotFoundException::class);
        $this->expectExceptionMessage('Property "foo" of the resource class "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\ThisDoesNotExist" not found.');

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcenotfound.yml';

        (new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath])))->create('ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist', 'foo');
    }

    public function testCreateWithNonexistentPropertyYaml()
    {
        $this->expectException(PropertyNotFoundException::class);
        $this->expectExceptionMessage('Property "bar" of the resource class "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\FileConfigDummy" not found.');

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';

        (new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class, 'bar');
    }

    public function testCreateWithMalformedResourcesSettingYaml()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/"resources" setting is expected to be null or an array, string given in ".+\\/\\.\\.\\/\\.\\.\\/\\.\\.\\/Fixtures\\/FileConfigurations\\/resourcesinvalid\\.yml"\\./');

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesinvalid.yml';

        (new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class, 'foo');
    }

    public function testCreateWithMalformedPropertiesSettingYaml()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/"properties" setting is expected to be null or an array, string given in ".+\\/\\.\\.\\/\\.\\.\\/\\.\\.\\/Fixtures\\/FileConfigurations\\/propertiesinvalid\\.yml"\\./');

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/propertiesinvalid.yml';

        (new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class, 'foo');
    }

    public function testCreateWithMalformedPropertySettingYaml()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/"foo" setting is expected to be null or an array, string given in ".+\\/\\.\\.\\/\\.\\.\\/\\.\\.\\/Fixtures\\/FileConfigurations\\/propertyinvalid\\.yml"\\./');

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/propertyinvalid.yml';

        (new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class, 'foo');
    }

    public function testCreateWithMalformedYaml()
    {
        $this->expectException(InvalidArgumentException::class);

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/parse_exception.yml';

        (new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class, 'foo');
    }

    public function testItExtractPropertiesFromInterfaceResources()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/interface_resource.yml';

        $propertyMetadataFactory = new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath]));
        $metadataSomething = $propertyMetadataFactory->create(DummyResourceInterface::class, 'something');
        $metadataSomethingElse = $propertyMetadataFactory->create(DummyResourceInterface::class, 'somethingElse');

        $this->assertInstanceOf(PropertyMetadata::class, $metadataSomething);
        $this->assertInstanceOf(PropertyMetadata::class, $metadataSomethingElse);
        $this->assertTrue($metadataSomething->isIdentifier());
        $this->assertFalse($metadataSomethingElse->isWritable());
    }
}
