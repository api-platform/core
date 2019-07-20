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

use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\GraphQl\Resolver\Factory\CollectionResolverFactory;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CollectionResolverFactoryTest extends TestCase
{
    /**
     * @dataProvider paginationProvider
     */
    public function testCreateCollectionResolverNoCollection(bool $paginationEnabled, array $expected)
    {
        $factory = $this->createCollectionResolverFactory([], [], ['id' => 1], $paginationEnabled);
        $resolver = $factory(RelatedDummy::class, Dummy::class, 'operationName');

        $resolveInfo = new ResolveInfo('relatedDummies', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $this->assertEquals($expected, $resolver(null, [], null, $resolveInfo));
    }

    public function paginationProvider(): array
    {
        return [
            [true, ['totalCount' => 0., 'edges' => [], 'pageInfo' => ['startCursor' => null, 'endCursor' => null, 'hasNextPage' => false, 'hasPreviousPage' => false]]],
            [false, []],
        ];
    }

    public function testCreateCollectionResolverNoPagination()
    {
        $factory = $this->createCollectionResolverFactory([
            'Object1',
            'Object2',
        ], [], [], false);

        $resolver = $factory(RelatedDummy::class, Dummy::class, 'operationName');

        $resolveInfo = new ResolveInfo('rootProperty', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $this->assertEquals(['normalizedObject1', 'normalizedObject2'], $resolver(null, [], null, $resolveInfo));
    }

    /**
     * @dataProvider subresourceProvider
     */
    public function testCreateSubresourceCollectionResolverNoPagination(array $subcollection, array $expected)
    {
        $identifiers = ['id' => 1];
        $factory = $this->createCollectionResolverFactory([
            'Object1',
            'Object2',
        ], $subcollection, $identifiers, false);

        $resolver = $factory(RelatedDummy::class, Dummy::class, 'operationName');

        $resolveInfo = new ResolveInfo('relatedDummies', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $source = [
            'relatedDummies' => [],
            ItemNormalizer::ITEM_IDENTIFIERS_KEY => $identifiers,
        ];

        $this->assertEquals($expected, $resolver($source, [], null, $resolveInfo));
    }

    public function subresourceProvider(): array
    {
        return [
            [['Subobject1', 'Subobject2'], ['normalizedSubobject1', 'normalizedSubobject2']],
            [[], []],
        ];
    }

    /**
     * @dataProvider cursorProvider
     */
    public function testCreateCollectionResolver(string $cursor, array $expectedCursors)
    {
        $factory = $this->createCollectionResolverFactory([
            'Object1',
            'Object2',
        ], [], [], true, $cursor);

        $resolver = $factory(RelatedDummy::class, Dummy::class, 'operationName');

        $resolveInfo = new ResolveInfo('relatedDummies', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        if ('$bad$' === $cursor) {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('Cursor $bad$ is invalid');
        }

        $this->assertEquals(
            [
                'totalCount' => 2.0,
                'edges' => [['node' => 'normalizedObject1', 'cursor' => $expectedCursors[0]], ['node' => 'normalizedObject2', 'cursor' => $expectedCursors[1]]],
                'pageInfo' => ['startCursor' => $expectedCursors[0], 'endCursor' => $expectedCursors[1], 'hasNextPage' => false, 'hasPreviousPage' => false],
            ],
            $resolver(null, ['after' => $cursor], null, $resolveInfo)
        );
    }

    public function cursorProvider(): array
    {
        return [
            ['$bad$', ['MA==', 'MQ==']],
            ['MQ==', ['Mg==', 'Mw==']],
        ];
    }

    public function testCreatePaginatorCollectionResolver()
    {
        $collectionPaginatorProphecy = $this->prophesize(PaginatorInterface::class)->willImplement(\Iterator::class);
        $collectionPaginatorProphecy->rewind()->shouldBeCalled();
        $collectionPaginatorProphecy->valid()->willReturn(true, false);
        $collectionPaginatorProphecy->key()->willReturn(0);
        $collectionPaginatorProphecy->current()->willReturn('Object1');
        $collectionPaginatorProphecy->next()->willReturn();
        $collectionPaginatorProphecy->getTotalItems()->willReturn(17);
        $collectionPaginatorProphecy->getCurrentPage()->willReturn(3);
        $collectionPaginatorProphecy->getLastPage()->willReturn(7);
        $collectionPaginatorProphecy->count()->willReturn(8);
        $collectionPaginatorProphecy->getItemsPerPage()->willReturn(8);

        $cursor = 'MQ==';
        $resolverFactory = $this->createCollectionResolverFactory($collectionPaginatorProphecy->reveal(), [], [], true, $cursor);
        $resolver = $resolverFactory(RelatedDummy::class, Dummy::class, 'operationName');

        $resolveInfo = new ResolveInfo('relatedDummies', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $this->assertEquals(
            ['edges' => [['node' => 'normalizedObject1', 'cursor' => 'Mg==']], 'pageInfo' => ['startCursor' => 'Mg==', 'endCursor' => 'OQ==', 'hasNextPage' => true, 'hasPreviousPage' => true], 'totalCount' => 17.],
            $resolver(null, ['after' => $cursor], null, $resolveInfo)
        );
    }

    public function testCreateCollectionResolverCustom(): void
    {
        $factory = $this->createCollectionResolverFactory([
            'Object1',
            'Object2',
        ], [], [], true, null, ['custom_query' => ['collection_query' => 'query_resolver_id']]);

        $resolver = $factory(RelatedDummy::class, Dummy::class, 'custom_query');

        $resolveInfo = new ResolveInfo('relatedDummies', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $this->assertEquals(
            [
                'totalCount' => 2.0,
                'edges' => [['node' => 'normalizedReturnedObject1', 'cursor' => 'MA=='], ['node' => 'normalizedReturnedObject2', 'cursor' => 'MQ==']],
                'pageInfo' => ['startCursor' => 'MA==', 'endCursor' => 'MQ==', 'hasNextPage' => false, 'hasPreviousPage' => false],
            ],
            $resolver(null, [], null, $resolveInfo)
        );
    }

    public function testCreateCollectionNoRead(): void
    {
        $factory = $this->createCollectionResolverFactory([
            'Object1',
            'Object2',
        ], [], [], false, null, ['query' => ['read' => false]]);

        $resolver = $factory(RelatedDummy::class, Dummy::class);

        $resolveInfo = new ResolveInfo('relatedDummies', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $this->assertEquals(
            [],
            $resolver(null, [], null, $resolveInfo)
        );
    }

    public function testCreateSubresourceCollectionNoRead(): void
    {
        $identifiers = ['id' => 1];
        $factory = $this->createCollectionResolverFactory([
            'Object1',
            'Object2',
        ], ['Subobject1', 'Subobject2'], $identifiers, false, null, ['query' => ['read' => false]]);

        $resolver = $factory(RelatedDummy::class, Dummy::class, 'query');

        $resolveInfo = new ResolveInfo('relatedDummies', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $source = [
            'relatedDummies' => [],
            ItemNormalizer::ITEM_IDENTIFIERS_KEY => $identifiers,
        ];

        $this->assertEquals([], $resolver($source, [], null, $resolveInfo));
    }

    public function testCreateCollectionNoSerialize(): void
    {
        $factory = $this->createCollectionResolverFactory([
            'Object1',
            'Object2',
        ], [], [], false, null, ['custom_query' => ['collection_query' => 'query_resolver_id', 'serialize' => false]]);

        $resolver = $factory(RelatedDummy::class, Dummy::class, 'custom_query');

        $resolveInfo = new ResolveInfo('relatedDummies', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $this->assertEquals(
            [],
            $resolver(null, [], null, $resolveInfo)
        );
    }

    public function testCreateCollectionNoSerializeNoPagination(): void
    {
        $factory = $this->createCollectionResolverFactory([
            'Object1',
            'Object2',
        ], [], [], true, null, ['custom_query' => ['collection_query' => 'query_resolver_id', 'serialize' => false]]);

        $resolver = $factory(RelatedDummy::class, Dummy::class, 'custom_query');

        $resolveInfo = new ResolveInfo('relatedDummies', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $this->assertEquals(
            [
                'totalCount' => 0.0,
                'edges' => [],
                'pageInfo' => ['startCursor' => null, 'endCursor' => null, 'hasNextPage' => false, 'hasPreviousPage' => false],
            ],
            $resolver(null, [], null, $resolveInfo)
        );
    }

    /**
     * @param array|\Iterator $collection
     */
    private function createCollectionResolverFactory($collection, array $subcollection, array $identifiers, bool $paginationEnabled, string $cursor = null, array $graphqlAttribute = []): CollectionResolverFactory
    {
        $collectionDataProviderProphecy = $this->prophesize(CollectionDataProviderInterface::class);

        $filters = $cursor ? ['after' => $cursor] : [];
        $collectionDataProviderProphecy->getCollection(RelatedDummy::class, null, ['groups' => ['foo'], 'attributes' => [], 'filters' => $filters, 'graphql' => true])->willReturn($paginationEnabled && \is_array($collection) ? new ArrayPaginator($collection, 0, \count($collection)) : $collection);

        $subresourceDataProviderProphecy = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProviderProphecy->getSubresource(RelatedDummy::class, $identifiers, [
            'property' => 'relatedDummies',
            'identifiers' => [['id', Dummy::class]],
            'collection' => true,
            'groups' => ['foo'],
            'attributes' => [],
            'filters' => [],
            'graphql' => true,
        ])->willReturn($subcollection);

        $queryResolverLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $returnedCollection = ['ReturnedObject1', 'ReturnedObject2'];
        $queryResolverLocatorProphecy->get('query_resolver_id')->willReturn(function () use ($returnedCollection) {
            return new ArrayPaginator($returnedCollection, 0, 2);
        });

        $normalizerProphecy = $this->prophesize(NormalizerInterface::class);

        if (\is_array($collection)) {
            foreach ($collection as $object) {
                $normalizerProphecy->normalize($object, Argument::cetera())->willReturn('normalized'.$object);
            }
        } else {
            $normalizerProphecy->normalize($collection->current(), Argument::cetera())->willReturn('normalized'.$collection->current());
        }

        foreach ($returnedCollection as $returnedObject) {
            $normalizerProphecy->normalize($returnedObject, Argument::cetera())->willReturn('normalized'.$returnedObject);
        }

        foreach ($subcollection as $object) {
            $normalizerProphecy->normalize($object, Argument::cetera())->willReturn('normalized'.$object);
        }

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn(new ResourceMetadata('RelatedDummy', null, null, null, null, ['normalization_context' => ['groups' => ['foo']]], [], $graphqlAttribute));

        $request = new Request();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new CollectionResolverFactory(
            $collectionDataProviderProphecy->reveal(),
            $subresourceDataProviderProphecy->reveal(),
            $queryResolverLocatorProphecy->reveal(),
            $normalizerProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            null,
            $requestStack,
            $paginationEnabled
        );
    }
}
