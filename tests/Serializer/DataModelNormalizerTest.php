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

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Bridge\Eloquent\PropertyAccess\EloquentPropertyAccessor;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Serializer\DataModelNormalizer;
use ApiPlatform\Core\Serializer\MappedDataModelNormalizer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\Dummy as DummyModel;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\RelatedDummy as RelatedDummyModel;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Resource\Dummy as DummyResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Resource\RelatedDummy as RelatedDummyResource;
use ApiPlatform\Core\Tests\ProphecyTrait;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final class DataModelNormalizerTest extends TestCase
{
    use ProphecyTrait;

    private $resourceMetadataFactoryProphecy;
    private $itemDataProviderProphecy;
    private $normalizer;

    protected function setUp(): void
    {
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(RelatedDummyResource::class)->willReturn(['id']);
        $this->itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);

        $this->normalizer = new DataModelNormalizer(
            $this->resourceMetadataFactoryProphecy->reveal(),
            $identifiersExtractorProphecy->reveal(),
            $this->itemDataProviderProphecy->reveal(),
            null,
            null,
            new EloquentPropertyAccessor(PropertyAccess::createPropertyAccessor())
        );
    }

    public function testSupportNormalization(): void
    {
        self::assertFalse($this->normalizer->supportsNormalization(new DummyModel()));
    }

    /**
     * @dataProvider provideSupportDenormalizationCases
     */
    public function testSupportDenormalization($data, string $type, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->normalizer->supportsDenormalization($data, $type));
    }

    public function provideSupportDenormalizationCases(): \Generator
    {
        yield 'not a class' => [[], 'not a class', false];
        yield 'data not an array' => ['string', DummyModel::class, false];
        yield 'data does not contain resource class key' => [[], DummyModel::class, false];
        yield 'resource class' => [[MappedDataModelNormalizer::ITEM_RESOURCE_CLASS_KEY => DummyResource::class], DummyModel::class, true];
    }

    public function testDenormalize(): void
    {
        /** @var DummyModel $result */
        $result = $this->normalizer->denormalize(['name' => 'dummy', 'alias' => 'dummy alias', 'foo' => ['bar']], DummyModel::class, null, [AbstractNormalizer::IGNORED_ATTRIBUTES => ['alias']]);

        self::assertInstanceOf(DummyModel::class, $result);
        self::assertSame('dummy', $result->getAttribute('name'));
        self::assertNull($result->getAttribute('alias'));
        self::assertSame(['bar'], $result->getAttribute('foo'));
    }

    public function testDenormalizeWithChildNotMappedDataModel(): void
    {
        $this->resourceMetadataFactoryProphecy->create(RelatedDummyResource::class)->willReturn(new ResourceMetadata());

        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('Class "ApiPlatform\Core\Tests\Fixtures\TestBundle\Resource\RelatedDummy" should have a data_model attribute in ApiResource.');

        $this->normalizer->denormalize(['relatedDummy' => ['name' => 'a related dummy', MappedDataModelNormalizer::ITEM_RESOURCE_CLASS_KEY => RelatedDummyResource::class]], DummyModel::class, null, []);
    }

    public function testDenormalizeWithChildMappedDataModel(): void
    {
        $this->resourceMetadataFactoryProphecy->create(RelatedDummyResource::class)->willReturn((new ResourceMetadata())->withAttributes(['data_model' => RelatedDummyModel::class]));
        $existingRelatedDummy = new RelatedDummyModel();
        $existingRelatedDummy->setAttribute('symfony', 'twig');
        $this->itemDataProviderProphecy->getItem(RelatedDummyModel::class, ['id' => 7], null, Argument::type('array'))->willReturn($existingRelatedDummy);
        $this->itemDataProviderProphecy->getItem(RelatedDummyModel::class, [], null, Argument::type('array'))->willReturn(null);

        /** @var DummyModel $result */
        $result = $this->normalizer->denormalize([
            'name' => 'dummy',
            'alias' => 'dummy alias',
            'foo' => ['bar'],
            'relatedDummy' => ['name' => 'a related dummy', MappedDataModelNormalizer::ITEM_RESOURCE_CLASS_KEY => RelatedDummyResource::class],
            'relatedDummies' => [
                ['name' => 'a newly created related dummy', MappedDataModelNormalizer::ITEM_RESOURCE_CLASS_KEY => RelatedDummyResource::class],
                ['id' => 7, 'name' => 'an existing related dummy', MappedDataModelNormalizer::ITEM_RESOURCE_CLASS_KEY => RelatedDummyResource::class],
            ],
        ], DummyModel::class, null, []);

        self::assertInstanceOf(DummyModel::class, $result);
        self::assertSame('dummy', $result->getAttribute('name'));
        self::assertSame('dummy alias', $result->getAttribute('alias'));
        self::assertSame(['bar'], $result->getAttribute('foo'));
        self::assertInstanceOf(RelatedDummyModel::class, $result->getRelation('relatedDummy'));
        self::assertSame('a related dummy', $result->getRelation('relatedDummy')->getAttribute('name'));
        self::assertInstanceOf(Collection::class, $result->getRelation('relatedDummies'));
        self::assertCount(2, $result->getRelation('relatedDummies'));
        self::assertSame('a newly created related dummy', $result->getRelation('relatedDummies')[0]->getAttribute('name'));
        self::assertSame('an existing related dummy', $result->getRelation('relatedDummies')[1]->getAttribute('name'));
        self::assertSame('twig', $result->getRelation('relatedDummies')[1]->getAttribute('symfony'));
    }
}
