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

namespace ApiPlatform\Core\Tests\DataPersister;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\DataPersister\MappedDataModelDataPersister;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Serializer\MappedDataModelNormalizer;
use ApiPlatform\Core\Tests\Fixtures\NotAResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\Dummy as DummyModel;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Resource\Dummy as DummyResource;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;

final class MappedDataModelDataPersisterTest extends TestCase
{
    use ProphecyTrait;

    private $dataPersisterProphecy;
    private $itemDataProviderProphecy;
    private $serializerProphecy;
    private $dataPersister;

    protected function setUp(): void
    {
        $this->dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $this->itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(NotAResource::class)->willThrow(new ResourceClassNotFoundException());
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata());
        $resourceMetadataFactoryProphecy->create(DummyResource::class)->willReturn((new ResourceMetadata())->withAttributes(['data_model' => DummyModel::class]));
        $this->serializerProphecy = $this->prophesize(Serializer::class);
        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass(DummyResource::class)->willReturn(['id']);

        $this->dataPersister = new MappedDataModelDataPersister(
            $this->dataPersisterProphecy->reveal(),
            $this->itemDataProviderProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $this->serializerProphecy->reveal(),
            $identifiersExtractorProphecy->reveal(),
            PropertyAccess::createPropertyAccessor()
        );
    }

    /**
     * @dataProvider provideSupportsCases
     */
    public function testSupports($data, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->dataPersister->supports($data));
    }

    public function provideSupportsCases(): \Generator
    {
        yield 'null data' => [null, false];
        yield 'not a resource' => [new NotAResource('foo', 'bar'), false];
        yield 'resource' => [new DummyCar(), false];
        yield 'mapped data model resource' => [new DummyResource(), true];
    }

    public function testPersist(): void
    {
        $data = new DummyResource();
        $persistedData = new DummyModel();
        $persistedData->setAttribute('id', 76);
        $persistedResourceData = new DummyResource();
        $persistedResourceData->setId(76);
        $persistedResourceData->setName('dummy');

        $this->dataPersisterProphecy->persist(Argument::type(DummyModel::class))->willReturn($persistedData);
        $normalizedMappedDataModel = [
            'name' => 'dummy',
            MappedDataModelNormalizer::ITEM_RESOURCE_CLASS_KEY => DummyResource::class,
        ];
        $this->serializerProphecy->normalize($data, null, [MappedDataModelNormalizer::MAPPED_DATA_MODEL => true])->willReturn($normalizedMappedDataModel);
        $normalizedPersistedData = [
            'id' => 76,
            'name' => 'dummy',
        ];
        $this->serializerProphecy->normalize($persistedData)->willReturn($normalizedPersistedData);
        $this->serializerProphecy->denormalize($normalizedMappedDataModel, DummyModel::class, null, [AbstractNormalizer::OBJECT_TO_POPULATE => null])->willReturn(new DummyModel());
        $this->serializerProphecy->denormalize($normalizedPersistedData, DummyResource::class, null, [MappedDataModelNormalizer::MAPPED_DATA_MODEL => true])->willReturn($persistedResourceData);

        self::assertSame($persistedResourceData, $this->dataPersister->persist($data));
    }

    public function testUpdatePersist(): void
    {
        $data = new DummyResource();
        $data->setId(76);
        $toUpdate = new DummyModel();
        $toUpdate->setAttribute('name', 'to update');
        $persistedData = new DummyModel();
        $persistedData->setAttribute('id', 76);
        $persistedResourceData = new DummyResource();
        $persistedResourceData->setId(76);
        $persistedResourceData->setName('dummy');

        $this->dataPersisterProphecy->persist(Argument::type(DummyModel::class))->willReturn($persistedData);
        $this->itemDataProviderProphecy->getItem(DummyModel::class, ['id' => 76], null, Argument::type('array'))->willReturn($toUpdate);
        $normalizedMappedDataModel = [
            'name' => 'dummy',
            MappedDataModelNormalizer::ITEM_RESOURCE_CLASS_KEY => DummyResource::class,
        ];
        $this->serializerProphecy->normalize($data, null, [MappedDataModelNormalizer::MAPPED_DATA_MODEL => true])->willReturn($normalizedMappedDataModel);
        $normalizedPersistedData = [
            'id' => 76,
            'name' => 'dummy',
        ];
        $this->serializerProphecy->normalize($persistedData)->willReturn($normalizedPersistedData);
        $this->serializerProphecy->denormalize($normalizedMappedDataModel, DummyModel::class, null, [AbstractNormalizer::OBJECT_TO_POPULATE => $toUpdate])->willReturn(new DummyModel());
        $this->serializerProphecy->denormalize($normalizedPersistedData, DummyResource::class, null, [MappedDataModelNormalizer::MAPPED_DATA_MODEL => true])->willReturn($persistedResourceData);

        self::assertSame($persistedResourceData, $this->dataPersister->persist($data));
    }

    public function testRemoveNotFound(): void
    {
        $data = new DummyResource();
        $data->setId(76);
        $toRemove = new DummyModel();
        $toRemove->setAttribute('name', 'to remove');

        $this->dataPersisterProphecy->remove(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The data model of "ApiPlatform\Core\Tests\Fixtures\TestBundle\Resource\Dummy" has not been found.');

        $this->dataPersister->remove($data);
    }

    public function testRemove(): void
    {
        $data = new DummyResource();
        $data->setId(76);
        $toRemove = new DummyModel();
        $toRemove->setAttribute('name', 'to remove');

        $this->itemDataProviderProphecy->getItem(DummyModel::class, ['id' => 76], null, Argument::type('array'))->willReturn($toRemove);
        $this->dataPersisterProphecy->remove(Argument::type(DummyModel::class), Argument::type('array'))->shouldBeCalledOnce();

        $this->dataPersister->remove($data);
    }
}
