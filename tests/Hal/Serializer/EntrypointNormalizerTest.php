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

namespace ApiPlatform\Tests\Hal\Serializer;

use ApiPlatform\Api\Entrypoint;
use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Hal\Serializer\EntrypointNormalizer;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
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

    public function testNormalize()
    {
        $collection = new ResourceNameCollection([Dummy::class]);
        $entrypoint = new Entrypoint($collection);
        $factoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $operation = (new GetCollection())->withShortName('Dummy')->withClass(Dummy::class);
        $factoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection('Dummy', [
            (new ApiResource('Dummy'))
                ->withShortName('Dummy')
                ->withOperations(new Operations([
                    'get' => $operation,
                ])),
        ]))->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, $operation)->willReturn('/api/dummies')->shouldBeCalled();

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_entrypoint')->willReturn('/api')->shouldBeCalled();

        $normalizer = new EntrypointNormalizer($factoryProphecy->reveal(), $iriConverterProphecy->reveal(), $urlGeneratorProphecy->reveal());

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
        $this->assertEquals($expected, $normalizer->normalize($entrypoint, EntrypointNormalizer::FORMAT));
    }
}
