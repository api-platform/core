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

use ApiPlatform\Core\Metadata\Extractor\XmlExtractor;
use ApiPlatform\Core\Metadata\Extractor\YamlExtractor;
use ApiPlatform\Core\Metadata\Property\Factory\ExtractorPropertyMetadataFactory;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\SubresourceMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Tests\Fixtures\DummyResourceInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @group legacy
 */
class ExtractorPropertyMetadataFactoryTest extends FileConfigurationMetadataFactoryProvider
{
    use ProphecyTrait;

    /**
     * @dataProvider propertyMetadataProvider
     */
    public function testCreateXml(ApiProperty $expectedPropertyMetadata)
    {
        $configPath = __DIR__.'/../../../../Fixtures/FileConfigurations/resources.xml';

        $propertyMetadataFactory = new ExtractorPropertyMetadataFactory(new XmlExtractor([$configPath]));
        $propertyMetadata = $propertyMetadataFactory->create(FileConfigDummy::class, 'foo');

        $this->assertEquals($expectedPropertyMetadata, $propertyMetadata);
    }

    /**
     * @dataProvider decoratedPropertyMetadataProvider
     */
    public function testCreateWithParentPropertyMetadataFactoryXml(ApiProperty $expectedPropertyMetadata)
    {
        $configPath = __DIR__.'/../../../../Fixtures/FileConfigurations/resources.xml';

        $decorated = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decorated
            ->create(FileConfigDummy::class, 'foo', [])
            ->willReturn((new ApiProperty())->withReadableLink(true)->withIdentifier(false)->withSubresource(new SubresourceMetadata('Foo', false, null)))
            ->shouldBeCalled();

        $propertyMetadataFactory = new ExtractorPropertyMetadataFactory(new XmlExtractor([$configPath]), $decorated->reveal());
        $propertyMetadata = $propertyMetadataFactory->create(FileConfigDummy::class, 'foo');

        $this->assertEquals($expectedPropertyMetadata, $propertyMetadata);
    }

    public function testCreateWithNonexistentResourceXml()
    {
        $this->expectException(PropertyNotFoundException::class);
        $this->expectExceptionMessage('Property "foo" of the resource class "ApiPlatform\\Tests\\Fixtures\\TestBundle\\Entity\\ThisDoesNotExist" not found.');

        $configPath = __DIR__.'/../../../../Fixtures/FileConfigurations/resourcenotfound.xml';

        (new ExtractorPropertyMetadataFactory(new XmlExtractor([$configPath])))->create('ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist', 'foo');
    }

    public function testCreateWithNonexistentPropertyXml()
    {
        $this->expectException(PropertyNotFoundException::class);
        $this->expectExceptionMessage('Property "bar" of the resource class "ApiPlatform\\Tests\\Fixtures\\TestBundle\\Entity\\FileConfigDummy" not found.');

        $configPath = __DIR__.'/../../../../Fixtures/FileConfigurations/resources.xml';

        (new ExtractorPropertyMetadataFactory(new XmlExtractor([$configPath])))->create(FileConfigDummy::class, 'bar');
    }

    public function testCreateWithInvalidXml()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('#.+Element \'\\{https://api-platform.com/schema/metadata\\}foo\': This element is not expected\\..+#');

        $configPath = __DIR__.'/../../../../Fixtures/FileConfigurations/propertyinvalid.xml';

