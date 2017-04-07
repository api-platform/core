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
use ApiPlatform\Core\Metadata\Property\Factory\ExtractorPropertyNameCollectionFactory;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ExtractorPropertyNameCollectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateXml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';

        $this->assertEquals(
            (new ExtractorPropertyNameCollectionFactory(new XmlExtractor([$configPath])))->create(FileConfigDummy::class),
            new PropertyNameCollection(['foo', 'name'])
        );
    }

    public function testCreateWithParentPropertyNameCollectionFactoryXml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';

        $decorated = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $decorated
            ->create(FileConfigDummy::class, [])
            ->willReturn(new PropertyNameCollection(['id']))
            ->shouldBeCalled();

        $this->assertEquals(
            (new ExtractorPropertyNameCollectionFactory(new XmlExtractor([$configPath]), $decorated->reveal()))->create(FileConfigDummy::class),
            new PropertyNameCollection(['id', 'foo', 'name'])
        );
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\ResourceClassNotFoundException
     * @expectedExceptionMessage The resource class "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist" does not exist.
     */
    public function testCreateWithNonexistentResourceXml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcenotfound.xml';

        (new ExtractorPropertyNameCollectionFactory(new XmlExtractor([$configPath])))->create(\ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist::class);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /.+Element 'foo': This element is not expected\..+/
     */
    public function testCreateWithInvalidXml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/propertyinvalid.xml';

        (new ExtractorPropertyNameCollectionFactory(new XmlExtractor([$configPath])))->create(FileConfigDummy::class);
    }

    public function testCreateYaml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';

        $this->assertEquals(
            (new ExtractorPropertyNameCollectionFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class),
            new PropertyNameCollection(['foo', 'name'])
        );
    }

    public function testCreateWithParentPropertyMetadataFactoryYaml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';

        $decorated = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $decorated
            ->create(FileConfigDummy::class, [])
            ->willReturn(new PropertyNameCollection(['id']))
            ->shouldBeCalled();

        $this->assertEquals(
            (new ExtractorPropertyNameCollectionFactory(new YamlExtractor([$configPath]), $decorated->reveal()))->create(FileConfigDummy::class),
            new PropertyNameCollection(['id', 'foo', 'name'])
        );
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\ResourceClassNotFoundException
     * @expectedExceptionMessage The resource class "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist" does not exist.
     */
    public function testCreateWithNonexistentResourceYaml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcenotfound.yml';

        (new ExtractorPropertyNameCollectionFactory(new YamlExtractor([$configPath])))->create(\ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist::class);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /"resources" setting is expected to be null or an array, string given in ".+\/\.\.\/\.\.\/\.\.\/Fixtures\/FileConfigurations\/resourcesinvalid\.yml"\./
     */
    public function testCreateWithMalformedResourcesSettingYaml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesinvalid.yml';

        (new ExtractorPropertyNameCollectionFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /"properties" setting is expected to be null or an array, string given in ".+\/\.\.\/\.\.\/\.\.\/Fixtures\/FileConfigurations\/propertiesinvalid\.yml"\./
     */
    public function testCreateWithMalformedPropertiesSettingYaml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/propertiesinvalid.yml';

        (new ExtractorPropertyNameCollectionFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /"foo" setting is expected to be null or an array, string given in ".+\/\.\.\/\.\.\/\.\.\/Fixtures\/FileConfigurations\/propertyinvalid\.yml"\./
     */
    public function testCreateWithMalformedPropertySettingYaml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/propertyinvalid.yml';

        (new ExtractorPropertyNameCollectionFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     */
    public function testCreateWithMalformedYaml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/parse_exception.yml';

        (new ExtractorPropertyNameCollectionFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class);
    }
}
