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

namespace ApiPlatform\Core\Tests\Metadata\Resource\Factory;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Extractor\XmlExtractor;
use ApiPlatform\Core\Metadata\Extractor\YamlExtractor;
use ApiPlatform\Core\Metadata\Resource\Factory\ExtractorResourceNameCollectionFactory;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;
use PHPUnit\Framework\TestCase;

/**
 * Tests extractor resource name collection factory.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class ExtractorResourceNameCollectionFactoryTest extends TestCase
{
    public function testXmlResourceName()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';
        $factory = new ExtractorResourceNameCollectionFactory(new XmlExtractor([$configPath]));

        $this->assertEquals($factory->create(), new ResourceNameCollection([
            Dummy::class,
            FileConfigDummy::class,
        ]));
    }

    public function testInvalidExtractorResourceNameCollectionFactory()
    {
        $this->expectException(InvalidArgumentException::class);

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesinvalid.xml';
        $factory = new ExtractorResourceNameCollectionFactory(new XmlExtractor([$configPath]));
        $factory->create();
    }

    public function testYamlResourceName()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';
        $factory = new ExtractorResourceNameCollectionFactory(new YamlExtractor([$configPath]));

        $this->assertEquals($factory->create(), new ResourceNameCollection([
            Dummy::class,
            FileConfigDummy::class,
        ]));
    }

    public function testYamlSingleResourceName()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/single_resource.yml';
        $factory = new ExtractorResourceNameCollectionFactory(new YamlExtractor([$configPath]));

        $this->assertEquals($factory->create(), new ResourceNameCollection([FileConfigDummy::class]));
    }

    public function testCreateWithMalformedYaml()
    {
        $this->expectException(InvalidArgumentException::class);

        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/parse_exception.yml';

        (new ExtractorResourceNameCollectionFactory(new YamlExtractor([$configPath])))->create();
    }
}
