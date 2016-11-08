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
use ApiPlatform\Core\Metadata\Property\Factory\YamlPropertyNameCollectionFactory;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\YamlExtractor;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class YamlPropertyNameCollectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';

        $this->assertEquals(
            (new YamlPropertyNameCollectionFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class),
            new PropertyNameCollection(['foo', 'name'])
        );
    }

    public function testCreateWithParentPropertyMetadataFactory()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';

        $decorated = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $decorated
            ->create(FileConfigDummy::class, [])
            ->willReturn(new PropertyNameCollection(['id']))
            ->shouldBeCalled();

        $this->assertEquals(
            (new YamlPropertyNameCollectionFactory(new YamlExtractor([$configPath]), $decorated->reveal()))->create(FileConfigDummy::class),
            new PropertyNameCollection(['id', 'foo', 'name'])
        );
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\ResourceClassNotFoundException
     * @expectedExceptionMessage The resource class "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist" does not exist.
     */
    public function testCreateWithNonexistentResource()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcenotfound.yml';

        (new YamlPropertyNameCollectionFactory(new YamlExtractor([$configPath])))->create(\ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist::class);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /"resources" setting is expected to be null or an array, string given in ".+\/\.\.\/\.\.\/\.\.\/Fixtures\/FileConfigurations\/resourcesinvalid\.yml"\./
     */
    public function testCreateWithMalformedResourcesSetting()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesinvalid.yml';

        (new YamlPropertyNameCollectionFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /"properties" setting is expected to be null or an array, string given in ".+\/\.\.\/\.\.\/\.\.\/Fixtures\/FileConfigurations\/propertiesinvalid\.yml"\./
     */
    public function testCreateWithMalformedPropertiesSetting()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/propertiesinvalid.yml';

        (new YamlPropertyNameCollectionFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /"foo" setting is expected to be null or an array, string given in ".+\/\.\.\/\.\.\/\.\.\/Fixtures\/FileConfigurations\/propertyinvalid\.yml"\./
     */
    public function testCreateWithMalformedPropertySetting()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/propertyinvalid.yml';

        (new YamlPropertyNameCollectionFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class);
    }

    /**
     * @expectedException \Symfony\Component\Yaml\Exception\ParseException
     */
    public function testCreateWithMalformedYaml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/parse_exception.yml';

        (new YamlPropertyNameCollectionFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class);
    }
}
