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
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\SubresourceMetadata;
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

        $this->assertInstanceOf(PropertyMetadata::class, $propertyMetadata);
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

        $this->assertInstanceOf(PropertyMetadata::class, $propertyMetadata);
        $this->assertEquals($expectedPropertyMetadata, $propertyMetadata);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\PropertyNotFoundException
     * @expectedExceptionMessage Property "foo" of the resource class "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist" not found.
     */
    public function testCreateWithNonexistentResourceXml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcenotfound.xml';

        (new ExtractorPropertyMetadataFactory(new XmlExtractor([$configPath])))->create('ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist', 'foo');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\PropertyNotFoundException
     * @expectedExceptionMessage Property "bar" of the resource class "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy" not found.
     */
    public function testCreateWithNonexistentPropertyXml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';

        (new ExtractorPropertyMetadataFactory(new XmlExtractor([$configPath])))->create(FileConfigDummy::class, 'bar');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp #.+Element '\{https://api-platform.com/schema/metadata\}foo': This element is not expected\..+#
     */
    public function testCreateWithInvalidXml()
    {
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

        $this->assertInstanceOf(PropertyMetadata::class, $propertyMetadata);
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

        $this->assertInstanceOf(PropertyMetadata::class, $propertyMetadata);
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
        $expectedPropertyMetadata = $expectedPropertyMetadata->withSubresource(new SubresourceMetadata(RelatedDummy::class, true));

        $decorated = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decorated
            ->create(FileConfigDummy::class, 'foo', [])
            ->willReturn(new PropertyMetadata($collectionType, null, null, null, true, null, null, false, null, null, ['Foo'], null))
            ->shouldBeCalled();

        $propertyMetadataFactory = new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath]), $decorated->reveal());
        $propertyMetadata = $propertyMetadataFactory->create(FileConfigDummy::class, 'foo');

        $this->assertInstanceOf(PropertyMetadata::class, $propertyMetadata);
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
        $expectedPropertyMetadata = $expectedPropertyMetadata->withSubresource(new SubresourceMetadata(RelatedDummy::class, false));

        $decorated = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decorated
            ->create(FileConfigDummy::class, 'foo', [])
            ->willReturn(new PropertyMetadata($type, null, null, null, true, null, null, false, null, null, ['Foo'], null))
            ->shouldBeCalled();

        $propertyMetadataFactory = new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath]), $decorated->reveal());
        $propertyMetadata = $propertyMetadataFactory->create(FileConfigDummy::class, 'foo');

        $this->assertInstanceOf(PropertyMetadata::class, $propertyMetadata);
        $this->assertEquals($expectedPropertyMetadata, $propertyMetadata);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\PropertyNotFoundException
     * @expectedExceptionMessage Property "foo" of the resource class "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist" not found.
     */
    public function testCreateWithNonexistentResourceYaml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcenotfound.yml';

        (new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath])))->create('ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist', 'foo');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\PropertyNotFoundException
     * @expectedExceptionMessage Property "bar" of the resource class "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy" not found.
     */
    public function testCreateWithNonexistentPropertyYaml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';

        (new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class, 'bar');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /"resources" setting is expected to be null or an array, string given in ".+\/\.\.\/\.\.\/\.\.\/Fixtures\/FileConfigurations\/resourcesinvalid\.yml"\./
     */
    public function testCreateWithMalformedResourcesSettingYaml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesinvalid.yml';

        (new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class, 'foo');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /"properties" setting is expected to be null or an array, string given in ".+\/\.\.\/\.\.\/\.\.\/Fixtures\/FileConfigurations\/propertiesinvalid\.yml"\./
     */
    public function testCreateWithMalformedPropertiesSettingYaml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/propertiesinvalid.yml';

        (new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class, 'foo');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /"foo" setting is expected to be null or an array, string given in ".+\/\.\.\/\.\.\/\.\.\/Fixtures\/FileConfigurations\/propertyinvalid\.yml"\./
     */
    public function testCreateWithMalformedPropertySettingYaml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/propertyinvalid.yml';

        (new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class, 'foo');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     */
    public function testCreateWithMalformedYaml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/parse_exception.yml';

        (new ExtractorPropertyMetadataFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class, 'foo');
    }
}
