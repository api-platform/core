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

namespace ApiPlatform\Tests\Hydra\Serializer;

use ApiPlatform\Api\Entrypoint;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Hydra\Serializer\EntrypointNormalizer;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FooDummy;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @group legacy
 */
class EntrypointNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testSupportNormalization()
    {
        $collection = new ResourceNameCollection();
        $entrypoint = new Entrypoint($collection);

        $factoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);

        $normalizer = new EntrypointNormalizer($factoryProphecy->reveal(), $iriConverterProphecy->reveal(), $urlGeneratorProphecy->reveal());

        $this->assertTrue($normalizer->supportsNormalization($entrypoint, EntrypointNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization($entrypoint, 'json'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), EntrypointNormalizer::FORMAT));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testNormalizeWithResourceMetadata()
    {
        $collection = new ResourceNameCollection([FooDummy::class, Dummy::class]);
        $entrypoint = new Entrypoint($collection);

        $factoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $factoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('Dummy', null, null, null, ['get']))->shouldBeCalled();
        $factoryProphecy->create(FooDummy::class)->willReturn(new ResourceMetadata('FooDummy', null, null, null, ['get']))->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResourceClass(Dummy::class)->willReturn('/api/dummies')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResourceClass(FooDummy::class)->willReturn('/api/foo_dummies')->shouldBeCalled();

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint')->willReturn('/api')->shouldBeCalled();
        $urlGeneratorProphecy->generate('api_jsonld_context', ['shortName' => 'Entrypoint'])->willReturn('/context/Entrypoint')->shouldBeCalled();

        $normalizer = new EntrypointNormalizer($factoryProphecy->reveal(), $iriConverterProphecy->reveal(), $urlGeneratorProphecy->reveal());

        $expected = [
            '@context' => '/context/Entrypoint',
            '@id' => '/api',
            '@type' => 'Entrypoint',
            'dummy' => '/api/dummies',
            'fooDummy' => '/api/foo_dummies',
        ];
        $this->assertEquals($expected, $normalizer->normalize($entrypoint, EntrypointNormalizer::FORMAT));
    }

    public function testNormalizeWithResourceCollection()
    {
        $collection = new ResourceNameCollection([FooDummy::class, Dummy::class]);
        $entrypoint = new Entrypoint($collection);

        $factoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $factoryProphecy->create(Dummy::class)->willReturn(
            new ResourceMetadataCollection(Dummy::class, [
                (new ApiResource())->withUriTemplate('Dummy')->withShortName('dummy')->withOperations(new Operations(['get' => (new Get())->withCollection(true)])),
            ])
        )->shouldBeCalled();

        $factoryProphecy->create(FooDummy::class)->willReturn(
            new ResourceMetadataCollection(FooDummy::class, [
                (new ApiResource())->withUriTemplate('FooDummy')->withShortName('fooDummy')->withOperations(new Operations(['get' => (new Get())->withCollection(true)])),
            ])
        )->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResourceClass(Dummy::class)->willReturn('/api/dummies')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResourceClass(FooDummy::class)->willReturn('/api/foo_dummies')->shouldBeCalled();

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint')->willReturn('/api')->shouldBeCalled();
        $urlGeneratorProphecy->generate('api_jsonld_context', ['shortName' => 'Entrypoint'])->willReturn('/context/Entrypoint')->shouldBeCalled();

        $normalizer = new EntrypointNormalizer($factoryProphecy->reveal(), $iriConverterProphecy->reveal(), $urlGeneratorProphecy->reveal());

        $expected = [
            '@context' => '/context/Entrypoint',
            '@id' => '/api',
            '@type' => 'Entrypoint',
            'dummy' => '/api/dummies',
            'fooDummy' => '/api/foo_dummies',
        ];
        $this->assertEquals($expected, $normalizer->normalize($entrypoint, EntrypointNormalizer::FORMAT));
    }
}
