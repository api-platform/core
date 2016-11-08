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

use ApiPlatform\Core\Metadata\Resource\Factory\YamlResourceNameCollectionFactory;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Metadata\YamlExtractor;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;

/**
 * Tests yaml resource name collection factory.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class YamlResourceNameCollectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testYamlResourceName()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';
        $yamlResourceNameCollectionFactory = new YamlResourceNameCollectionFactory(new YamlExtractor([$configPath]));

        $this->assertEquals($yamlResourceNameCollectionFactory->create(), new ResourceNameCollection([
            Dummy::class,
            FileConfigDummy::class,
        ]));
    }

    public function testYamlSingleResourceName()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/single_resource.yml';
        $yamlResourceNameCollectionFactory = new YamlResourceNameCollectionFactory(new YamlExtractor([$configPath]));

        $this->assertEquals($yamlResourceNameCollectionFactory->create(), new ResourceNameCollection([FileConfigDummy::class]));
    }

    /**
     * @expectedException \Symfony\Component\Yaml\Exception\ParseException
     */
    public function testCreateWithMalformedYaml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/parse_exception.yml';

        (new YamlResourceNameCollectionFactory(new YamlExtractor([$configPath])))->create();
    }
}
