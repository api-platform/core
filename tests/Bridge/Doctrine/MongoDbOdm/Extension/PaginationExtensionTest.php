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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\MongoDbOdm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\PaginationExtension;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\PartialPaginatorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\MongoDB\Aggregation\Stage\Count;
use Doctrine\MongoDB\Aggregation\Stage\Facet;
use Doctrine\MongoDB\Aggregation\Stage\Limit;
use Doctrine\MongoDB\Aggregation\Stage\Skip;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\CommandCursor;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class PaginationExtensionTest extends TestCase
{
    private $managerRegistryProphecy;

    protected function setUp()
    {
        $this->managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
    }

    public function testApplyToCollection()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => 20, '_page' => 2]));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_items_per_page' => 40,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $aggregationBuilder = $this->mockAggregationBuilder(40, 40);

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $requestStack,
            $resourceMetadataFactory,
            true,
            false,
            false,
            30,
            '_page'
        );
        $extension->applyToCollection($aggregationBuilder, 'Foo', 'op');
    }

    public function testApplyToCollectionWithItemPerPageZero()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item per page parameter should not be less than 1');

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => 0, '_page' => 1]));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_items_per_page' => 0,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->facet()->shouldNotBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $requestStack,
            $resourceMetadataFactory,
            true,
            false,
            false,
            0,
            '_page'
        );
        $extension->applyToCollection($aggregationBuilder, 'Foo', 'op');
    }

    public function testApplyToCollectionWithItemPerPageLessThen0()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item per page parameter should not be less than 1');

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => -20, '_page' => 2]));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_items_per_page' => -20,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->facet()->shouldNotBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $requestStack,
            $resourceMetadataFactory,
            true,
            false,
            false,
            -20,
            '_page'
        );
        $extension->applyToCollection($aggregationBuilder, 'Foo', 'op');
    }

    public function testApplyToCollectionWithItemPerPageTooHigh()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => 301, '_page' => 2]));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_client_items_per_page' => true,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $aggregationBuilder = $this->mockAggregationBuilder(300, 300);

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $requestStack,
            $resourceMetadataFactory,
            true,
            false,
            false,
            30,
            '_page',
            'pagination',
            'itemsPerPage',
            300
        );
        $extension->applyToCollection($aggregationBuilder, 'Foo', 'op');
    }

    public function testApplyToCollectionWithGraphql()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true], [], [
            '_graphql' => true,
            '_graphql_collections_args' => ['Foo' => ['first' => 5, 'after' => 'OQ==']], // base64_encode('9')
        ]));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_client_items_per_page' => 20,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $aggregationBuilder = $this->mockAggregationBuilder(10, 5);

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $requestStack,
            $resourceMetadataFactory,
            true,
            false,
            false,
            30
        );
        $extension->applyToCollection($aggregationBuilder, 'Foo', 'op');
    }

    public function testApplyToCollectionNoRequest()
    {
        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->facet()->shouldNotBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            new RequestStack(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal()
        );
        $extension->applyToCollection($aggregationBuilder, 'Foo', 'op');
    }

    public function testApplyToCollectionEmptyRequest()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $aggregationBuilder = $this->mockAggregationBuilder(0, 30);

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $requestStack,
            $resourceMetadataFactory
        );
        $extension->applyToCollection($aggregationBuilder, 'Foo', 'op');
    }

    public function testApplyToCollectionPaginationDisabled()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->facet()->shouldNotBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $requestStack,
            $resourceMetadataFactory,
            false
        );
        $extension->applyToCollection($aggregationBuilder, 'Foo', 'op');
    }

    public function testApplyToCollectionWithMaximumItemsPerPage()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => 80, 'page' => 1]));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'maximum_items_per_page' => 80,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $aggregationBuilder = $this->mockAggregationBuilder(0, 80);

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $requestStack,
            $resourceMetadataFactory,
            true,
            true,
            true,
            30,
            'page',
            'pagination',
            'itemsPerPage',
            50
        );
        $extension->applyToCollection($aggregationBuilder, 'Foo', 'op');
    }

    public function testSupportsResult()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $requestStack,
            $resourceMetadataFactory
        );
        $this->assertTrue($extension->supportsResult('Foo', 'op'));
    }

    public function testSupportsResultNoRequest()
    {
        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            new RequestStack(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal()
        );
        $this->assertFalse($extension->supportsResult('Foo', 'op'));
    }

    public function testSupportsResultEmptyRequest()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $requestStack,
            $resourceMetadataFactory
        );
        $this->assertTrue($extension->supportsResult('Foo', 'op'));
    }

    public function testSupportsResultClientNotAllowedToPaginate()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true]));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $requestStack,
            $resourceMetadataFactory,
            false,
            false
        );
        $this->assertFalse($extension->supportsResult('Foo', 'op'));
    }

    public function testSupportsResultPaginationDisabled()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []))->shouldBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $requestStack,
            $resourceMetadataFactory,
            false
        );
        $this->assertFalse($extension->supportsResult('Foo', 'op'));
    }

    public function testGetResult()
    {
        $result = $this->getPaginationExtensionResult();

        $this->assertInstanceOf(PartialPaginatorInterface::class, $result);
        $this->assertInstanceOf(PaginatorInterface::class, $result);
    }

    private function getPaginationExtensionResult()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $unitOfWorkProphecy = $this->prophesize(UnitOfWork::class);

        $documentManagerProphecy = $this->prophesize(DocumentManager::class);
        $documentManagerProphecy->getUnitOfWork()->shouldBeCalled()->willReturn($unitOfWorkProphecy->reveal());

        $this->managerRegistryProphecy->getManagerForClass('Foo')->shouldBeCalled()->willReturn($documentManagerProphecy->reveal());

        $commandCursorProphecy = $this->prophesize(CommandCursor::class);
        $commandCursorProphecy->info()->shouldBeCalled()->willReturn([
            'query' => [
                'pipeline' => [
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
                ],
            ],
        ]);
        $commandCursorProphecy->toArray()->shouldBeCalled()->willReturn([
            [
                'count' => [
                    [
                        'count' => 9,
                    ],
                ],
            ],
        ]);

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->execute()->shouldBeCalled()->willReturn($commandCursorProphecy->reveal());

        $paginationExtension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $requestStack,
            $resourceMetadataFactoryProphecy->reveal()
        );

        return $paginationExtension->getResult($aggregationBuilderProphecy->reveal(), 'Foo');
    }

    private function mockAggregationBuilder($expectedFirstResult, $expectedItemsPerPage)
    {
        $limitProphecy = $this->prophesize(Limit::class);

        $skipProphecy = $this->prophesize(Skip::class);
        $skipProphecy->limit($expectedItemsPerPage)->shouldBeCalled()->willReturn($limitProphecy->reveal());

        $resultsAggregationBuilderProphecy = $this->prophesize(Builder::class);
        $resultsAggregationBuilderProphecy->skip($expectedFirstResult)->shouldBeCalled()->willReturn($skipProphecy->reveal());

        $countProphecy = $this->prophesize(Count::class);

        $countAggregationBuilderProphecy = $this->prophesize(Builder::class);
        $countAggregationBuilderProphecy->count('count')->shouldBeCalled()->willReturn($countProphecy->reveal());

        $repositoryProphecy = $this->prophesize(DocumentRepository::class);
        $repositoryProphecy->createAggregationBuilder()->shouldBeCalled()->willReturn(
            $resultsAggregationBuilderProphecy->reveal(),
            $countAggregationBuilderProphecy->reveal()
        );

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->getRepository('Foo')->shouldBeCalled()->willReturn($repositoryProphecy->reveal());

        $this->managerRegistryProphecy->getManagerForClass('Foo')->shouldBeCalled()->willReturn($objectManagerProphecy->reveal());

        $facetProphecy = $this->prophesize(Facet::class);
        $facetProphecy->pipeline($limitProphecy)->shouldBeCalled()->willReturn($facetProphecy);
        $facetProphecy->pipeline($countProphecy)->shouldBeCalled()->willReturn($facetProphecy);
        $facetProphecy->field('count')->shouldBeCalled()->willReturn($facetProphecy);
        $facetProphecy->field('results')->shouldBeCalled()->willReturn($facetProphecy);

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->facet()->shouldBeCalled()->willReturn($facetProphecy->reveal());

        return $aggregationBuilderProphecy->reveal();
    }
}
