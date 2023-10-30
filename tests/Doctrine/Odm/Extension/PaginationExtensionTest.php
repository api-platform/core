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

namespace ApiPlatform\Tests\Doctrine\Odm\Extension;

use ApiPlatform\Doctrine\Odm\Extension\PaginationExtension;
use ApiPlatform\Doctrine\Odm\Paginator;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\Test\DoctrineMongoDbOdmSetup;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy;
use ApiPlatform\Tests\ProphecyTrait;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Count;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Facet;
use Doctrine\ODM\MongoDB\Aggregation\Stage\MatchStage as AggregationMatch;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Skip;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @group mongodb
 */
class PaginationExtensionTest extends TestCase
{
    use ProphecyTrait;

    private $managerRegistryProphecy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
    }

    public function testApplyToCollection(): void
    {
        $pagination = new Pagination([
            'page_parameter_name' => '_page',
        ]);

        $aggregationBuilderProphecy = $this->mockAggregationBuilder(40, 40);

        $context = ['filters' => ['pagination' => true, 'itemsPerPage' => 20, '_page' => 2]];

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilderProphecy->reveal(), 'Foo', (new GetCollection())->withPaginationEnabled(true)->withPaginationClientEnabled(true)->withPaginationItemsPerPage(40), $context);
    }

    public function testApplyToCollectionWithItemPerPageZero(): void
    {
        $pagination = new Pagination([
            'items_per_page' => 0,
            'page_parameter_name' => '_page',
        ]);

        $aggregationBuilderProphecy = $this->mockAggregationBuilder(0, 0);

        $context = ['filters' => ['pagination' => true, 'itemsPerPage' => 0, '_page' => 1]];

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilderProphecy->reveal(), 'Foo', (new GetCollection())->withPaginationEnabled(true)->withPaginationClientEnabled(true)->withPaginationItemsPerPage(0), $context);
    }

    public function testApplyToCollectionWithItemPerPageZeroAndPage2(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Page should not be greater than 1 if limit is equal to 0');

        $pagination = new Pagination([
            'items_per_page' => 0,
            'page_parameter_name' => '_page',
        ]);

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->facet()->shouldNotBeCalled();

        $context = ['filters' => ['pagination' => true, 'itemsPerPage' => 0, '_page' => 2]];

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilderProphecy->reveal(), 'Foo', (new GetCollection())->withPaginationEnabled(true)->withPaginationClientEnabled(true)->withPaginationItemsPerPage(0), $context);
    }

    public function testApplyToCollectionWithItemPerPageLessThan0(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit should not be less than 0');

        $pagination = new Pagination([
            'items_per_page' => -20,
            'page_parameter_name' => '_page',
        ]);

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->facet()->shouldNotBeCalled();

        $context = ['filters' => ['pagination' => true, 'itemsPerPage' => -20, '_page' => 2]];

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilderProphecy->reveal(), 'Foo', (new GetCollection())->withPaginationEnabled(true)->withPaginationClientEnabled(true)->withPaginationItemsPerPage(-20), $context);
    }

    public function testApplyToCollectionWithItemPerPageTooHigh(): void
    {
        $pagination = new Pagination([
            'page_parameter_name' => '_page',
            'maximum_items_per_page' => 300,
        ]);

        $aggregationBuilderProphecy = $this->mockAggregationBuilder(300, 300);

        $context = ['filters' => ['pagination' => true, 'itemsPerPage' => 301, '_page' => 2]];

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilderProphecy->reveal(), 'Foo', (new GetCollection())->withPaginationEnabled(true)->withPaginationClientEnabled(true)->withPaginationClientItemsPerPage(true), $context);
    }

    public function testApplyToCollectionWithGraphql(): void
    {
        $pagination = new Pagination();

        $aggregationBuilderProphecy = $this->mockAggregationBuilder(10, 5);

        $context = ['filters' => ['pagination' => true, 'first' => 5, 'after' => 'OQ=='], 'graphql_operation_name' => 'query'];

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilderProphecy->reveal(), 'Foo', (new GetCollection())->withPaginationEnabled(true)->withPaginationClientEnabled(true)->withPaginationClientItemsPerPage(true), $context);
    }

    public function testApplyToCollectionWithGraphqlAndCountContext(): void
    {
        $pagination = new Pagination();

        $aggregationBuilderProphecy = $this->mockAggregationBuilder(4, 5);
        $iteratorProphecy = $this->prophesize(Iterator::class);
        $iteratorProphecy->toArray()->willReturn([
            [
                'count' => 9,
            ],
        ]);
        $countProphecy = $this->prophesize(Count::class);
        $countProphecy->execute()->shouldBeCalled()->willReturn($iteratorProphecy->reveal());
        $aggregationBuilderProphecy->count('count')->shouldBeCalled()->willReturn($countProphecy->reveal());

        $context = ['filters' => ['pagination' => true, 'last' => 5], 'graphql_operation_name' => 'query'];

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilderProphecy->reveal(), 'Foo', (new GetCollection())->withPaginationEnabled(true)->withPaginationClientEnabled(true)->withPaginationClientItemsPerPage(true), $context);
    }

    public function testApplyToCollectionNoFilters(): void
    {
        $pagination = new Pagination();

        $aggregationBuilderProphecy = $this->mockAggregationBuilder(0, 30);

        $context = [];

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilderProphecy->reveal(), 'Foo', (new GetCollection())->withPaginationEnabled(true), $context);
    }

    public function testApplyToCollectionPaginationDisabled(): void
    {
        $pagination = new Pagination([
            'enabled' => false,
        ]);

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->facet()->shouldNotBeCalled();

        $context = [];

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilderProphecy->reveal(), 'Foo', new GetCollection(), $context);
    }

    public function testApplyToCollectionGraphQlPaginationDisabled(): void
    {
        $pagination = new Pagination([], [
            'enabled' => false,
        ]);

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->facet()->shouldNotBeCalled();

        $context = ['graphql_operation_name' => 'get'];

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilderProphecy->reveal(), 'Foo', new GetCollection(), $context);
    }

    public function testApplyToCollectionWithMaximumItemsPerPage(): void
    {
        $pagination = new Pagination([
            'client_enabled' => true,
            'client_items_per_page' => true,
            'pagination_maximum_items_per_page' => 50,
        ]);

        $aggregationBuilderProphecy = $this->mockAggregationBuilder(0, 80);

        $context = ['filters' => ['pagination' => true, 'itemsPerPage' => 80, 'page' => 1]];

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilderProphecy->reveal(), 'Foo', (new GetCollection())->withPaginationEnabled(true)->withPaginationClientEnabled(true)->withPaginationMaximumItemsPerPage(80), $context);
    }

    public function testSupportsResult(): void
    {
        $pagination = new Pagination();

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $this->assertTrue($extension->supportsResult('Foo', (new GetCollection())->withPaginationEnabled(true)));
    }

    public function testSupportsResultClientNotAllowedToPaginate(): void
    {
        $pagination = new Pagination([
            'enabled' => false,
            'client_enabled' => false,
        ]);

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $this->assertFalse($extension->supportsResult('Foo', new GetCollection(), ['filters' => ['pagination' => true]]));
    }

    public function testSupportsResultPaginationDisabled(): void
    {
        $pagination = new Pagination([
            'enabled' => false,
        ]);

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $this->assertFalse($extension->supportsResult('Foo', new GetCollection(), ['filters' => ['enabled' => false]]));
    }

    public function testSupportsResultGraphQlPaginationDisabled(): void
    {
        $pagination = new Pagination([], [
            'enabled' => false,
        ]);

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $this->assertFalse($extension->supportsResult('Foo', new GetCollection(), ['filters' => ['enabled' => false], 'graphql_operation_name' => 'query']));
    }

    public function testGetResult(): void
    {
        $pagination = new Pagination();

        $fixturesPath = \dirname((string) (new \ReflectionClass(Dummy::class))->getFileName());
        $config = DoctrineMongoDbOdmSetup::createAnnotationMetadataConfiguration([$fixturesPath], true);
        $documentManager = DocumentManager::create(null, $config);

        $this->managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($documentManager);

        $iteratorProphecy = $this->prophesize(Iterator::class);
        $iteratorProphecy->toArray()->willReturn([
            [
                'count' => [
                    [
                        'count' => 9,
                    ],
                ],
            ],
        ]);

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->execute([])->willReturn($iteratorProphecy->reveal());
        $aggregationBuilderProphecy->getPipeline()->willReturn([
            [
                '$facet' => [
                    'results' => [
                        ['$skip' => 3],
                        ['$limit' => 6],
                    ],
                    'count' => [
                        ['$count' => 'count'],
                    ],
                ],
            ],
        ]);

        $paginationExtension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );

        $result = $paginationExtension->getResult($aggregationBuilderProphecy->reveal(), Dummy::class, new GetCollection());

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertInstanceOf(PaginatorInterface::class, $result);
    }

    public function testGetResultWithExecuteOptions(): void
    {
        $pagination = new Pagination();

        $fixturesPath = \dirname((string) (new \ReflectionClass(Dummy::class))->getFileName());
        $config = DoctrineMongoDbOdmSetup::createAnnotationMetadataConfiguration([$fixturesPath], true);
        $documentManager = DocumentManager::create(null, $config);

        $this->managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($documentManager);

        $iteratorProphecy = $this->prophesize(Iterator::class);
        $iteratorProphecy->toArray()->willReturn([
            [
                'count' => [
                    [
                        'count' => 9,
                    ],
                ],
            ],
        ]);

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->execute(['allowDiskUse' => true])->willReturn($iteratorProphecy->reveal());
        $aggregationBuilderProphecy->getPipeline()->willReturn([
            [
                '$facet' => [
                    'results' => [
                        ['$skip' => 3],
                        ['$limit' => 6],
                    ],
                    'count' => [
                        ['$count' => 'count'],
                    ],
                ],
            ],
        ]);

        $paginationExtension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );

        $result = $paginationExtension->getResult($aggregationBuilderProphecy->reveal(), Dummy::class, (new GetCollection())->withExtraProperties(['doctrine_mongodb' => ['execute_options' => ['allowDiskUse' => true]]]));

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertInstanceOf(PaginatorInterface::class, $result);
    }

    private function mockAggregationBuilder($expectedOffset, $expectedLimit)
    {
        $skipProphecy = $this->prophesize(Skip::class);
        if ($expectedLimit > 0) {
            $skipProphecy->limit($expectedLimit)->shouldBeCalled();
        } else {
            $matchProphecy = $this->prophesize(AggregationMatch::class);
            $matchProphecy->field(Paginator::LIMIT_ZERO_MARKER_FIELD)->shouldBeCalled()->willReturn($matchProphecy);
            $matchProphecy->equals(Paginator::LIMIT_ZERO_MARKER)->shouldBeCalled();
            $skipProphecy->match()->shouldBeCalled()->willReturn($matchProphecy->reveal());
        }

        $resultsAggregationBuilderProphecy = $this->prophesize(Builder::class);
        $resultsAggregationBuilderProphecy->skip($expectedOffset)->shouldBeCalled()->willReturn($skipProphecy->reveal());

        $countProphecy = $this->prophesize(Count::class);

        $countAggregationBuilderProphecy = $this->prophesize(Builder::class);
        $countAggregationBuilderProphecy->count('count')->shouldBeCalled()->willReturn($countProphecy->reveal());

        $repositoryProphecy = $this->prophesize(DocumentRepository::class);
        $repositoryProphecy->createAggregationBuilder()->shouldBeCalled()->willReturn(
            $resultsAggregationBuilderProphecy->reveal(),
            $countAggregationBuilderProphecy->reveal()
        );

        $objectManagerProphecy = $this->prophesize(DocumentManager::class);
        $objectManagerProphecy->getRepository('Foo')->shouldBeCalled()->willReturn($repositoryProphecy->reveal());

        $this->managerRegistryProphecy->getManagerForClass('Foo')->shouldBeCalled()->willReturn($objectManagerProphecy->reveal());

        $facetProphecy = $this->prophesize(Facet::class);
        $facetProphecy->pipeline($skipProphecy)->shouldBeCalled()->willReturn($facetProphecy);
        $facetProphecy->pipeline($countProphecy)->shouldBeCalled()->willReturn($facetProphecy);
        $facetProphecy->field('count')->shouldBeCalled()->willReturn($facetProphecy);
        $facetProphecy->field('results')->shouldBeCalled()->willReturn($facetProphecy);

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->facet()->shouldBeCalled()->willReturn($facetProphecy->reveal());

        return $aggregationBuilderProphecy;
    }
}
