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

use ApiPlatform\JsonApi\Serializer\ItemDenormalizer;
use ApiPlatform\JsonApi\Serializer\ItemNormalizer;
use ApiPlatform\JsonApi\Tests\Fixtures\Dummy;
use ApiPlatform\JsonApi\Tests\Fixtures\RelatedDummy;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\TypeInfo\Type;

class ItemDenormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testSupportsDenormalizationOnlyForJsonApiFormat(): void
    {
        $dummy = new Dummy();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $denormalizer = new ItemDenormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal()
        );

        $this->assertFalse($denormalizer->supportsNormalization($dummy, ItemNormalizer::FORMAT));
        $this->assertTrue($denormalizer->supportsDenormalization($dummy, Dummy::class, ItemNormalizer::FORMAT));
        $this->assertFalse($denormalizer->supportsDenormalization($dummy, Dummy::class, 'jsonld'));
    }

    #[Group('legacy')]
    #[IgnoreDeprecations]
    public function testDenormalizeOnLegacyItemNormalizerIsDeprecated(): void
    {
        $this->expectUserDeprecationMessage('Since api-platform/core 4.4: Calling "denormalize()" on "ApiPlatform\JsonApi\Serializer\ItemNormalizer" is deprecated, use "ApiPlatform\JsonApi\Serializer\ItemDenormalizer" instead.');
        $this->expectException(NotNormalizableValueException::class);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

        $normalizer = new ItemNormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal()
        );

        $normalizer->denormalize(
            ['data' => ['id' => '/dummies/1']],
            Dummy::class,
            ItemNormalizer::FORMAT,
            ['api_allow_update' => false]
        );
    }

    public static function provideNonStringIds(): iterable
    {
        yield 'array resource id' => [['data' => ['id' => ['x'], 'type' => 'Dummy']], true];
        yield 'integer resource id with IRIs' => [['data' => ['id' => 1, 'type' => 'Dummy']], true];
        yield 'array resource id without IRIs' => [['data' => ['id' => ['x'], 'type' => 'Dummy']], false];
        yield 'integer relationship id with IRIs' => [['data' => ['type' => 'Dummy', 'relationships' => ['relatedDummy' => ['data' => ['id' => 1, 'type' => 'RelatedDummy']]]]], true];
        yield 'array relationship id' => [['data' => ['type' => 'Dummy', 'relationships' => ['relatedDummy' => ['data' => ['id' => ['x'], 'type' => 'RelatedDummy']]]]], false];
    }

    #[DataProvider('provideNonStringIds')]
    public function testDenormalizeRejectsNonStringId(array $data, bool $useIriAsId): void
    {
        $propertyNameCollectionFactory = $this->createStub(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->method('create')->willReturn(new PropertyNameCollection(['relatedDummy']));
        $propertyMetadataFactory = $this->createStub(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->method('create')->willReturn((new ApiProperty())->withNativeType(Type::object(RelatedDummy::class))->withWritable(true)->withReadableLink(false)->withWritableLink(false));
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $iriConverter->expects($this->never())->method('getResourceFromIri');
        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('getResourceClass')->willReturnArgument(1);
        $resourceClassResolver->method('isResourceClass')->willReturn(true);

        $denormalizer = new ItemDenormalizer($propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, useIriAsId: $useIriAsId);

        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessageMatches('/must be "string"/');

        $denormalizer->denormalize($data, Dummy::class, ItemDenormalizer::FORMAT, ['operation' => new Patch()]);
    }
}
