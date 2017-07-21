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

namespace ApiPlatform\Core\Tests\JsonLd\Serializer;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\JsonLd\ContextBuilderInterface;
use ApiPlatform\Core\JsonLd\Serializer\ItemNormalizer;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Prophecy\Argument;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ItemNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testDontSupportDenormalization()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);
        $resourceClassResolverProphecy->getResourceClass(['dummy'], 'Dummy')->willReturn(Dummy::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['name' => 'name']));
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn(new PropertyMetadata());

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);

        $normalizer = new ItemNormalizer($resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $iriConverterProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $contextBuilderProphecy->reveal(), null, null, null, $identifiersExtractorProphecy->reveal());

        $this->assertFalse($normalizer->supportsDenormalization('foo', ItemNormalizer::FORMAT));
        $normalizer->denormalize(['foo'], Dummy::class, 'jsonld', ['jsonld_has_context' => true, 'jsonld_sub_level' => true, 'resource_class' => Dummy::class]);
    }

    public function testSupportNormalization()
    {
        $std = new \stdClass();
        $dummy = new Dummy();
        $dummy->setDescription('hello');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass($std)->willThrow(new InvalidArgumentException());

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);

        $normalizer = new ItemNormalizer($resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $iriConverterProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $contextBuilderProphecy->reveal(), null, null, null, $identifiersExtractorProphecy->reveal());

        $this->assertTrue($normalizer->supportsNormalization($dummy, 'jsonld'));
        $this->assertFalse($normalizer->supportsNormalization($dummy, 'xml'));
        $this->assertFalse($normalizer->supportsNormalization($std, 'jsonld'));
    }

    public function testNormalize()
    {
        $dummy = new Dummy();
        $dummy->setName('hello');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('Dummy'));
        $propertyNameCollection = new PropertyNameCollection(['name']);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn($propertyNameCollection);

        $propertyMetadataProphecy = new PropertyMetadata(null, null, true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'name', [])->willReturn($propertyMetadataProphecy);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummies/1988');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, null, true)->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass($dummy, Dummy::class, true)->willReturn(Dummy::class);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->willImplement(NormalizerInterface::class);
        $serializerProphecy->normalize('hello', null, Argument::type('array'))->willReturn('hello');
        $contextBuilderProphecy = $this->prophesize(ContextBuilderInterface::class);
        $contextBuilderProphecy->getResourceContextUri(Dummy::class)->willReturn('/contexts/Dummy');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromItem($dummy)->willReturn(['id']);

        $normalizer = new ItemNormalizer($resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $iriConverterProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $contextBuilderProphecy->reveal(), null, null, null, $identifiersExtractorProphecy->reveal());
        $normalizer->setSerializer($serializerProphecy->reveal());

        $expected = ['@context' => '/contexts/Dummy',
                     '@id' => '/dummies/1988',
                     '@type' => 'Dummy',
                     'name' => 'hello',
        ];
        $this->assertEquals($expected, $normalizer->normalize($dummy));
    }
}
