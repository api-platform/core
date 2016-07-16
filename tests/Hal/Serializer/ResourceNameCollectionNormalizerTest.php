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
use ApiPlatform\Core\Hal\Serializer\ResourceNameCollectionNormalizer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourceNameCollectionNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testSupportNormalization()
    {
        $collection = new ResourceNameCollection();

        $factoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);

        $normalizer = new ResourceNameCollectionNormalizer($factoryProphecy->reveal(), $iriConverterProphecy->reveal(), $urlGeneratorProphecy->reveal());

        $this->assertTrue($normalizer->supportsNormalization($collection, ResourceNameCollectionNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization($collection, 'json'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), ResourceNameCollectionNormalizer::FORMAT));
    }

    public function testNormalize()
    {
        $collection = new ResourceNameCollection([Dummy::class]);

        $factoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('Dummy', null, null, null, ['get']))->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResourceClass(Dummy::class)->willReturn('/api/dummies')->shouldBeCalled();

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint')->willReturn('/api')->shouldBeCalled();

        $normalizer = new ResourceNameCollectionNormalizer($factoryProphecy->reveal(), $iriConverterProphecy->reveal(), $urlGeneratorProphecy->reveal());

        $expected = [
            '_links' => [
                'self' => [
                    'href' => '/api',
                ],
                'dummy' => [
                    'href' => '/api/dummies',
                ],
            ],
        ];
        $this->assertEquals($expected, $normalizer->normalize($collection, ResourceNameCollectionNormalizer::FORMAT));
    }
}
