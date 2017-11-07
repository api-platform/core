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
use ApiPlatform\Core\Bridge\Graphql\Resolver\ItemResolverFactory;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class ItemResolverFactoryTest extends TestCase
{
    public function testCreateItemResolverNoItem()
    {
        $mockedItemResolverFactory = $this->mockItemResolverFactory(null, null, [], null);

        $resolver = $mockedItemResolverFactory->createItemResolver('resourceClass', 'rootClass', 'foo');

        $resolveInfoProphecy = $this->prophesize(ResolveInfo::class);
        $resolveInfoProphecy->fieldName = 'rootProperty';

        $this->assertNull($resolver(null, ['id' => 3], null, $resolveInfoProphecy->reveal()));
    }

    public function testCreateItemResolver()
    {
        $mockedItemResolverFactory = $this->mockItemResolverFactory('Item1', null, ['id' => 3], 3);

        $resolver = $mockedItemResolverFactory->createItemResolver('resourceClass', 'rootClass', 'foo');

        $resolveInfoProphecy = $this->prophesize(ResolveInfo::class);
        $resolveInfoProphecy->fieldName = 'rootProperty';

        $this->assertEquals('normalizedItem1', $resolver(null, ['id' => 3], null, $resolveInfoProphecy->reveal()));
    }

    public function testCreateCompositeIdentifiersItemResolver()
    {
        $mockedItemResolverFactory = $this->mockItemResolverFactory('Item1', null, ['relation1' => 1, 'relation2' => 2], 'relation1=1;relation2=2');

        $resolver = $mockedItemResolverFactory->createItemResolver('resourceClass', 'rootClass', 'foo');

        $resolveInfoProphecy = $this->prophesize(ResolveInfo::class);
        $resolveInfoProphecy->fieldName = 'rootProperty';

        $this->assertEquals('normalizedItem1', $resolver(null, ['relation1' => ['id' => 1], 'relation2' => ['id' => 2]], null, $resolveInfoProphecy->reveal()));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Composite identifiers are not allowed for a resource already used as a composite identifier
     */
    public function testCreateRecursiveCompositeIdentifiersItemResolver()
    {
        $mockedItemResolverFactory = $this->mockItemResolverFactory('Item1', null, ['relation1' => ['link1' => 1, 'link2' => 3], 'relation2' => 2], null);

        $resolver = $mockedItemResolverFactory->createItemResolver('resourceClass', 'rootClass', 'foo');

        $resolveInfoProphecy = $this->prophesize(ResolveInfo::class);
        $resolveInfoProphecy->fieldName = 'rootProperty';

        $resolver(null, ['relation1' => ['link1' => ['id' => 1], 'link2' => ['id' => 3]], 'relation2' => ['id' => 2]], null, $resolveInfoProphecy->reveal());
    }

    /**
     * @dataProvider subresourceProvider
     */
    public function testCreateSubresourceItemResolver($subresource, $expected)
    {
        $mockedItemResolverFactory = $this->mockItemResolverFactory('Item1', $subresource, ['rootIdentifier' => 'valueRootIdentifier'], null);

        $resolver = $mockedItemResolverFactory->createItemResolver('subresourceClass', 'rootClass', 'foo');

        $resolveInfoProphecy = $this->prophesize(ResolveInfo::class);
        $resolveInfoProphecy->fieldName = 'rootProperty';

        $this->assertEquals($expected, $resolver(['rootProperty' => true, 'rootIdentifier' => 'valueRootIdentifier'], [], null, $resolveInfoProphecy->reveal()));
    }

    public function subresourceProvider()
    {
        return [['Subitem1', 'normalizedSubitem1'], [null, null]];
    }

    private function mockItemResolverFactory($item, $subitem, array $identifiers, $flatId): ItemResolverFactory
    {
        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProviderProphecy->getItem('resourceClass', $flatId)->willReturn($item);

        $subresourceDataProviderProphecy = $this->prophesize(SubresourceDataProviderInterface::class);
        $subresourceDataProviderProphecy->getSubresource('subresourceClass', $identifiers, [
            'property' => 'rootProperty',
            'identifiers' => [['rootIdentifier', 'rootClass']],
            'collection' => false,
        ])->willReturn($subitem);

        $normalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $normalizerProphecy->normalize($item, Argument::cetera())->willReturn('normalized'.$item);
        $normalizerProphecy->normalize($subitem, Argument::cetera())->willReturn('normalized'.$subitem);

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass('rootClass')->willReturn(array_keys($identifiers));

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('resourceClass')->willReturn(new ResourceMetadata('resourceClass', null, null, null, null, ['normalization_context' => ['groups' => ['foo']]]));
        $resourceMetadataFactoryProphecy->create('subresourceClass')->willReturn(new ResourceMetadata('subresourceClass', null, null, null, null, ['normalization_context' => ['groups' => ['foo']]]));

        return new ItemResolverFactory(
            $itemDataProviderProphecy->reveal(),
            $subresourceDataProviderProphecy->reveal(),
            $normalizerProphecy->reveal(),
            $identifiersExtractorProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal()
        );
    }
}
