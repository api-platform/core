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

namespace ApiPlatform\Doctrine\Odm\Tests\Filter;

use ApiPlatform\Doctrine\Odm\Filter\ExactFilter;
use ApiPlatform\Doctrine\Odm\Tests\DoctrineMongoDbOdmTestCase;
use ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document\Dummy;
use ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document\RelatedDummy;
use ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document\ThirdLevel;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class ExactFilterTest extends TestCase
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

    public function testExactFilterSimpleProperty(): void
    {
        $filter = new ExactFilter();
        $filter->setManagerRegistry($this->managerRegistry);

        $parameter = new QueryParameter(property: 'name', key: 'name');
        $parameter->setValue('foo');
        $aggregationBuilder = $this->manager->getRepository(Dummy::class)->createAggregationBuilder();

        $context = [
            'parameter' => $parameter,
            'filters' => ['name' => 'foo'],
        ];

        $filter->apply($aggregationBuilder, Dummy::class, null, $context);

        // The filter populates $context['match'] with the match expression (no pipeline stage added)
        $this->assertArrayHasKey('match', $context);
        $this->assertNoPipelineStages($aggregationBuilder);
    }

    public function testExactFilterNestedProperty(): void
    {
        $filter = new ExactFilter();
        $filter->setManagerRegistry($this->managerRegistry);

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
                        [
                            'type' => 'reference',
                            'target_document' => RelatedDummy::class,
                            'is_owning_side' => true,
                            'mapped_by' => null,
                        ],
                    ],
                ],
            ],
        );
        $parameter->setValue('bar');

        $aggregationBuilder = $this->manager->getRepository(Dummy::class)->createAggregationBuilder();

        $context = [
            'parameter' => $parameter,
            'filters' => ['relatedDummy.name' => 'bar'],
        ];

        $filter->apply($aggregationBuilder, Dummy::class, null, $context);
        $pipeline = $aggregationBuilder->getPipeline();

        // Nested property adds $lookup + $unwind stages
        $this->assertCount(2, $pipeline);

        $this->assertEquals([
            '$lookup' => [
                'from' => 'RelatedDummy',
                'localField' => 'relatedDummy',
                'foreignField' => '_id',
                'as' => 'relatedDummy_lkup',
            ],
        ], $pipeline[0]);

        $this->assertArrayHasKey('$unwind', $pipeline[1]);

        // The match expression is populated for the parameter extension to commit
        $this->assertArrayHasKey('match', $context);
    }

    public function testExactFilterMultiHopNestedProperty(): void
    {
        $filter = new ExactFilter();
        $filter->setManagerRegistry($this->managerRegistry);

        $parameter = new QueryParameter(
            property: 'relatedDummy.thirdLevel.level',
            key: 'relatedDummy.thirdLevel.level',
            extraProperties: [
                'nested_property_info' => [
                    'relation_segments' => ['relatedDummy', 'thirdLevel'],
                    'relation_classes' => [Dummy::class, RelatedDummy::class],
                    'leaf_property' => 'level',
                    'leaf_class' => ThirdLevel::class,
                    'odm_segments' => [
                        [
                            'type' => 'reference',
                            'target_document' => RelatedDummy::class,
                            'is_owning_side' => true,
                            'mapped_by' => null,
                        ],
                        [
                            'type' => 'reference',
                            'target_document' => ThirdLevel::class,
                            'is_owning_side' => true,
                            'mapped_by' => null,
                        ],
                    ],
                ],
            ],
        );
        $parameter->setValue(3);

        $aggregationBuilder = $this->manager->getRepository(Dummy::class)->createAggregationBuilder();

        $context = [
            'parameter' => $parameter,
            'filters' => ['relatedDummy.thirdLevel.level' => 3],
        ];

        $filter->apply($aggregationBuilder, Dummy::class, null, $context);
        $pipeline = $aggregationBuilder->getPipeline();

        // 2 lookup+unwind pairs = 4 stages
        $this->assertCount(4, $pipeline);

        $this->assertEquals([
            '$lookup' => [
                'from' => 'RelatedDummy',
                'localField' => 'relatedDummy',
                'foreignField' => '_id',
                'as' => 'relatedDummy_lkup',
            ],
        ], $pipeline[0]);

        $this->assertArrayHasKey('$unwind', $pipeline[1]);

        $this->assertEquals([
            '$lookup' => [
                'from' => 'ThirdLevel',
                'localField' => 'relatedDummy_lkup.thirdLevel',
                'foreignField' => '_id',
                'as' => 'relatedDummy_lkup.thirdLevel_lkup',
            ],
        ], $pipeline[2]);

        $this->assertArrayHasKey('$unwind', $pipeline[3]);

        $this->assertArrayHasKey('match', $context);
    }

    private function assertNoPipelineStages(Builder $aggregationBuilder): void
    {
        try {
            $pipeline = $aggregationBuilder->getPipeline();
            $this->assertEmpty($pipeline);
        } catch (\OutOfRangeException) {
            // No stages added — expected for simple property filters
        }
    }
}
