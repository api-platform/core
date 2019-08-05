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

namespace ApiPlatform\Core\Tests\GraphQl\Resolver\Factory;

use ApiPlatform\Core\GraphQl\Resolver\Factory\CollectionResolverFactory;
use ApiPlatform\Core\GraphQl\Resolver\Stage\DenyAccessStageInterface;
use ApiPlatform\Core\GraphQl\Resolver\Stage\ReadStageInterface;
use ApiPlatform\Core\GraphQl\Resolver\Stage\SerializeStageInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CollectionResolverFactoryTest extends TestCase
{
    private $collectionResolverFactory;
    private $readStageProphecy;
    private $denyAccessStageProphecy;
    private $serializeStageProphecy;
    private $queryResolverLocatorProphecy;
    private $resourceMetadataFactoryProphecy;
    private $requestStackProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->readStageProphecy = $this->prophesize(ReadStageInterface::class);
        $this->denyAccessStageProphecy = $this->prophesize(DenyAccessStageInterface::class);
        $this->serializeStageProphecy = $this->prophesize(SerializeStageInterface::class);
        $this->queryResolverLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $this->requestStackProphecy = $this->prophesize(RequestStack::class);

        $this->collectionResolverFactory = new CollectionResolverFactory(
            $this->readStageProphecy->reveal(),
            $this->denyAccessStageProphecy->reveal(),
            $this->serializeStageProphecy->reveal(),
            $this->queryResolverLocatorProphecy->reveal(),
            $this->resourceMetadataFactoryProphecy->reveal(),
            $this->requestStackProphecy->reveal()
        );
    }

    public function testResolve(): void
    {
        $resourceClass = 'stdClass';
        $rootClass = 'rootClass';
        $operationName = 'query';
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => true, 'is_mutation' => false];

        $request = new Request();
        $attributesParameterBagProphecy = $this->prophesize(ParameterBag::class);
        $attributesParameterBagProphecy->get('_graphql_collections_args', [])->willReturn(['collection_args']);
        $attributesParameterBagProphecy->set('_graphql_collections_args', [$resourceClass => $args, 'collection_args'])->shouldBeCalled();
        $request->attributes = $attributesParameterBagProphecy->reveal();
        $this->requestStackProphecy->getCurrentRequest()->willReturn($request);

        $readStageCollection = [new \stdClass()];
        $this->readStageProphecy->apply($resourceClass, $rootClass, $operationName, $resolverContext)->shouldBeCalled()->willReturn($readStageCollection);

        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadata());

        $this->denyAccessStageProphecy->apply($resourceClass, $operationName, $resolverContext + [
            'extra_variables' => [
                'object' => $readStageCollection,
                'previous_object' => $readStageCollection,
            ],
        ])->shouldBeCalled();

        $serializeStageData = ['serialized'];
        $this->serializeStageProphecy->apply($readStageCollection, $resourceClass, $operationName, $resolverContext)->shouldBeCalled()->willReturn($serializeStageData);

        $this->assertSame($serializeStageData, ($this->collectionResolverFactory)($resourceClass, $rootClass, $operationName)($source, $args, null, $info));
    }

    public function testResolveNullResourceClass(): void
    {
        $resourceClass = null;
        $rootClass = 'rootClass';
        $operationName = 'query';
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();

        $this->assertNull(($this->collectionResolverFactory)($resourceClass, $rootClass, $operationName)($source, $args, null, $info));
    }

    public function testResolveNullRootClass(): void
    {
        $resourceClass = 'stdClass';
        $rootClass = null;
        $operationName = 'query';
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();

        $this->assertNull(($this->collectionResolverFactory)($resourceClass, $rootClass, $operationName)($source, $args, null, $info));
    }

    public function testResolveBadReadStageCollection(): void
    {
        $resourceClass = 'stdClass';
        $rootClass = 'rootClass';
        $operationName = 'query';
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => true, 'is_mutation' => false];

        $readStageCollection = new \stdClass();
        $this->readStageProphecy->apply($resourceClass, $rootClass, $operationName, $resolverContext)->shouldBeCalled()->willReturn($readStageCollection);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Collection from read stage should be iterable.');

        ($this->collectionResolverFactory)($resourceClass, $rootClass, $operationName)($source, $args, null, $info);
    }

    public function testResolveCustom(): void
    {
        $resourceClass = 'stdClass';
        $rootClass = 'rootClass';
        $operationName = 'query';
        $source = ['source'];
        $args = ['args'];
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $resolverContext = ['source' => $source, 'args' => $args, 'info' => $info, 'is_collection' => true, 'is_mutation' => false];

        $readStageCollection = [new \stdClass()];
        $this->readStageProphecy->apply($resourceClass, $rootClass, $operationName, $resolverContext)->shouldBeCalled()->willReturn($readStageCollection);

        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(
            (new ResourceMetadata())->withGraphql([$operationName => ['collection_query' => 'query_resolver_id']])
        );

        $customCollection = [new \stdClass()];
        $customCollection[0]->field = 'foo';
        $this->queryResolverLocatorProphecy->get('query_resolver_id')->shouldBeCalled()->willReturn(function () use ($customCollection) {
            return $customCollection;
        });

        $this->denyAccessStageProphecy->apply($resourceClass, $operationName, $resolverContext + [
            'extra_variables' => [
                'object' => $customCollection,
                'previous_object' => $customCollection,
            ],
        ])->shouldBeCalled();

        $serializeStageData = ['serialized'];
        $this->serializeStageProphecy->apply($customCollection, $resourceClass, $operationName, $resolverContext)->shouldBeCalled()->willReturn($serializeStageData);

        $this->assertSame($serializeStageData, ($this->collectionResolverFactory)($resourceClass, $rootClass, $operationName)($source, $args, null, $info));
    }
}
