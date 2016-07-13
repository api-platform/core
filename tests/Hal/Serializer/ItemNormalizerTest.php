<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\tests\Hal;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Hal\Serializer\ItemNormalizer;
use ApiPlatform\Core\Hypermedia\ContextBuilderInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class ItemNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ItemNormalizer
     */
    private $itemNormalizer;
    private $hal;

    public function setUp()
    {
        $dummy1 = new Dummy();
        $dummy1->setName('dummy1');

        $this->hal = $dummy1;

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($this->hal)->willReturn('dummy');
        $iriConverter = $this->prophesize(IriConverterInterface::class);
        $iriConverter->getIriFromResourceClass('dummy')->willReturn('/dummies');
        $propertyAccess = $this->prophesize(PropertyAccessorInterface::class);
        $nameConverter = $this->prophesize(NameConverterInterface::class);
        $this->itemNormalizer = new ItemNormalizer($resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $iriConverter->reveal(), $resourceClassResolverProphecy->reveal(), $contextBuilderProphecy->reveal(), $propertyAccess->reveal(), $nameConverter->reveal(), ['jsonhal' => ['mime_types' => ['application/hal+json']]]);
    }

    public function testSupportsNormalization()
    {
        $this->assertEquals(true, $this->itemNormalizer->supportsNormalization($this->hal, 'jsonhal'));
    }

    public function testNormalize()
    {
        $this->assertEquals([], $this->itemNormalizer->normalize($this->hal, 'jsonhal', []));
    }
}
