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

namespace ApiPlatform\Metadata\Tests\Property\Factory;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Extractor\XmlPropertyExtractor;
use ApiPlatform\Metadata\Extractor\YamlPropertyExtractor;
use ApiPlatform\Metadata\Property\Factory\ExtractorPropertyNameCollectionFactory;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\FileConfigDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ExtractorPropertyNameCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateXml(): void
    {
        $configPath = __DIR__.'/../../Fixtures/FileConfigurations/properties.xml';

        $this->assertEquals(
            (new ExtractorPropertyNameCollectionFactory(new XmlPropertyExtractor([$configPath])))->create(FileConfigDummy::class),
            new PropertyNameCollection(['foo', 'name'])
        );
    }

    public function testCreateWithParentPropertyNameCollectionFactoryXml(): void
    {
        $configPath = __DIR__.'/../../Fixtures/FileConfigurations/properties.xml';

        $decorated = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $decorated
            ->create(FileConfigDummy::class, [])
            ->willReturn(new PropertyNameCollection(['id']))
            ->shouldBeCalled();

        $this->assertEquals(
            (new ExtractorPropertyNameCollectionFactory(new XmlPropertyExtractor([$configPath]), $decorated->reveal()))->create(FileConfigDummy::class),
            new PropertyNameCollection(['id', 'foo', 'name'])
        );
    }

    public function testCreateWithNonexistentResourceXml(): void
    {
        $this->expectException(ResourceClassNotFoundException::class);
        $this->expectExceptionMessage('The resource class "ApiPlatform\\Metadata\\Tests\\Fixtures\\ApiResource\\ThisDoesNotExist" does not exist.');

        $configPath = __DIR__.'/../../Fixtures/FileConfigurations/resourcenotfound.xml';

        (new ExtractorPropertyNameCollectionFactory(new XmlPropertyExtractor([$configPath])))->create('ApiPlatform\Metadata\Tests\Fixtures\ApiResource\ThisDoesNotExist');
    }

    public function testCreateWithInvalidXml(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('#.+Element \'\\{https://api-platform.com/schema/metadata/properties-3.0\\}foo\': This element is not expected\\..+#');

        $configPath = __DIR__.'/../../Fixtures/FileConfigurations/propertyinvalid.xml';

        (new ExtractorPropertyNameCollectionFactory(new XmlPropertyExtractor([$configPath])))->create(FileConfigDummy::class);
    }

    public function testCreateYaml(): void
    {
        $configPath = __DIR__.'/../../Fixtures/FileConfigurations/properties.yml';

        $this->assertEquals(
            (new ExtractorPropertyNameCollectionFactory(new YamlPropertyExtractor([$configPath])))->create(FileConfigDummy::class),
            new PropertyNameCollection(['foo', 'name'])
        );
    }

    public function testCreateWithParentPropertyMetadataFactoryYaml(): void
    {
        $configPath = __DIR__.'/../../Fixtures/FileConfigurations/properties.yml';

        $decorated = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $decorated
            ->create(FileConfigDummy::class, [])
            ->willReturn(new PropertyNameCollection(['id']))
            ->shouldBeCalled();

        $this->assertEquals(
            (new ExtractorPropertyNameCollectionFactory(new YamlPropertyExtractor([$configPath]), $decorated->reveal()))->create(FileConfigDummy::class),
            new PropertyNameCollection(['id', 'foo', 'name'])
        );
    }

    public function testCreateWithNonexistentResourceYaml(): void
    {
        $this->expectException(ResourceClassNotFoundException::class);
        $this->expectExceptionMessage('The resource class "ApiPlatform\\Metadata\\Tests\\Fixtures\\ApiResource\\ThisDoesNotExist" does not exist.');

        $configPath = __DIR__.'/../../Fixtures/FileConfigurations/resourcenotfound.yml';

        (new ExtractorPropertyNameCollectionFactory(new YamlPropertyExtractor([$configPath])))->create('ApiPlatform\Metadata\Tests\Fixtures\ApiResource\ThisDoesNotExist');
    }

    public function testCreateWithMalformedResourcesSettingYaml(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/"properties" setting is expected to be null or an array, string given in ".+\\/\\.\\.\\/\\.\\.\\/Fixtures\\/FileConfigurations\\/propertiesinvalid\\.yml"\\./');

        $configPath = __DIR__.'/../../Fixtures/FileConfigurations/propertiesinvalid.yml';

        (new ExtractorPropertyNameCollectionFactory(new YamlPropertyExtractor([$configPath])))->create(FileConfigDummy::class);
    }

    public function testCreateWithMalformedPropertySettingYaml(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"foo" setting is expected to be null or an array, string given.');

        $configPath = __DIR__.'/../../Fixtures/FileConfigurations/propertyinvalid.yml';

        (new ExtractorPropertyNameCollectionFactory(new YamlPropertyExtractor([$configPath])))->create(FileConfigDummy::class);
    }

    public function testCreateWithMalformedYaml(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $configPath = __DIR__.'/../../Fixtures/FileConfigurations/parse_exception.yml';

        (new ExtractorPropertyNameCollectionFactory(new YamlPropertyExtractor([$configPath])))->create(FileConfigDummy::class);
    }
}
