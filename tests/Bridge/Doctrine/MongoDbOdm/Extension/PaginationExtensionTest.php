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
use ApiPlatform\Core\DataProvider\Pagination;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\PartialPaginatorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Count;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Facet;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Limit;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Skip;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
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
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_items_per_page' => 40,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => 20, '_page' => 2]));

        $pagination = new Pagination($requestStack, $resourceMetadataFactory, [
            'page_parameter_name' => '_page',
        ]);

        $aggregationBuilder = $this->mockAggregationBuilder(40, 40);

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilder, 'Foo', 'op');
    }

    public function testApplyToCollectionWithItemPerPageZero()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_items_per_page' => 0,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => 0, '_page' => 1]));

        $pagination = new Pagination($requestStack, $resourceMetadataFactory, [
            'items_per_page' => 0,
            'page_parameter_name' => '_page',
        ]);

        $aggregationBuilder = $this->mockAggregationBuilder(0, 0);

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilder, 'Foo', 'op');
    }

    public function testApplyToCollectionWithItemPerPageZeroAndPage2()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Page should not be greater than 1 if limit is equal to 0');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_items_per_page' => 0,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => 0, '_page' => 2]));

        $pagination = new Pagination($requestStack, $resourceMetadataFactory, [
            'items_per_page' => 0,
            'page_parameter_name' => '_page',
        ]);

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->facet()->shouldNotBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilder, 'Foo', 'op');
    }

    public function testApplyToCollectionWithItemPerPageLessThan0()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit should not be less than 0');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_items_per_page' => -20,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => -20, '_page' => 2]));

        $pagination = new Pagination($requestStack, $resourceMetadataFactory, [
            'items_per_page' => -20,
            'page_parameter_name' => '_page',
        ]);

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->facet()->shouldNotBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilder, 'Foo', 'op');
    }

    public function testApplyToCollectionWithItemPerPageTooHigh()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_client_items_per_page' => true,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => 301, '_page' => 2]));

        $pagination = new Pagination($requestStack, $resourceMetadataFactory, [
            'page_parameter_name' => '_page',
            'maximum_items_per_page' => 300,
        ]);

        $aggregationBuilder = $this->mockAggregationBuilder(300, 300);

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilder, 'Foo', 'op');
    }

    public function testApplyToCollectionWithGraphql()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'pagination_client_items_per_page' => 20,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true], [], [
            '_graphql' => true,
            '_graphql_collections_args' => ['Foo' => ['first' => 5, 'after' => 'OQ==']], // base64_encode('9')
        ]));

        $pagination = new Pagination($requestStack, $resourceMetadataFactory);

        $aggregationBuilder = $this->mockAggregationBuilder(10, 5);

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilder, 'Foo', 'op');
    }

    public function testApplyToCollectionNoRequest()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $pagination = new Pagination(new RequestStack(), $resourceMetadataFactory);

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->facet()->shouldNotBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilder, 'Foo', 'op');
    }

    public function testApplyToCollectionEmptyRequest()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $pagination = new Pagination($requestStack, $resourceMetadataFactory);

        $aggregationBuilder = $this->mockAggregationBuilder(0, 30);

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilder, 'Foo', 'op');
    }

    public function testApplyToCollectionPaginationDisabled()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $pagination = new Pagination($requestStack, $resourceMetadataFactory, [
            'enabled' => false,
        ]);

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->facet()->shouldNotBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilder, 'Foo', 'op');
    }

    public function testApplyToCollectionWithMaximumItemsPerPage()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $attributes = [
            'pagination_enabled' => true,
            'pagination_client_enabled' => true,
            'maximum_items_per_page' => 80,
        ];
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], [], $attributes));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true, 'itemsPerPage' => 80, 'page' => 1]));

        $pagination = new Pagination($requestStack, $resourceMetadataFactory, [
            'client_enabled' => true,
            'client_items_per_page' => true,
            'maximum_items_per_page' => 50,
        ]);

        $aggregationBuilder = $this->mockAggregationBuilder(0, 80);

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $extension->applyToCollection($aggregationBuilder, 'Foo', 'op');
    }

    public function testSupportsResult()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $pagination = new Pagination($requestStack, $resourceMetadataFactory);

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $this->assertTrue($extension->supportsResult('Foo', 'op'));
    }

    public function testSupportsResultNoRequest()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $pagination = new Pagination(new RequestStack(), $resourceMetadataFactory);

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $this->assertFalse($extension->supportsResult('Foo', 'op'));
    }

    public function testSupportsResultEmptyRequest()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $pagination = new Pagination($requestStack, $resourceMetadataFactory);

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $this->assertTrue($extension->supportsResult('Foo', 'op'));
    }

    public function testSupportsResultClientNotAllowedToPaginate()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pagination' => true]));

        $pagination = new Pagination($requestStack, $resourceMetadataFactory, [
            'enabled' => false,
            'client_enabled' => false,
        ]);

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
        );
        $this->assertFalse($extension->supportsResult('Foo', 'op'));
    }

    public function testSupportsResultPaginationDisabled()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata(null, null, null, [], []));
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $pagination = new Pagination($requestStack, $resourceMetadataFactory, [
            'enabled' => false,
        ]);

        $extension = new PaginationExtension(
            $this->managerRegistryProphecy->reveal(),
            $pagination
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
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $pagination = new Pagination($requestStack, $resourceMetadataFactory);

        $unitOfWorkProphecy = $this->prophesize(UnitOfWork::class);

        $documentManagerProphecy = $this->prophesize(DocumentManager::class);
        $documentManagerProphecy->getUnitOfWork()->willReturn($unitOfWorkProphecy->reveal());

        $this->managerRegistryProphecy->getManagerForClass('Foo')->willReturn($documentManagerProphecy->reveal());

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
        $aggregationBuilderProphecy->execute()->willReturn($iteratorProphecy->reveal());
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

        return $paginationExtension->getResult($aggregationBuilderProphecy->reveal(), 'Foo');
    }

    private function mockAggregationBuilder($expectedOffset, $expectedLimit)
    {
        $limitProphecy = $this->prophesize(Limit::class);

        $skipProphecy = $this->prophesize(Skip::class);
        $skipProphecy->limit($expectedLimit)->shouldBeCalled()->willReturn($limitProphecy->reveal());

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
        $facetProphecy->pipeline($limitProphecy)->shouldBeCalled()->willReturn($facetProphecy);
        $facetProphecy->pipeline($countProphecy)->shouldBeCalled()->willReturn($facetProphecy);
        $facetProphecy->field('count')->shouldBeCalled()->willReturn($facetProphecy);
        $facetProphecy->field('results')->shouldBeCalled()->willReturn($facetProphecy);

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->facet()->shouldBeCalled()->willReturn($facetProphecy->reveal());

        return $aggregationBuilderProphecy->reveal();
    }
}
