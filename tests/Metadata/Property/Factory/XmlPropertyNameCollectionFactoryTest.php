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

use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\XmlPropertyNameCollectionFactory;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class XmlPropertyNameCollectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';

        $this->assertEquals(
            (new XmlPropertyNameCollectionFactory([$configPath]))->create(FileConfigDummy::class),
            new PropertyNameCollection(['foo', 'name'])
        );
    }

    public function testCreateWithParentPropertyNameCollectionFactory()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';

        $decorated = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $decorated
            ->create(FileConfigDummy::class, [])
            ->willReturn(new PropertyNameCollection(['id']))
            ->shouldBeCalled();

        $this->assertEquals(
            (new XmlPropertyNameCollectionFactory([$configPath], $decorated->reveal()))->create(FileConfigDummy::class),
            new PropertyNameCollection(['foo', 'name', 'id'])
        );
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\ResourceClassNotFoundException
     * @expectedExceptionMessage The resource class "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist" does not exist.
     */
    public function testCreateWithNonexistentResource()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcenotfound.xml';

        (new XmlPropertyNameCollectionFactory([$configPath]))->create(\ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist::class);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /.+Element 'foo': This element is not expected\..+/
     */
    public function testCreateWithInvalidXml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/propertyinvalid.xml';

        (new XmlPropertyNameCollectionFactory([$configPath]))->create(FileConfigDummy::class);
    }
}
