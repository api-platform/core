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
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
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
}
