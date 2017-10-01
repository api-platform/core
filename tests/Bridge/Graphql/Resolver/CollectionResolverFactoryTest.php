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

namespace ApiPlatform\Core\Tests\Bridge\Graphql\Resolver;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Bridge\Graphql\Resolver\CollectionResolverFactory;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class CollectionResolverFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider paginationProvider
     */
    public function testCreateCollectionResolverNoCollection($paginationEnabled, $expected)
    {
        $mockedCollectionResolverFactory = $this->mockCollectionResolverFactory([], [], [], $paginationEnabled);

        $resolver = $mockedCollectionResolverFactory->createCollectionResolver('resourceClass', 'rootClass');
        $resolveInfoProphecy = $this->prophesize(ResolveInfo::class);
        $resolveInfoProphecy->fieldName = 'rootProperty';
        $this->assertEquals($expected, $resolver(null, [], null, $resolveInfoProphecy->reveal()));
    }

    public function testCreateCollectionResolverNoPagination()
    {
        $mockedCollectionResolverFactory = $this->mockCollectionResolverFactory([
            'Object1',
            'Object2',
        ], [], [], false);

        $resolver = $mockedCollectionResolverFactory->createCollectionResolver('resourceClass', 'rootClass');
        $resolveInfoProphecy = $this->prophesize(ResolveInfo::class);
        $resolveInfoProphecy->fieldName = 'rootProperty';
        $this->assertEquals(['normalizedObject1', 'normalizedObject2'], $resolver(null, [], null, $resolveInfoProphecy->reveal()));
    }

    /**
     * @dataProvider subresourceProvider
     */
    public function testCreateSubresourceCollectionResolverNoPagination($subresource, $expected)
    {
        $mockedCollectionResolverFactory = $this->mockCollectionResolverFactory([
            'Object1',
            'Object2',
        ], $subresource, ['rootIdentifier' => 'valueRootIdentifier'], false);

        $resolver = $mockedCollectionResolverFactory->createCollectionResolver('subresourceClass', 'rootClass');
        $resolveInfoProphecy = $this->prophesize(ResolveInfo::class);
        $resolveInfoProphecy->fieldName = 'rootProperty';
        $this->assertEquals($expected, $resolver(['rootProperty' => true, 'rootIdentifier' => 'valueRootIdentifier'], [], null, $resolveInfoProphecy->reveal()));
    }

    /**
     * @dataProvider cursorProvider
     */
    public function testCreateCollectionResolver($cursor, $expectedCursors)
    {
        $mockedCollectionResolverFactory = $this->mockCollectionResolverFactory([
            'Object1',
            'Object2',
        ], [], [], true);

        $resolver = $mockedCollectionResolverFactory->createCollectionResolver('resourceClass', 'rootClass');
        $resolveInfoProphecy = $this->prophesize(ResolveInfo::class);
        $resolveInfoProphecy->fieldName = 'rootProperty';
        if ('$bad$' === $cursor) {
            $this->expectException('\Exception');
            $this->expectExceptionMessage('Cursor $bad$ is invalid');
        }
        $this->assertEquals(
            [
                'edges' => [['node' => 'normalizedObject1', 'cursor' => $expectedCursors[0]], ['node' => 'normalizedObject2', 'cursor' => $expectedCursors[1]]],
                'pageInfo' => ['endCursor' => null, 'hasNextPage' => false],
            ],
            $resolver(null, ['after' => $cursor], null, $resolveInfoProphecy->reveal())
        );
    }

    public function testCreatePaginatorCollectionResolver()
    {
        $collectionPaginatorProphecy = $this->prophesize(PaginatorInterface::class);
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

        $mockedCollectionResolverFactory = $this->mockCollectionResolverFactory($collectionPaginatorProphecy->reveal(), [], [], true);

        $resolver = $mockedCollectionResolverFactory->createCollectionResolver('resourceClass', 'rootClass');
        $resolveInfoProphecy = $this->prophesize(ResolveInfo::class);
        $resolveInfoProphecy->fieldName = 'rootProperty';
        $this->assertEquals(
            ['edges' => [['node' => 'normalizedObject1', 'cursor' => 'Mg==']], 'pageInfo' => ['endCursor' => 'MTY=', 'hasNextPage' => true]],
            $resolver(null, ['after' => 'MQ=='], null, $resolveInfoProphecy->reveal())
        );
    }

    public function paginationProvider()
    {
        return [[true, ['edges' => [], 'pageInfo' => ['endCursor' => null, 'hasNextPage' => false]]], [false, []]];
    }

    public function cursorProvider()
    {
        return [['$bad$', ['MA==', 'MQ==']], ['MQ==', ['Mg==', 'Mw==']]];
    }

    public function subresourceProvider()
    {
        return [[['Subobject1', 'Subobject2'], ['normalizedSubobject1', 'normalizedSubobject2']], [[], []]];
    }

    private function mockCollectionResolverFactory($collection, array $subcollection, array $identifiers, bool $paginationEnabled): CollectionResolverFactory
    {
        $collectionDataProviderProphecy = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProviderProphecy->getCollection('resourceClass')->willReturn($collection);
        $subresourceDataProviderProphecy = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProviderProphecy->getSubresource('subresourceClass', $identifiers, [
            'property' => 'rootProperty',
            'identifiers' => [['rootIdentifier', 'rootClass']],
            'collection' => true,
        ])->willReturn($subcollection);
        $normalizerProphecy = $this->prophesize(NormalizerInterface::class);
        if (!is_array($collection)) {
            $normalizerProphecy->normalize($collection->current(), Argument::cetera())->willReturn('normalized'.$collection->current());
        } else {
            foreach ($collection as $object) {
                $normalizerProphecy->normalize($object, Argument::cetera())->willReturn('normalized'.$object);
            }
        }
        foreach ($subcollection as $object) {
            $normalizerProphecy->normalize($object, Argument::cetera())->willReturn('normalized'.$object);
        }
        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass('rootClass')->willReturn(array_keys($identifiers));
        $request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new CollectionResolverFactory(
            $collectionDataProviderProphecy->reveal(),
            $subresourceDataProviderProphecy->reveal(),
            $normalizerProphecy->reveal(),
            $identifiersExtractorProphecy->reveal(),
            $requestStack,
            $paginationEnabled
        );
    }
}
