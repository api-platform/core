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

namespace ApiPlatform\Core\Tests\Serializer;

use ApiPlatform\Core\Api\ResourceClassResolver;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Serializer\MappedDataModelNormalizer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Resource\Dummy as DummyResource;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;

final class MappedDataModelNormalizerTest extends TestCase
{
    use ProphecyTrait;

    private $resourceNameCollectionFactoryProphecy;
    private $normalizer;

    protected function setUp(): void
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([DummyResource::class]));
        $resourceClassResolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy->reveal());
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(DummyResource::class, 'foo')->willReturn((new PropertyMetadata())->withAttributes(['virtual' => true]));
        $propertyMetadataFactoryProphecy->create(DummyResource::class, Argument::type('string'))->willReturn(new PropertyMetadata());

        $this->normalizer = new MappedDataModelNormalizer(
            null,
            null,
            null,
            null,
            null,
            null,
            [],
            $resourceClassResolver,
            $propertyMetadataFactoryProphecy->reveal()
        );
        $this->normalizer->setSerializer(new Serializer());
    }

    /**
     * @dataProvider provideSupportNormalizationCases
     */
    public function testSupportNormalization($data, array $context, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->normalizer->supportsNormalization($data, null, $context));
    }

    public function provideSupportNormalizationCases(): \Generator
    {
        yield 'null data' => [null, [], false];
        yield 'empty context' => [new DummyResource(), [], false];
        yield 'resource class' => [new DummyResource(), [MappedDataModelNormalizer::MAPPED_DATA_MODEL => true], true];
    }

    public function testSupportNormalizationNullResourceClassResolver(): void
    {
        self::assertFalse((new MappedDataModelNormalizer())->supportsNormalization(new DummyResource(), null, [MappedDataModelNormalizer::MAPPED_DATA_MODEL => true]));
    }

    public function testNormalize(): void
    {
        $object = new DummyResource();
        $object->setName('dummy');

        $result = $this->normalizer->normalize($object, null, [MappedDataModelNormalizer::MAPPED_DATA_MODEL => true, AbstractNormalizer::IGNORED_ATTRIBUTES => ['alias']]);

        self::assertSame('dummy', $result['name']);
        self::assertArrayNotHasKey('alias', $result);
        self::assertArrayNotHasKey('foo', $result);
        self::assertSame(DummyResource::class, $result[MappedDataModelNormalizer::ITEM_RESOURCE_CLASS_KEY]);
    }

    /**
     * @dataProvider provideSupportDenormalizationCases
     */
    public function testSupportDenormalization(string $type, array $context, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->normalizer->supportsDenormalization([], $type, null, $context));
    }

    public function provideSupportDenormalizationCases(): \Generator
    {
        yield 'not a class' => ['not a class', [], false];
        yield 'empty context' => [DummyResource::class, [], false];
        yield 'resource class' => [DummyResource::class, [MappedDataModelNormalizer::MAPPED_DATA_MODEL => true], true];
    }

    public function testSupportDenormalizationNullResourceClassResolver(): void
    {
        self::assertFalse((new MappedDataModelNormalizer())->supportsDenormalization([], DummyResource::class, null, [MappedDataModelNormalizer::MAPPED_DATA_MODEL => true]));
    }

    public function testDenormalize(): void
    {
        /** @var DummyResource $result */
        $result = $this->normalizer->denormalize(['name' => 'dummy', 'alias' => 'dummy alias', 'foo' => ['bar']], DummyResource::class, null, [MappedDataModelNormalizer::MAPPED_DATA_MODEL => true, AbstractNormalizer::IGNORED_ATTRIBUTES => ['alias']]);

        self::assertInstanceOf(DummyResource::class, $result);
        self::assertSame('dummy', $result->getName());
        self::assertNull($result->getAlias());
        self::assertNull($result->getFoo());
    }
}
