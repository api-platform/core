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

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Hal\ContextBuilder;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class ContextBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContextBuilder
     */
    private $contextBuilder;

    public function setUp()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $nameConverter = $this->prophesize(NameConverterInterface::class);
        $urlGeneratorProphecy->generate('api_hal_entrypoint')->willReturn('/');
        $this->contextBuilder = new ContextBuilder($resourceNameCollectionFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $urlGeneratorProphecy->reveal(), 'doc', $nameConverter->reveal());
    }

    public function testGetBaseContext()
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
                ],
            ],
            $this->contextBuilder->getBaseContext());
    }

    public function testGetEntrypointContext()
    {
        $this->assertEquals([], $this->contextBuilder->getEntrypointContext());
    }

    public function testGetResourceContext()
    {
        $this->assertEquals([], $this->contextBuilder->getEntrypointContext());
    }

    public function testGetResourceContextUri()
    {
        $this->assertEquals([], $this->contextBuilder->getEntrypointContext());
    }
}
