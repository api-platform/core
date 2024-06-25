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

namespace ApiPlatform\JsonApi\Tests\Serializer;

use ApiPlatform\Documentation\Entrypoint;
use ApiPlatform\JsonApi\Serializer\EntrypointNormalizer;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\Serializer;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
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

        if (!method_exists(Serializer::class, 'getSupportedTypes')) {
            $this->assertTrue($normalizer->hasCacheableSupportsMethod());
        }
    }

    public function testNormalize(): void
    {
        $collection = new ResourceNameCollection([Dummy::class, RelatedDummy::class, DummyCar::class]);
        $entrypoint = new Entrypoint($collection);
        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection('Dummy', [
            (new ApiResource())->withShortName('Dummy')->withOperations(new Operations([
                'get' => (new GetCollection())->withShortName('Dummy'),
            ])),
        ]))->shouldBeCalled();
        $resourceMetadataCollectionFactoryProphecy->create(RelatedDummy::class)->willReturn(new ResourceMetadataCollection('RelatedDummy', [
            (new ApiResource())->withShortName('RelatedDummy')->withOperations(new Operations([
                'get' => (new Get())->withShortName('RelatedDummy'),
            ])),
        ]))->shouldBeCalled();
        $resourceMetadataCollectionFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadataCollection('DummyCar', [
            (new ApiResource())->withShortName('DummyCar')->withOperations(new Operations([
                'post' => (new Post())->withShortName('DummyCar'),
            ])),
        ]))->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_URL, Argument::type(Operation::class))->willReturn('http://localhost/api/dummies')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource(RelatedDummy::class, UrlGeneratorInterface::ABS_URL, Argument::type(Operation::class))->shouldNotBeCalled();
        $iriConverterProphecy->getIriFromResource(DummyCar::class, UrlGeneratorInterface::ABS_URL, Argument::type(Operation::class))->shouldNotBeCalled();

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint', [], UrlGeneratorInterface::ABS_URL)->willReturn('http://localhost/api')->shouldBeCalled();

        $this->assertEquals(
            [
                'links' => [
                    'self' => 'http://localhost/api',
                    'dummy' => 'http://localhost/api/dummies',
                ],
            ],
            (new EntrypointNormalizer($resourceMetadataCollectionFactoryProphecy->reveal(), $iriConverterProphecy->reveal(), $urlGeneratorProphecy->reveal()))->normalize($entrypoint, EntrypointNormalizer::FORMAT)
        );
    }
}
