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

namespace ApiPlatform\Core\Tests\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\MappedDataModelCollectionDataProvider;
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
use Symfony\Component\Serializer\Serializer;

final class MappedDataModelCollectionDataProviderTest extends TestCase
{
    use ProphecyTrait;

    private $dataProviderProphecy;
    private $serializerProphecy;
    private $dataProvider;

    protected function setUp(): void
    {
        $this->dataProviderProphecy = $this->prophesize(CollectionDataProviderInterface::class);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(NotAResource::class)->willThrow(new ResourceClassNotFoundException());
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata());
        $resourceMetadataFactoryProphecy->create(DummyResource::class)->willReturn((new ResourceMetadata())->withAttributes(['data_model' => DummyModel::class]));
        $this->serializerProphecy = $this->prophesize(Serializer::class);

        $this->dataProvider = new MappedDataModelCollectionDataProvider(
            $this->dataProviderProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $this->serializerProphecy->reveal()
        );
    }

    /**
     * @dataProvider provideSupportsCases
     */
    public function testSupports(string $resourceClass, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->dataProvider->supports($resourceClass));
    }

    public function provideSupportsCases(): \Generator
    {
        yield 'not a resource' => [NotAResource::class, false];
        yield 'resource' => [DummyCar::class, false];
        yield 'mapped data model resource' => [DummyResource::class, true];
    }

    public function testGetCollection(): void
    {
        $resourceClass = DummyResource::class;
        $collectionItem = new DummyModel();
        $collectionItem->setAttribute('id', 76);
        $resourceCollectionItem = new DummyResource();
        $resourceCollectionItem->setId(76);
        $resourceCollectionItem->setName('dummy');

        $this->dataProviderProphecy->getCollection(DummyModel::class, null, [])->willReturn([$collectionItem]);
        $normalizedCollectionItem = [
            'id' => 76,
            'name' => 'dummy',
        ];
        $this->serializerProphecy->normalize($collectionItem)->willReturn($normalizedCollectionItem);
        $this->serializerProphecy->denormalize($normalizedCollectionItem, $resourceClass, null, [MappedDataModelNormalizer::MAPPED_DATA_MODEL => true])->willReturn($resourceCollectionItem);

        self::assertSame([$resourceCollectionItem], $this->dataProvider->getCollection($resourceClass));
    }
}