        (new ExtractorPropertyMetadataFactory(new XmlExtractor([$configPath])))->create(FileConfigDummy::class, 'foo');
    }

    /**
     * @dataProvider propertyMetadataProvider
     */
    public function testCreateYaml(ApiProperty $expectedPropertyMetadata)
    {
        $configPath = __DIR__.'/../../../../Fixtures/FileConfigurations/resources.yml';

        $propertyMetadataFactory = new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath]));
        $propertyMetadata = $propertyMetadataFactory->create(FileConfigDummy::class, 'foo');

        $this->assertEquals($expectedPropertyMetadata, $propertyMetadata);
    }

    /**
     * @dataProvider decoratedPropertyMetadataProvider
     */
    public function testCreateWithParentPropertyMetadataFactoryYaml(ApiProperty $expectedPropertyMetadata)
    {
        $configPath = __DIR__.'/../../../../Fixtures/FileConfigurations/resources.yml';

        $decorated = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decorated
            ->create(FileConfigDummy::class, 'foo', [])
            ->willReturn((new ApiProperty())->withReadableLink(true)->withIdentifier(false)->withSubresource(new SubresourceMetadata('Foo', false)))
            ->shouldBeCalled();

        $propertyMetadataFactory = new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath]), $decorated->reveal());
        $propertyMetadata = $propertyMetadataFactory->create(FileConfigDummy::class, 'foo');

        $this->assertEquals($expectedPropertyMetadata, $propertyMetadata);
    }

    /**
     * @dataProvider decoratedPropertyMetadataProvider
     */
    public function testCreateWithCollectionTypedParentPropertyMetadataFactoryYaml(ApiProperty $expectedPropertyMetadata)
    {
        $configPath = __DIR__.'/../../../../Fixtures/FileConfigurations/resources.yml';

        $collectionType = new Type(Type::BUILTIN_TYPE_OBJECT,
                    false,
                    ArrayCollection::class,
                    true,
                    new Type(Type::BUILTIN_TYPE_INT),
                    new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)
                );

        $expectedPropertyMetadata = $expectedPropertyMetadata->withBuiltinTypes([$collectionType]);
        $expectedPropertyMetadata = $expectedPropertyMetadata->withSubresource(new SubresourceMetadata(RelatedDummy::class, true, 1));

        $decorated = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decorated
            ->create(FileConfigDummy::class, 'foo', [])
            ->willReturn((new ApiProperty())->withBuiltinTypes([$collectionType])->withReadableLink(true)->withIdentifier(false))
            ->shouldBeCalled();

        $propertyMetadataFactory = new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath]), $decorated->reveal());
        $propertyMetadata = $propertyMetadataFactory->create(FileConfigDummy::class, 'foo');

        $this->assertEquals($expectedPropertyMetadata, $propertyMetadata);
    }

    /**
     * @dataProvider decoratedPropertyMetadataProvider
     */
    public function testCreateWithTypedParentPropertyMetadataFactoryYaml(ApiProperty $expectedPropertyMetadata)
    {
        $configPath = __DIR__.'/../../../../Fixtures/FileConfigurations/resources.yml';

        $type = new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class);

        $expectedPropertyMetadata = $expectedPropertyMetadata->withBuiltinTypes([$type]);
        $expectedPropertyMetadata = $expectedPropertyMetadata->withSubresource(new SubresourceMetadata(RelatedDummy::class, false, 1));

        $decorated = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decorated
            ->create(FileConfigDummy::class, 'foo', [])
            ->willReturn((new ApiProperty())->withBuiltinTypes([$type])->withReadableLink(true)->withIdentifier(false))
            ->shouldBeCalled();

        $propertyMetadataFactory = new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath]), $decorated->reveal());
        $propertyMetadata = $propertyMetadataFactory->create(FileConfigDummy::class, 'foo');

        $this->assertEquals($expectedPropertyMetadata, $propertyMetadata);
    }

    public function testCreateWithNonexistentResourceYaml()
    {
        $this->expectException(PropertyNotFoundException::class);
        $this->expectExceptionMessage('Property "foo" of the resource class "ApiPlatform\\Tests\\Fixtures\\TestBundle\\Entity\\ThisDoesNotExist" not found.');

        $configPath = __DIR__.'/../../../../Fixtures/FileConfigurations/resourcenotfound.yml';

        (new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath])))->create('ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist', 'foo');
    }

    public function testCreateWithNonexistentPropertyYaml()
    {
        $this->expectException(PropertyNotFoundException::class);
        $this->expectExceptionMessage('Property "bar" of the resource class "ApiPlatform\\Tests\\Fixtures\\TestBundle\\Entity\\FileConfigDummy" not found.');

        $configPath = __DIR__.'/../../../../Fixtures/FileConfigurations/resources.yml';

        (new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class, 'bar');
    }

    public function testCreateWithMalformedResourcesSettingYaml()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/"resources" setting is expected to be null or an array, string given in ".+\\/\\.\\.\\/\\.\\.\\/\\.\\.\\/\\.\\.\\/Fixtures\\/FileConfigurations\\/resourcesinvalid\\.yml"\\./');

        $configPath = __DIR__.'/../../../../Fixtures/FileConfigurations/resourcesinvalid.yml';

        (new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class, 'foo');
    }

    public function testCreateWithMalformedPropertiesSettingYaml()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/"properties" setting is expected to be null or an array, string given in ".+\\/\\.\\.\\/\\.\\.\\/\\.\\.\\/\\.\\.\\/Fixtures\\/FileConfigurations\\/propertiesinvalid\\.yml"\\./');

        $configPath = __DIR__.'/../../../../Fixtures/FileConfigurations/propertiesinvalid.yml';

        (new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class, 'foo');
    }

    public function testCreateWithMalformedPropertySettingYaml()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/"foo" setting is expected to be null or an array, string given in ".+\\/\\.\\.\\/\\.\\.\\/\\.\\.\\/\\.\\.\\/Fixtures\\/FileConfigurations\\/propertyinvalid\\.yml"\\./');

        $configPath = __DIR__.'/../../../../Fixtures/FileConfigurations/propertyinvalid.yml';

        (new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class, 'foo');
    }

    public function testCreateWithMalformedYaml()
    {
        $this->expectException(InvalidArgumentException::class);

        $configPath = __DIR__.'/../../../../Fixtures/FileConfigurations/parse_exception.yml';

        (new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class, 'foo');
    }

    public function testItExtractPropertiesFromInterfaceResources()
    {
        $configPath = __DIR__.'/../../../../Fixtures/FileConfigurations/interface_resource.yml';

        $propertyMetadataFactory = new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath]));
        $metadataSomething = $propertyMetadataFactory->create(DummyResourceInterface::class, 'something');
        $metadataSomethingElse = $propertyMetadataFactory->create(DummyResourceInterface::class, 'somethingElse');

        $this->assertInstanceOf(ApiProperty::class, $metadataSomething);
        $this->assertInstanceOf(ApiProperty::class, $metadataSomethingElse);
        $this->assertTrue($metadataSomething->isIdentifier());
        $this->assertFalse($metadataSomethingElse->isWritable());
    }
}
