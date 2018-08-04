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
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Extractor\XmlExtractor;
use ApiPlatform\Core\Metadata\Extractor\YamlExtractor;
use ApiPlatform\Core\Metadata\Property\Factory\ExtractorPropertyNameCollectionFactory;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;
use PHPUnit\Framework\TestCase;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ExtractorPropertyNameCollectionFactoryTest extends TestCase
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

    public function testCreateWithNonexistentResourceXml()
    {
        $this->expectException(ResourceClassNotFoundException::class);
        $this->expectExceptionMessage('The resource class "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\ThisDoesNotExist" does not exist.');

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcenotfound.xml';

        (new ExtractorPropertyNameCollectionFactory(new XmlExtractor([$configPath])))->create('ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist');
    }

    public function testCreateWithInvalidXml()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('#.+Element \'\\{https://api-platform.com/schema/metadata\\}foo\': This element is not expected\\..+#');

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

    public function testCreateWithNonexistentResourceYaml()
    {
        $this->expectException(ResourceClassNotFoundException::class);
        $this->expectExceptionMessage('The resource class "ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\ThisDoesNotExist" does not exist.');

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcenotfound.yml';

        (new ExtractorPropertyNameCollectionFactory(new YamlExtractor([$configPath])))->create('ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThisDoesNotExist');
    }

    public function testCreateWithMalformedResourcesSettingYaml()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/"resources" setting is expected to be null or an array, string given in ".+\\/\\.\\.\\/\\.\\.\\/\\.\\.\\/Fixtures\\/FileConfigurations\\/resourcesinvalid\\.yml"\\./');

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesinvalid.yml';

        (new ExtractorPropertyNameCollectionFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class);
    }

    public function testCreateWithMalformedPropertiesSettingYaml()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/"properties" setting is expected to be null or an array, string given in ".+\\/\\.\\.\\/\\.\\.\\/\\.\\.\\/Fixtures\\/FileConfigurations\\/propertiesinvalid\\.yml"\\./');

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/propertiesinvalid.yml';

        (new ExtractorPropertyNameCollectionFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class);
    }

    public function testCreateWithMalformedPropertySettingYaml()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/"foo" setting is expected to be null or an array, string given in ".+\\/\\.\\.\\/\\.\\.\\/\\.\\.\\/Fixtures\\/FileConfigurations\\/propertyinvalid\\.yml"\\./');

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/propertyinvalid.yml';

        (new ExtractorPropertyNameCollectionFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class);
    }

    public function testCreateWithMalformedYaml()
    {
        $this->expectException(InvalidArgumentException::class);

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/parse_exception.yml';

        (new ExtractorPropertyNameCollectionFactory(new YamlExtractor([$configPath])))->create(FileConfigDummy::class);
    }
}
