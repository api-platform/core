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
use ApiPlatform\Core\Metadata\Property\Factory\XmlPropertyMetadataFactory;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\XmlExtractor;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class XmlPropertyMetadataFactoryTest extends FileConfigurationMetadataFactoryProvider
{
    /**
     * @dataProvider propertyMetadataProvider
     */
    public function testCreate(PropertyMetadata $expectedPropertyMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';

        $propertyMetadataFactory = new XmlPropertyMetadataFactory(new XmlExtractor([$configPath]));
        $propertyMetadata = $propertyMetadataFactory->create(FileConfigDummy::class, 'foo');

        $this->assertInstanceOf(PropertyMetadata::class, $propertyMetadata);
        $this->assertEquals($expectedPropertyMetadata, $propertyMetadata);
    }

    /**
     * @dataProvider decoratedPropertyMetadataProvider
     */
    public function testCreateWithParentPropertyMetadataFactory(PropertyMetadata $expectedPropertyMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';

        $decorated = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decorated
            ->create(FileConfigDummy::class, 'foo', [])
            ->willReturn(new PropertyMetadata(null, null, null, null, true, null, null, false, null, null, ['Foo']))
            ->shouldBeCalled();

        $propertyMetadataFactory = new XmlPropertyMetadataFactory(new XmlExtractor([$configPath]), $decorated->reveal());
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
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcenotfound.xml';

        (new XmlPropertyMetadataFactory(new XmlExtractor([$configPath])))->create(\ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist::class, 'foo');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\PropertyNotFoundException
     * @expectedExceptionMessage Property "bar" of the resource class "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy" not found.
     */
    public function testCreateWithNonexistentProperty()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';

        (new XmlPropertyMetadataFactory(new XmlExtractor([$configPath])))->create(FileConfigDummy::class, 'bar');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /.+Element 'foo': This element is not expected\..+/
     */
    public function testCreateWithInvalidXml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/propertyinvalid.xml';

        (new XmlPropertyMetadataFactory(new XmlExtractor([$configPath])))->create(FileConfigDummy::class, 'foo');
    }
}
