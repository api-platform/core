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

namespace ApiPlatform\Elasticsearch\Tests\Serializer;

use ApiPlatform\Elasticsearch\Serializer\ItemDenormalizer;
use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;

final class ItemDenormalizerTest extends TestCase
{
    private function createDenormalizer(): ItemDenormalizer
    {
        $propertyNameCollectionFactory = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->method('create')->willReturn(new PropertyNameCollection(['id', 'name', 'bar']));

        $propertyMetadataFactory = $this->createStub(PropertyMetadataFactoryInterface::class);

        $iriConverter = $this->createStub(IriConverterInterface::class);

        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturn(true);
        $resourceClassResolver->method('getResourceClass')->willReturnArgument(1);

        $resourceMetadataCollectionFactory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->method('create')->willReturn(
            new ResourceMetadataCollection(Foo::class, [(new ApiResource())->withOperations(new Operations([new Get()]))])
        );

        return new ItemDenormalizer(
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $iriConverter,
            $resourceClassResolver,
            null,
            null,
            null,
            [],
            $resourceMetadataCollectionFactory,
        );
    }

    public function testConstruct(): void
    {
        $denormalizer = $this->createDenormalizer();

        self::assertInstanceOf(DenormalizerInterface::class, $denormalizer);
        self::assertInstanceOf(SerializerAwareInterface::class, $denormalizer);
    }

    public function testSupportsDenormalization(): void
    {
        $denormalizer = $this->createDenormalizer();

        $document = [
            '_index' => 'test',
            '_type' => '_doc',
            '_id' => '1',
            '_source' => [
                'id' => 1,
                'name' => 'Caroline',
                'bar' => 'Chaverot',
            ],
        ];

        self::assertTrue($denormalizer->supportsDenormalization($document, Foo::class, ItemDenormalizer::FORMAT));
        self::assertFalse($denormalizer->supportsDenormalization($document, Foo::class, 'json'));
    }

    public function testGetSupportedTypes(): void
    {
        $denormalizer = $this->createDenormalizer();

        self::assertSame(['object' => true], $denormalizer->getSupportedTypes(ItemDenormalizer::FORMAT));
        self::assertSame([], $denormalizer->getSupportedTypes('json'));
    }
}
