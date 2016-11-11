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

use ApiPlatform\Core\Metadata\Resource\Factory\XmlResourceNameCollectionFactory;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Metadata\XmlExtractor;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;

/**
 * Tests XML resource name collection factory.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class XmlResourceNameCollectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testXmlResourceName()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';
        $xmlResourceNameCollectionFactory = new XmlResourceNameCollectionFactory(new XmlExtractor([$configPath]));

        $this->assertEquals($xmlResourceNameCollectionFactory->create(), new ResourceNameCollection([
            Dummy::class,
            FileConfigDummy::class,
        ]));
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     */
    public function testInvalidXmlResourceNameCollectionFactory()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesinvalid.xml';
        $xmlResourceNameCollectionFactory = new XmlResourceNameCollectionFactory(new XmlExtractor([$configPath]));
        $xmlResourceNameCollectionFactory->create();
    }
}
