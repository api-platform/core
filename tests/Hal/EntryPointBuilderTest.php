<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Hal\Serializer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Hal\EntrypointBuilder;
use ApiPlatform\Core\Hypermedia\ContextBuilderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class EntryPointBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntrypointBuilder
     */
    private $entrypointBuilder;

    public function setUp()
    {
        $dummyMetadata = new ResourceMetadata('dummy', 'dummy', '#dummy', ['get' => ['method' => 'GET'], 'put' => ['method' => 'PUT']], ['get' => ['method' => 'GET'], 'post' => ['method' => 'POST']], []);
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $nameConverter = $this->prophesize(NameConverterInterface::class);
        $urlGeneratorProphecy->generate('api_hal_entrypoint')->willReturn('/');
        $contextBuilder = $this->prophesize(ContextBuilderInterface::class);
        $contextBuilder->getBaseContext(1)->willReturn([
            '_links' => ['self' => ['href' => '/'],
                         'curies' => [
                             ['name' => 'ap',
                              'href' => '/doc#section-{rel}',
                              'templated' => true,
                             ],
                         ],
            ],
        ]);

        $iriConverter = $this->prophesize(IriConverterInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['dummy' => 'dummy']))->shouldBeCalled();
        $resourceMetadataFactoryProphecy->create('dummy')->shouldBeCalled()->willReturn($dummyMetadata);
        $iriConverter->getIriFromResourceClass('dummy')->shouldBeCalled()->willReturn('/dummies');



        $this->entrypointBuilder = new EntrypointBuilder($resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), $iriConverter->reveal(), $urlGeneratorProphecy->reveal(), $contextBuilder->reveal());
    }

    public function testGetEntrypoint()
    {
        $this->assertEquals(
            [
                '_links' => ['self' => ['href' => '/'],
                'curies' => [
                                 ['name' => 'ap',
                                  'href' => '/doc#section-{rel}',
                                  'templated' => true,
                                 ],
                             ],
                 'ap:dummy' => ['href' => '/dummies'],
                ],
            ],
            $this->entrypointBuilder->getEntrypoint());
    }
}
