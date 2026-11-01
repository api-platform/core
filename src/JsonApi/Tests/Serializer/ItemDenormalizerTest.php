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
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

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

    public static function provideNonStringRelationshipIds(): iterable
    {
        yield 'integer' => [1, true];
        yield 'array' => [['x'], true];
        yield 'boolean' => [true, true];
        yield 'array in identifier mode' => [['x'], false];
    }

    #[DataProvider('provideNonStringRelationshipIds')]
    public function testDenormalizeRelationRejectsNonStringId(mixed $id, bool $useIriAsId): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getResourceFromIri(Argument::cetera())->shouldNotBeCalled();
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

        $denormalizer = new ItemDenormalizer(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            useIriAsId: $useIriAsId,
        );

        try {
            (new \ReflectionMethod($denormalizer, 'denormalizeRelation'))->invoke(
                $denormalizer,
                'relatedDummy',
                new ApiProperty(),
                RelatedDummy::class,
                ['type' => 'RelatedDummy', 'id' => $id],
                ItemDenormalizer::FORMAT,
                ['deserialization_path' => 'relatedDummy']
            );
            $this->fail('A NotNormalizableValueException should have been thrown.');
        } catch (NotNormalizableValueException $e) {
            $this->assertSame('relatedDummy', $e->getPath());
            $this->assertSame(['string'], $e->getExpectedTypes());
            $this->assertTrue($e->canUseMessageForUser());
        }
    }
}
