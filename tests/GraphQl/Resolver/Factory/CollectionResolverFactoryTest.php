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

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\GraphQl\Resolver\Factory\CollectionResolverFactory;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
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

        $resolveInfo = new ResolveInfo([]);
        $resolveInfo->fieldName = 'relatedDummies';
        $resolveInfo->fieldNodes = [];

        $this->assertEquals($expected, $resolver(null, [], null, $resolveInfo));
    }

    public function paginationProvider(): array
    {
        return [
            [true, ['edges' => [], 'pageInfo' => ['endCursor' => null, 'hasNextPage' => false]]],
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

        $resolveInfo = new ResolveInfo([]);
        $resolveInfo->fieldName = 'rootProperty';
        $resolveInfo->fieldNodes = [];

        $this->assertEquals(['normalizedObject1', 'normalizedObject2'], $resolver(null, [], null, $resolveInfo));
    }

    /**
     * @dataProvider subresourceProvider
     */
    public function testCreateSubresourceCollectionResolverNoPagination(array $subcollection, array $expected)
    {
        $factory = $this->createCollectionResolverFactory([
            'Object1',
            'Object2',
        ], $subcollection, ['id' => 1], false);

        $resolver = $factory(RelatedDummy::class, Dummy::class, 'operationName');

        $resolveInfo = new ResolveInfo([]);
        $resolveInfo->fieldName = 'relatedDummies';
        $resolveInfo->fieldNodes = [];

        $dummy = new Dummy();
        $dummy->setId(1);

        $source = [
            'relatedDummies' => [],
            ItemNormalizer::ITEM_KEY => serialize($dummy),
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
        ], [], [], true);

        $resolver = $factory(RelatedDummy::class, Dummy::class, 'operationName');

        $resolveInfo = new ResolveInfo([]);
        $resolveInfo->fieldName = 'relatedDummies';
        $resolveInfo->fieldNodes = [];

        if ('$bad$' === $cursor) {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('Cursor $bad$ is invalid');
        }

        $this->assertEquals(
            [
                'edges' => [['node' => 'normalizedObject1', 'cursor' => $expectedCursors[0]], ['node' => 'normalizedObject2', 'cursor' => $expectedCursors[1]]],
                'pageInfo' => ['endCursor' => null, 'hasNextPage' => false],
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

        $resolverFactory = $this->createCollectionResolverFactory($collectionPaginatorProphecy->reveal(), [], [], true);
        $resolver = $resolverFactory(RelatedDummy::class, Dummy::class, 'operationName');

        $resolveInfo = new ResolveInfo([]);
        $resolveInfo->fieldName = 'relatedDummies';
        $resolveInfo->fieldNodes = [];

        $this->assertEquals(
            ['edges' => [['node' => 'normalizedObject1', 'cursor' => 'Mg==']], 'pageInfo' => ['endCursor' => 'MTY=', 'hasNextPage' => true]],
            $resolver(null, ['after' => 'MQ=='], null, $resolveInfo)
        );
    }

    /**
     * @param array|\Iterator $collection
     */
    private function createCollectionResolverFactory($collection, array $subcollection, array $identifiers, bool $paginationEnabled): CollectionResolverFactory
    {
        $collectionDataProviderProphecy = $this->prophesize(CollectionDataProviderInterface::class);
        $collectionDataProviderProphecy->getCollection(Dummy::class, null, ['groups' => ['foo'], 'attributes' => []])->willReturn($collection);
        $collectionDataProviderProphecy->getCollection(RelatedDummy::class, null, ['groups' => ['foo'], 'attributes' => []])->willReturn($collection);

        $subresourceDataProviderProphecy = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProviderProphecy->getSubresource(RelatedDummy::class, $identifiers, [
            'property' => 'relatedDummies',
            'identifiers' => [['id', Dummy::class]],
            'collection' => true,
            'groups' => ['foo'],
            'attributes' => [],
        ])->willReturn($subcollection);

        $normalizerProphecy = $this->prophesize(NormalizerInterface::class);

        if (\is_array($collection)) {
            foreach ($collection as $object) {
                $normalizerProphecy->normalize($object, Argument::cetera())->willReturn('normalized'.$object);
            }
        } else {
            $normalizerProphecy->normalize($collection->current(), Argument::cetera())->willReturn('normalized'.$collection->current());
        }

        foreach ($subcollection as $object) {
            $normalizerProphecy->normalize($object, Argument::cetera())->willReturn('normalized'.$object);
        }

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromItem(Argument::type(Dummy::class))->willReturn($identifiers);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('Dummy', null, null, null, null, ['normalization_context' => ['groups' => ['foo']]]));
        $resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn(new ResourceMetadata('RelatedDummy', null, null, null, null, ['normalization_context' => ['groups' => ['foo']]]));

        $request = new Request();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new CollectionResolverFactory(
            $collectionDataProviderProphecy->reveal(),
            $subresourceDataProviderProphecy->reveal(),
            $normalizerProphecy->reveal(),
            $identifiersExtractorProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            null,
            $requestStack,
            $paginationEnabled
        );
    }
}
