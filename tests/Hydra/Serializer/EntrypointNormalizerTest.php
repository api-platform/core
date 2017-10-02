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

namespace ApiPlatform\Core\Tests\Hydra\Serializer;

use ApiPlatform\Core\Api\Entrypoint;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Hydra\Serializer\EntrypointNormalizer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class EntrypointNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testSupportNormalization()
    {
        $collection = new ResourceNameCollection();
        $entrypoint = new Entrypoint($collection);

        $factoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);

        $normalizer = new EntrypointNormalizer($factoryProphecy->reveal(), $iriConverterProphecy->reveal(), $urlGeneratorProphecy->reveal());

        $this->assertTrue($normalizer->supportsNormalization($entrypoint, EntrypointNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization($entrypoint, 'json'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), EntrypointNormalizer::FORMAT));
    }

    public function testNormalize()
    {
        $collection = new ResourceNameCollection([Dummy::class]);
        $entrypoint = new Entrypoint($collection);

        $factoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('Dummy', null, null, null, ['get']))->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResourceClass(Dummy::class)->willReturn('/api/dummies')->shouldBeCalled();

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint')->willReturn('/api')->shouldBeCalled();
        $urlGeneratorProphecy->generate('api_jsonld_context', ['shortName' => 'Entrypoint'])->willReturn('/context/Entrypoint')->shouldBeCalled();

        $normalizer = new EntrypointNormalizer($factoryProphecy->reveal(), $iriConverterProphecy->reveal(), $urlGeneratorProphecy->reveal());

        $expected = [
            '@context' => '/context/Entrypoint',
            '@id' => '/api',
            '@type' => 'Entrypoint',
            'dummy' => '/api/dummies',
        ];
        $this->assertEquals($expected, $normalizer->normalize($entrypoint, EntrypointNormalizer::FORMAT));
    }
}
