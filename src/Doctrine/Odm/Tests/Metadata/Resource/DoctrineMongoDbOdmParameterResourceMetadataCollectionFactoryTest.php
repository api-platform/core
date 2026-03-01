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

namespace ApiPlatform\Doctrine\Odm\Tests\Metadata\Resource;

use ApiPlatform\Doctrine\Odm\Metadata\Resource\DoctrineMongoDbOdmParameterResourceMetadataCollectionFactory;
use ApiPlatform\Doctrine\Odm\State\Options;
use ApiPlatform\Doctrine\Odm\Tests\DoctrineMongoDbOdmTestCase;
use ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document\Dummy;
use ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document\EmbeddableDummy;
use ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document\RelatedDummy;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class DoctrineMongoDbOdmParameterResourceMetadataCollectionFactoryTest extends TestCase
{
    private DocumentManager $manager;
    private ManagerRegistry $managerRegistry;

    protected function setUp(): void
    {
        $this->manager = DoctrineMongoDbOdmTestCase::createTestDocumentManager();

        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturn($this->manager);
        $this->managerRegistry = $managerRegistry;
    }

    public function testParameterWithoutNestedInfoPassedThrough(): void
    {
        $parameter = new QueryParameter(property: 'name', key: 'name');

        $collection = $this->createCollectionWithParameter($parameter, Dummy::class);
        $factory = $this->createFactory($collection);

        $result = $factory->create(Dummy::class);
        $resultParameter = $this->getFirstParameter($result);

        // No nested_property_info — parameter should be unchanged
        $this->assertArrayNotHasKey('nested_property_info', $resultParameter->getExtraProperties());
    }

    public function testParameterWithNestedInfoGetsOdmSegments(): void
    {
        $parameter = new QueryParameter(
            property: 'relatedDummy.name',
            key: 'relatedDummy.name',
            extraProperties: [
                'nested_property_info' => [
                    'relation_segments' => ['relatedDummy'],
                    'relation_classes' => [Dummy::class],
                    'leaf_property' => 'name',
                    'leaf_class' => RelatedDummy::class,
                ],
            ],
        );

        $collection = $this->createCollectionWithParameter($parameter, Dummy::class);
        $factory = $this->createFactory($collection);

        $result = $factory->create(Dummy::class);
        $resultParameter = $this->getFirstParameter($result);

        $nestedInfo = $resultParameter->getExtraProperties()['nested_property_info'];
        $this->assertArrayHasKey('odm_segments', $nestedInfo);
        $this->assertCount(1, $nestedInfo['odm_segments']);

        $segment = $nestedInfo['odm_segments'][0];
        $this->assertSame('reference', $segment['type']);
        $this->assertSame(RelatedDummy::class, $segment['target_document']);
        $this->assertTrue($segment['is_owning_side']);
        $this->assertNull($segment['mapped_by']);
    }

    public function testEmbeddedDocumentProducesEmbedType(): void
    {
        $parameter = new QueryParameter(
            property: 'embeddedDummy.dummyName',
            key: 'embeddedDummy.dummyName',
            extraProperties: [
                'nested_property_info' => [
                    'relation_segments' => ['embeddedDummy'],
                    'relation_classes' => [RelatedDummy::class],
                    'leaf_property' => 'dummyName',
                    'leaf_class' => EmbeddableDummy::class,
                ],
            ],
        );

        $collection = $this->createCollectionWithParameter($parameter, RelatedDummy::class);
        $factory = $this->createFactory($collection);

        $result = $factory->create(RelatedDummy::class);
        $resultParameter = $this->getFirstParameter($result);

        $nestedInfo = $resultParameter->getExtraProperties()['nested_property_info'];
        $this->assertArrayHasKey('odm_segments', $nestedInfo);

        $segment = $nestedInfo['odm_segments'][0];
        $this->assertSame('embed', $segment['type']);
        $this->assertSame(EmbeddableDummy::class, $segment['target_document']);
    }

    public function testAlreadyEnrichedParameterNotProcessedAgain(): void
    {
        $parameter = new QueryParameter(
            property: 'relatedDummy.name',
            key: 'relatedDummy.name',
            extraProperties: [
                'nested_property_info' => [
                    'relation_segments' => ['relatedDummy'],
                    'relation_classes' => [Dummy::class],
                    'leaf_property' => 'name',
                    'leaf_class' => RelatedDummy::class,
                    'odm_segments' => [
                        ['type' => 'reference', 'target_document' => RelatedDummy::class, 'is_owning_side' => true, 'mapped_by' => null],
                    ],
                ],
            ],
        );

        $collection = $this->createCollectionWithParameter($parameter, Dummy::class);
        $factory = $this->createFactory($collection);

        $result = $factory->create(Dummy::class);
        $resultParameter = $this->getFirstParameter($result);

        $nestedInfo = $resultParameter->getExtraProperties()['nested_property_info'];
        // odm_segments should still be the original — not re-processed
        $this->assertCount(1, $nestedInfo['odm_segments']);
    }

    public function testNonOdmManagedClassSkippedGracefully(): void
    {
        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturn(null);

        $parameter = new QueryParameter(
            property: 'relatedDummy.name',
            key: 'relatedDummy.name',
            extraProperties: [
                'nested_property_info' => [
                    'relation_segments' => ['relatedDummy'],
                    'relation_classes' => [Dummy::class],
                    'leaf_property' => 'name',
                    'leaf_class' => RelatedDummy::class,
                ],
            ],
        );

        $collection = $this->createCollectionWithParameter($parameter, Dummy::class);

        $decorated = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->method('create')->willReturn($collection);

        $factory = new DoctrineMongoDbOdmParameterResourceMetadataCollectionFactory($managerRegistry, $decorated);
        $result = $factory->create(Dummy::class);
        $resultParameter = $this->getFirstParameter($result);

        // No odm_segments should be added since the class isn't managed by ODM
        $nestedInfo = $resultParameter->getExtraProperties()['nested_property_info'];
        $this->assertArrayNotHasKey('odm_segments', $nestedInfo);
    }

    public function testNestedPropertiesInfoEnrichedForFreeTextQueryFilter(): void
    {
        $parameter = new QueryParameter(
            property: null,
            key: 'search',
            extraProperties: [
                'nested_properties_info' => [
                    'relatedDummy.name' => [
                        'relation_segments' => ['relatedDummy'],
                        'relation_classes' => [Dummy::class],
                        'leaf_property' => 'name',
                        'leaf_class' => RelatedDummy::class,
                    ],
                ],
            ],
        );

        $collection = $this->createCollectionWithParameter($parameter, Dummy::class);
        $factory = $this->createFactory($collection);

        $result = $factory->create(Dummy::class);
        $resultParameter = $this->getFirstParameter($result);

        $nestedPropertiesInfo = $resultParameter->getExtraProperties()['nested_properties_info'];
        $this->assertArrayHasKey('odm_segments', $nestedPropertiesInfo['relatedDummy.name']);
        $this->assertSame('reference', $nestedPropertiesInfo['relatedDummy.name']['odm_segments'][0]['type']);
    }

    private function createFactory(ResourceMetadataCollection $collection): DoctrineMongoDbOdmParameterResourceMetadataCollectionFactory
    {
        $decorated = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->method('create')->willReturn($collection);

        return new DoctrineMongoDbOdmParameterResourceMetadataCollectionFactory($this->managerRegistry, $decorated);
    }

    private function createCollectionWithParameter(QueryParameter $parameter, string $resourceClass): ResourceMetadataCollection
    {
        $parameters = new Parameters();
        $parameters->add($parameter->getKey(), $parameter);

        $operation = (new GetCollection())->withClass($resourceClass)->withStateOptions(new Options(documentClass: $resourceClass))->withParameters($parameters);
        $operations = new Operations();
        $operations->add('_api_'.$resourceClass.'_GetCollection', $operation);

        $resource = (new ApiResource())->withOperations($operations)->withClass($resourceClass);

        return new ResourceMetadataCollection($resourceClass, [$resource]);
    }

    private function getFirstParameter(ResourceMetadataCollection $collection): QueryParameter
    {
        foreach ($collection as $resource) {
            foreach ($resource->getOperations() as $operation) {
                foreach ($operation->getParameters() as $parameter) {
                    if (!$parameter instanceof QueryParameter) {
                        continue;
                    }

                    return $parameter;
                }
            }
        }

        $this->fail('No parameter found in collection');
    }
}
