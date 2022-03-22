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

namespace ApiPlatform\Tests\JsonApi\Serializer;

use ApiPlatform\Api\Entrypoint;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\JsonApi\Serializer\EntrypointNormalizer;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use PHPUnit\Framework\TestCase;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @group legacy
 */
class EntrypointNormalizerTest extends TestCase
{
    use ProphecyTrait;

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
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testNormalize()
    {
        $collection = new ResourceNameCollection([Dummy::class, RelatedDummy::class, DummyCar::class]);
        $entrypoint = new Entrypoint($collection);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('Dummy', null, null, null, ['get']))->shouldBeCalled();
        $resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn(new ResourceMetadata('RelatedDummy', null, null, ['get'], null))->shouldBeCalled();
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata('DummyCar', null, null, null, ['post']))->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResourceClass(Dummy::class, UrlGeneratorInterface::ABS_URL)->willReturn('http://localhost/api/dummies')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResourceClass(RelatedDummy::class, UrlGeneratorInterface::ABS_URL)->shouldNotBeCalled();
        $iriConverterProphecy->getIriFromResourceClass(DummyCar::class, UrlGeneratorInterface::ABS_URL)->willThrow(new InvalidArgumentException())->shouldBeCalled();

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint', [], UrlGeneratorInterface::ABS_URL)->willReturn('http://localhost/api')->shouldBeCalled();

        $this->assertEquals(
            [
                'links' => [
                    'self' => 'http://localhost/api',
                    'dummy' => 'http://localhost/api/dummies',
                ],
            ],
            (new EntrypointNormalizer($resourceMetadataFactoryProphecy->reveal(), $iriConverterProphecy->reveal(), $urlGeneratorProphecy->reveal()))->normalize($entrypoint, EntrypointNormalizer::FORMAT)
        );
    }
}
