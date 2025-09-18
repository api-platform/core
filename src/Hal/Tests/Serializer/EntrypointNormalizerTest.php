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

use ApiPlatform\Documentation\Entrypoint;
use ApiPlatform\Hal\Serializer\EntrypointNormalizer;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class EntrypointNormalizerTest extends TestCase
{
    public function testSupportNormalization(): void
    {
        $collection = new ResourceNameCollection();
        $entrypoint = new Entrypoint($collection);

        $factoryMock = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $iriConverterMock = $this->createMock(IriConverterInterface::class);
        $urlGeneratorMock = $this->createMock(UrlGeneratorInterface::class);

        $normalizer = new EntrypointNormalizer($factoryMock, $iriConverterMock, $urlGeneratorMock);

        $this->assertTrue($normalizer->supportsNormalization($entrypoint, EntrypointNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization($entrypoint, 'json'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), EntrypointNormalizer::FORMAT));

        $this->assertEmpty($normalizer->getSupportedTypes('json'));
        $this->assertSame([Entrypoint::class => true], $normalizer->getSupportedTypes($normalizer::FORMAT));
    }

    public function testNormalize(): void
    {
        $collection = new ResourceNameCollection([Dummy::class]);
        $entrypoint = new Entrypoint($collection);
        $factoryMock = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $operation = (new GetCollection())->withShortName('Dummy')->withClass(Dummy::class);
        $factoryMock->expects($this->once())->method('create')->with(Dummy::class)->willReturn(new ResourceMetadataCollection('Dummy', [
            (new ApiResource('Dummy'))
                ->withShortName('Dummy')
                ->withOperations(new Operations([
                    'get' => $operation,
                ])),
        ]));

        $iriConverterMock = $this->createMock(IriConverterInterface::class);
        $iriConverterMock->expects($this->once())->method('getIriFromResource')->with(Dummy::class, UrlGeneratorInterface::ABS_PATH, $operation)->willReturn('/api/dummies');

        $urlGeneratorMock = $this->createMock(UrlGeneratorInterface::class);
        $urlGeneratorMock->expects($this->once())->method('generate')->with('api_entrypoint')->willReturn('/api');

        $normalizer = new EntrypointNormalizer($factoryMock, $iriConverterMock, $urlGeneratorMock);

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
        $this->assertSame($expected, $normalizer->normalize($entrypoint, EntrypointNormalizer::FORMAT));
    }
}
