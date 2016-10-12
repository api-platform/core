<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Metadata\Property\Factory;

use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\YamlPropertyMetadataFactory;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class YamlPropertyMetadataFactoryTest extends FileConfigurationMetadataFactoryProvider
{
    /**
     * @dataProvider propertyMetadataProvider
     */
    public function testCreate(PropertyMetadata $expectedPropertyMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';

        $propertyMetadataFactory = new YamlPropertyMetadataFactory([$configPath]);
        $propertyMetadata = $propertyMetadataFactory->create(FileConfigDummy::class, 'foo');

        $this->assertInstanceOf(PropertyMetadata::class, $propertyMetadata);
        $this->assertEquals($expectedPropertyMetadata, $propertyMetadata);
    }

    /**
     * @dataProvider decoratedPropertyMetadataProvider
     */
    public function testCreateWithParentPropertyMetadataFactory(PropertyMetadata $expectedPropertyMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';

        $decorated = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decorated
            ->create(FileConfigDummy::class, 'foo', [])
            ->willReturn(new PropertyMetadata(null, null, null, null, true, null, null, false, null, null, ['Foo']))
            ->shouldBeCalled();

        $propertyMetadataFactory = new YamlPropertyMetadataFactory([$configPath], $decorated->reveal());
        $propertyMetadata = $propertyMetadataFactory->create(FileConfigDummy::class, 'foo');

        $this->assertInstanceOf(PropertyMetadata::class, $propertyMetadata);
        $this->assertEquals($expectedPropertyMetadata, $propertyMetadata);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\PropertyNotFoundException
     * @expectedExceptionMessage Property "foo" of the resource class "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist" not found.
     */
    public function testCreateWithNonexistentResource()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcenotfound.yml';

        (new YamlPropertyMetadataFactory([$configPath]))->create(\ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist::class, 'foo');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\PropertyNotFoundException
     * @expectedExceptionMessage Property "bar" of the resource class "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy" not found.
     */
    public function testCreateWithNonexistentProperty()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';

        (new YamlPropertyMetadataFactory([$configPath]))->create(FileConfigDummy::class, 'bar');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /"resources" setting is expected to be null or an array, string given in ".+\/\.\.\/\.\.\/\.\.\/Fixtures\/FileConfigurations\/resourcesinvalid\.yml"\./
     */
    public function testCreateWithMalformedResourcesSetting()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesinvalid.yml';

        (new YamlPropertyMetadataFactory([$configPath]))->create(FileConfigDummy::class, 'foo');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /"properties" setting is expected to be null or an array, string given in ".+\/\.\.\/\.\.\/\.\.\/Fixtures\/FileConfigurations\/propertiesinvalid\.yml"\./
     */
    public function testCreateWithMalformedPropertiesSetting()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/propertiesinvalid.yml';

        (new YamlPropertyMetadataFactory([$configPath]))->create(FileConfigDummy::class, 'foo');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /"foo" setting is expected to be null or an array, string given in ".+\/\.\.\/\.\.\/\.\.\/Fixtures\/FileConfigurations\/propertyinvalid\.yml"\./
     */
    public function testCreateWithMalformedPropertySetting()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/propertyinvalid.yml';

        (new YamlPropertyMetadataFactory([$configPath]))->create(FileConfigDummy::class, 'foo');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /"class" setting is expected to be a string, none given in ".+\/\.\.\/\.\.\/\.\.\/Fixtures\/FileConfigurations\/resourcenoclass\.yml"\./
     */
    public function testCreateWithoutResourceClass()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcenoclass.yml';

        (new YamlPropertyMetadataFactory([$configPath]))->create(FileConfigDummy::class, 'foo');
    }

    /**
     * @expectedException \Symfony\Component\Yaml\Exception\ParseException
     */
    public function testCreateWithMalformedYaml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/parse_exception.yml';

        (new YamlPropertyMetadataFactory([$configPath]))->create(FileConfigDummy::class, 'foo');
    }
}
