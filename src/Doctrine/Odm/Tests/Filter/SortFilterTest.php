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

use ApiPlatform\Doctrine\Odm\Filter\SortFilter;
use ApiPlatform\Doctrine\Odm\Tests\DoctrineMongoDbOdmTestCase;
use ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document\Dummy;
use ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document\RelatedDummy;
use ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document\ThirdLevel;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\TestCase;

class SortFilterTest extends TestCase
{
    private DocumentManager $manager;

    protected function setUp(): void
    {
        $this->manager = DoctrineMongoDbOdmTestCase::createTestDocumentManager();
    }

    public function testSortAscending(): void
    {
        $filter = new SortFilter();

        $parameter = new QueryParameter(property: 'name', key: 'order[name]');
        $parameter = $parameter->setValue('asc');
        $aggregationBuilder = $this->manager->getRepository(Dummy::class)->createAggregationBuilder();

        $context = [
            'parameter' => $parameter,
        ];

        $filter->apply($aggregationBuilder, Dummy::class, null, $context);
        $pipeline = $aggregationBuilder->getPipeline();

        $this->assertEquals([
            ['$sort' => ['name' => 1]],
        ], $pipeline);
    }

    public function testSortDescending(): void
    {
        $filter = new SortFilter();

        $parameter = new QueryParameter(property: 'name', key: 'order[name]');
        $parameter = $parameter->setValue('DESC');
        $aggregationBuilder = $this->manager->getRepository(Dummy::class)->createAggregationBuilder();

        $context = [
            'parameter' => $parameter,
        ];

        $filter->apply($aggregationBuilder, Dummy::class, null, $context);
        $pipeline = $aggregationBuilder->getPipeline();

        $this->assertEquals([
            ['$sort' => ['name' => -1]],
        ], $pipeline);
    }

    public function testInvalidDirection(): void
    {
        $filter = new SortFilter();

        $parameter = new QueryParameter(property: 'name', key: 'order[name]');
        $parameter = $parameter->setValue('invalid');
        $aggregationBuilder = $this->manager->getRepository(Dummy::class)->createAggregationBuilder();

        $context = [
            'parameter' => $parameter,
        ];

        $filter->apply($aggregationBuilder, Dummy::class, null, $context);

        $pipeline = [];
        try {
            $pipeline = $aggregationBuilder->getPipeline();
        } catch (\OutOfRangeException) {
        }

        $this->assertEmpty($pipeline);
    }

    public function testNullParameter(): void
    {
        $filter = new SortFilter();

        $aggregationBuilder = $this->manager->getRepository(Dummy::class)->createAggregationBuilder();

        $context = [];

        $filter->apply($aggregationBuilder, Dummy::class, null, $context);

        $pipeline = [];
        try {
            $pipeline = $aggregationBuilder->getPipeline();
        } catch (\OutOfRangeException) {
        }

        $this->assertEmpty($pipeline);
    }

    public function testNullValue(): void
    {
        $filter = new SortFilter();

        $parameter = new QueryParameter(property: 'name', key: 'order[name]');
        $aggregationBuilder = $this->manager->getRepository(Dummy::class)->createAggregationBuilder();

        $context = [
            'parameter' => $parameter,
        ];

        $filter->apply($aggregationBuilder, Dummy::class, null, $context);

        $pipeline = [];
        try {
            $pipeline = $aggregationBuilder->getPipeline();
        } catch (\OutOfRangeException) {
        }

        $this->assertEmpty($pipeline);
    }

    public function testNestedPropertySort(): void
    {
        $filter = new SortFilter();

        $parameter = new QueryParameter(
            property: 'relatedDummy.name',
            key: 'order[relatedDummy.name]',
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
        $parameter = $parameter->setValue('asc');

        $aggregationBuilder = $this->manager->getRepository(Dummy::class)->createAggregationBuilder();

        $context = [
            'parameter' => $parameter,
        ];

        $filter->apply($aggregationBuilder, Dummy::class, null, $context);
        $pipeline = $aggregationBuilder->getPipeline();

        $this->assertEquals([
            [
                '$lookup' => [
                    'from' => 'RelatedDummy',
                    'localField' => 'relatedDummy',
                    'foreignField' => '_id',
                    'as' => 'relatedDummy_lkup',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$relatedDummy_lkup',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            [
                '$sort' => ['relatedDummy_lkup.name' => 1],
            ],
        ], $pipeline);
    }

    public function testNullsComparison(): void
    {
        $filter = new SortFilter(nullsComparison: 'nulls_smallest');

        $parameter = new QueryParameter(property: 'dummyDate', key: 'order[dummyDate]');
        $parameter = $parameter->setValue('asc');
        $aggregationBuilder = $this->manager->getRepository(Dummy::class)->createAggregationBuilder();

        $context = [
            'parameter' => $parameter,
        ];

        $filter->apply($aggregationBuilder, Dummy::class, null, $context);
        $pipeline = $aggregationBuilder->getPipeline();

        // nulls_smallest + ASC => nulls direction ASC (1), single combined $sort stage
        $this->assertCount(2, $pipeline);
        $this->assertArrayHasKey('$addFields', $pipeline[0]);
        $this->assertArrayHasKey('_null_rank_dummyDate', $pipeline[0]['$addFields']);
        $this->assertEquals(['$sort' => ['_null_rank_dummyDate' => 1, 'dummyDate' => 1]], $pipeline[1]);
    }

    public function testGetSchema(): void
    {
        $filter = new SortFilter();

        $parameter = new QueryParameter(property: 'name', key: 'order[name]');

        $this->assertEquals(
            ['type' => 'string', 'enum' => ['asc', 'desc', 'ASC', 'DESC']],
            $filter->getSchema($parameter)
        );
    }

    public function testMultiHopNestedPropertySort(): void
    {
        $filter = new SortFilter();

        $parameter = new QueryParameter(
            property: 'relatedDummy.thirdLevel.level',
            key: 'order[relatedDummy.thirdLevel.level]',
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
        $parameter = $parameter->setValue('asc');

        $aggregationBuilder = $this->manager->getRepository(Dummy::class)->createAggregationBuilder();

        $context = [
            'parameter' => $parameter,
        ];

        $filter->apply($aggregationBuilder, Dummy::class, null, $context);
        $pipeline = $aggregationBuilder->getPipeline();

        // 2 lookup+unwind pairs + 1 sort = 5 stages
        $this->assertCount(5, $pipeline);

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

        $this->assertEquals([
            '$sort' => ['relatedDummy_lkup.thirdLevel_lkup.level' => 1],
        ], $pipeline[4]);
    }

    public function testLookupDeduplication(): void
    {
        $filter = new SortFilter();

        $parameter = new QueryParameter(
            property: 'relatedDummy.name',
            key: 'order[relatedDummy.name]',
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
        $parameter = $parameter->setValue('asc');

        $aggregationBuilder = $this->manager->getRepository(Dummy::class)->createAggregationBuilder();

        // Shared context simulating a prior filter having already added the lookup
        $context = [
            'parameter' => $parameter,
            '_odm_lookups' => ['relatedDummy_lkup' => true],
        ];

        $filter->apply($aggregationBuilder, Dummy::class, null, $context);
        $pipeline = $aggregationBuilder->getPipeline();

        // Only $sort should be present — no $lookup/$unwind since they were deduplicated
        $this->assertCount(1, $pipeline);
        $this->assertEquals(['$sort' => ['relatedDummy_lkup.name' => 1]], $pipeline[0]);
    }
}
