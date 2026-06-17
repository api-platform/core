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

use ApiPlatform\Doctrine\Odm\Filter\EndSearchFilter;
use ApiPlatform\Doctrine\Odm\Tests\DoctrineMongoDbOdmTestCase;
use ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document\Dummy;
use ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document\RelatedDummy;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\Regex;
use PHPUnit\Framework\TestCase;

class EndSearchFilterTest extends TestCase
{
    private DocumentManager $manager;

    protected function setUp(): void
    {
        $this->manager = DoctrineMongoDbOdmTestCase::createTestDocumentManager();
    }

    public function testEndSearchSimpleProperty(): void
    {
        $filter = new EndSearchFilter();

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
        $this->assertEquals(
            ['$and' => [['name' => new Regex('foo$', '')]]],
            $context['match']->getQuery()
        );
        $this->assertNoPipelineStages($aggregationBuilder);
    }

    public function testEndSearchCaseInsensitive(): void
    {
        $filter = new EndSearchFilter(caseSensitive: false);

        $parameter = new QueryParameter(property: 'name', key: 'name');
        $parameter->setValue('foo');
        $aggregationBuilder = $this->manager->getRepository(Dummy::class)->createAggregationBuilder();

        $context = [
            'parameter' => $parameter,
            'filters' => ['name' => 'foo'],
        ];

        $filter->apply($aggregationBuilder, Dummy::class, null, $context);

        $this->assertEquals(
            ['$and' => [['name' => new Regex('foo$', 'i')]]],
            $context['match']->getQuery()
        );
    }

    public function testEndSearchNestedProperty(): void
    {
        $filter = new EndSearchFilter();

        $parameter = new QueryParameter(
            property: 'relatedDummy.name',
            key: 'relatedDummy.name',
            extraProperties: [
                'nested_properties_info' => ['relatedDummy.name' => [
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
                ]],
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
        $this->assertArrayHasKey('$lookup', $pipeline[0]);
        $this->assertArrayHasKey('$unwind', $pipeline[1]);

        // The match expression is populated for the parameter extension to commit
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
