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

namespace ApiPlatform\Hydra\Tests\Serializer;

use ApiPlatform\Documentation\Entrypoint;
use ApiPlatform\Hydra\Serializer\EntrypointNormalizer;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FooDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class EntrypointNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testSupportNormalization(): void
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
        $this->assertEmpty($normalizer->getSupportedTypes('json'));
        $this->assertSame([Entrypoint::class => true], $normalizer->getSupportedTypes($normalizer::FORMAT));
    }

    public function testNormalizeWithResourceMetadata(): void
    {
        $collection = new ResourceNameCollection([FooDummy::class, Dummy::class]);
        $entrypoint = new Entrypoint($collection);

        $factoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $factoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withShortName('Dummy')->withUriTemplate('/api/dummies')->withOperations(new Operations([
                'get' => new GetCollection(),
            ])),
        ]))->shouldBeCalled();
        $factoryProphecy->create(FooDummy::class)->willReturn(new ResourceMetadataCollection(FooDummy::class, [
            (new ApiResource())->withShortName('FooDummy')->withUriTemplate('/api/foo_dummies')->withOperations(new Operations([
                'get' => new GetCollection(),
            ])),
        ]))->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, Argument::any())->willReturn('/api/dummies')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource(FooDummy::class, UrlGeneratorInterface::ABS_PATH, Argument::any())->willReturn('/api/foo_dummies')->shouldBeCalled();

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
        $this->assertSame($expected, $normalizer->normalize($entrypoint, EntrypointNormalizer::FORMAT));
    }

    public function testNormalizeWithResourceCollection(): void
    {
        $collection = new ResourceNameCollection([FooDummy::class, Dummy::class]);
        $entrypoint = new Entrypoint($collection);

        $factoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $factoryProphecy->create(Dummy::class)->willReturn(
            new ResourceMetadataCollection(Dummy::class, [
                (new ApiResource())->withUriTemplate('Dummy')->withShortName('dummy')->withOperations(new Operations(['get' => (new GetCollection())])),
            ])
        )->shouldBeCalled();

        $factoryProphecy->create(FooDummy::class)->willReturn(
            new ResourceMetadataCollection(FooDummy::class, [
                (new ApiResource())->withUriTemplate('FooDummy')->withShortName('fooDummy')->withOperations(new Operations(['get' => (new GetCollection())])),
            ])
        )->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, Argument::any())->willReturn('/api/dummies')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource(FooDummy::class, UrlGeneratorInterface::ABS_PATH, Argument::any())->willReturn('/api/foo_dummies')->shouldBeCalled();

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
        $this->assertSame($expected, $normalizer->normalize($entrypoint, EntrypointNormalizer::FORMAT));
    }
}
