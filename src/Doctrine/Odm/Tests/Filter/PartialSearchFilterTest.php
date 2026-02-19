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

use ApiPlatform\Doctrine\Odm\Filter\PartialSearchFilter;
use ApiPlatform\Doctrine\Odm\Tests\DoctrineMongoDbOdmTestCase;
use ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document\Dummy;
use ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document\RelatedDummy;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class PartialSearchFilterTest extends TestCase
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

    public function testPartialSearchSimpleProperty(): void
    {
        $filter = new PartialSearchFilter();
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

    public function testPartialSearchNestedProperty(): void
    {
        $filter = new PartialSearchFilter();
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

    public function testPartialSearchNestedPropertyCaseInsensitive(): void
    {
        $filter = new PartialSearchFilter(caseSensitive: false);
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

        // Same $lookup/$unwind structure regardless of case sensitivity
        $this->assertCount(2, $pipeline);
        $this->assertArrayHasKey('$lookup', $pipeline[0]);
        $this->assertArrayHasKey('$unwind', $pipeline[1]);
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
