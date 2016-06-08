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

use ApiPlatform\Core\Metadata\Resource\Factory\XmlResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\XmlResourceNameCollectionFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\YamlResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\YamlResourceNameCollectionFactory;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\FileConfigDummy;

/**
 * Tests resource file configurations (Yaml and Xml).
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class FileConfigurationMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function resourceMetadataProvider()
    {
        $resourceMetadata = new ResourceMetadata();

        $metadata = [
            'shortName' => 'thedummyshortname',
            'description' => 'Dummy resource',
            'itemOperations' => [
                'my_op_name' => ['method' => 'GET'],
                'my_other_op_name' => ['method' => 'POST'],
            ],
            'collectionOperations' => [
                'my_collection_op' => ['method' => 'POST', 'path' => 'the/collection/path'],
            ],
            'iri' => 'someirischema',
            'attributes' => [
                'normalization_context' => [
                    'groups' => ['default'],
                ],
                'denormalization_context' => [
                    'groups' => ['default'],
                ],
                'hydra_context' => [
                    '@type' => 'hydra:Operation',
                    '@hydra:title' => 'File config Dummy',
                ],
            ],
        ];

        foreach (['shortName', 'description', 'itemOperations', 'collectionOperations', 'iri', 'attributes'] as $property) {
            $wither = 'with'.ucfirst($property);
            $resourceMetadata = $resourceMetadata->$wither($metadata[$property]);
        }

        return [[$resourceMetadata]];
    }

    public function optionalResourceMetadataProvider()
    {
        $resourceMetadata = new ResourceMetadata();

        $resourceMetadata = $resourceMetadata->withItemOperations([
            'my_op_name' => ['method' => 'POST'],
        ]);

        return [[$resourceMetadata]];
    }

    public function testYamlResourceName()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';
        $yamlResourceNameCollectionFactory = new YamlResourceNameCollectionFactory([$configPath]);

        $this->assertEquals($yamlResourceNameCollectionFactory->create(), new ResourceNameCollection([
            FileConfigDummy::class,
        ]));
    }

    public function testXmlResourceName()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';
        $xmlResourceNameCollectionFactory = new XmlResourceNameCollectionFactory([$configPath]);

        $this->assertEquals($xmlResourceNameCollectionFactory->create(), new ResourceNameCollection([
            FileConfigDummy::class,
        ]));
    }

    /**
     * @dataProvider resourceMetadataProvider
     */
    public function testYamlCreateResourceMetadata(ResourceMetadata $expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.yml';

        $resourceMetadataFactory = new YamlResourceMetadataFactory([$configPath]);
        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertInstanceOf(ResourceMetadata::class, $resourceMetadata);
        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @dataProvider resourceMetadataProvider
     */
    public function testXmlCreateResourceMetadata($expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resources.xml';

        $resourceMetadataFactory = new XmlResourceMetadataFactory([$configPath]);
        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertInstanceOf(ResourceMetadata::class, $resourceMetadata);
        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @expectedException ApiPlatform\Core\Exception\ResourceClassNotFoundException
     */
    public function testYamlDoesNotExistMetadataFactory()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcenotfound.yml';
        $yamlResourceNameCollectionFactory = new YamlResourceNameCollectionFactory([$configPath]);
        $resourceMetadataFactory = new YamlResourceMetadataFactory([$configPath]);

        foreach ($yamlResourceNameCollectionFactory->create() as $resourceName) {
            $resourceMetadata = $resourceMetadataFactory->create($resourceName);
        }
    }

    /**
     * @expectedException ApiPlatform\Core\Exception\ResourceClassNotFoundException
     */
    public function testXmlDoesNotExistMetadataFactory()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcenotfound.xml';
        $xmlResourceNameCollectionFactory = new XmlResourceNameCollectionFactory([$configPath]);
        $resourceMetadataFactory = new XmlResourceMetadataFactory([$configPath]);

        foreach ($xmlResourceNameCollectionFactory->create() as $resourceName) {
            $resourceMetadata = $resourceMetadataFactory->create($resourceName);
        }
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNotValidXml()
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesinvalid.xml';
        $xmlResourceNameCollectionFactory = new XmlResourceNameCollectionFactory([$configPath]);
        $resourceMetadataFactory = new XmlResourceMetadataFactory([$configPath]);

        foreach ($xmlResourceNameCollectionFactory->create() as $resourceName) {
            $resourceMetadata = $resourceMetadataFactory->create($resourceName);
        }
    }

    /**
     * @dataProvider optionalResourceMetadataProvider
     */
    public function testXmlOptionalResourceMetadata($expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesoptional.xml';

        $resourceMetadataFactory = new XmlResourceMetadataFactory([$configPath]);
        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertInstanceOf(ResourceMetadata::class, $resourceMetadata);

        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }

    /**
     * @dataProvider optionalResourceMetadataProvider
     */
    public function testYamlOptionalResourceMetadata($expectedResourceMetadata)
    {
        $configPath = __DIR__.'/../../../Fixtures/FileConfigurations/resourcesoptional.yml';

        $resourceMetadataFactory = new YamlResourceMetadataFactory([$configPath]);
        $resourceMetadata = $resourceMetadataFactory->create(FileConfigDummy::class);

        $this->assertInstanceOf(ResourceMetadata::class, $resourceMetadata);
        $this->assertEquals($expectedResourceMetadata, $resourceMetadata);
    }
}
