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
use ApiPlatform\Core\Bridge\Graphql\Resolver\ItemMutationResolverFactory;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class ItemMutationResolverFactoryTest extends TestCase
{
    /**
     * @expectedException \GraphQL\Error\Error
     */
    public function testCreateItemMutationResolverNoItem()
    {
        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->remove(Argument::any())->shouldNotBeCalled();
        $mockedItemMutationResolverFactory = $this->mockItemMutationResolverFactory(null, ['id' => 3], 3, $dataPersisterProphecy);

        $resolver = $mockedItemMutationResolverFactory->createItemMutationResolver('resourceClass', 'delete');

        $resolveInfoProphecy = $this->prophesize(ResolveInfo::class);

        $resolver(null, ['input' => ['id' => 3]], null, $resolveInfoProphecy->reveal());
    }

    public function testCreateItemDeleteMutationResolver()
    {
        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->remove('Item1')->shouldBeCalled();
        $mockedItemMutationResolverFactory = $this->mockItemMutationResolverFactory('Item1', ['id' => 3], 3, $dataPersisterProphecy);

        $resolver = $mockedItemMutationResolverFactory->createItemMutationResolver('resourceClass', 'delete');

        $resolveInfoProphecy = $this->prophesize(ResolveInfo::class);

        $this->assertEquals(['id' => 3], $resolver(null, ['input' => ['id' => 3]], null, $resolveInfoProphecy->reveal()));
    }

    public function testCreateCompositeSimpleIdentifiersMutationItemResolver()
    {
        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->remove('Item1')->shouldBeCalled();
        $mockedItemMutationResolverFactory = $this->mockItemMutationResolverFactory('Item1', ['id1' => 1, 'id2' => 2], 'id1=1;id2=2', $dataPersisterProphecy);

        $resolver = $mockedItemMutationResolverFactory->createItemMutationResolver('resourceClass', 'delete');

        $resolveInfoProphecy = $this->prophesize(ResolveInfo::class);

        $this->assertEquals(['id1' => 1, 'id2' => 2], $resolver(null, ['input' => ['id1' => 1, 'id2' => 2]], null, $resolveInfoProphecy->reveal()));
    }

    public function testCreateCompositeIdentifiersMutationItemResolver()
    {
        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->remove('Item1')->shouldBeCalled();
        $mockedItemMutationResolverFactory = $this->mockItemMutationResolverFactory('Item1', ['relation1' => 1, 'relation2' => 2], 'relation1=1;relation2=2', $dataPersisterProphecy);

        $resolver = $mockedItemMutationResolverFactory->createItemMutationResolver('resourceClass', 'delete');

        $resolveInfoProphecy = $this->prophesize(ResolveInfo::class);

        $this->assertEquals(['relation1' => ['id' => 1], 'relation2' => ['id' => 2]], $resolver(null, ['input' => ['relation1' => ['id' => 1], 'relation2' => ['id' => 2]]], null, $resolveInfoProphecy->reveal()));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Composite identifiers are not allowed for a resource already used as a composite identifier
     */
    public function testCreateRecursiveCompositeIdentifiersItemMutationResolver()
    {
        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->remove(Argument::any())->shouldNotBeCalled();
        $mockedItemMutationResolverFactory = $this->mockItemMutationResolverFactory('Item1', ['relation1' => ['link1' => 1, 'link2' => 3], 'relation2' => 2], null, $dataPersisterProphecy);

        $resolver = $mockedItemMutationResolverFactory->createItemMutationResolver('resourceClass', 'delete');

        $resolveInfoProphecy = $this->prophesize(ResolveInfo::class);

        $resolver(null, ['input' => ['relation1' => ['link1' => ['id' => 1], 'link2' => ['id' => 3]], 'relation2' => ['id' => 2]]], null, $resolveInfoProphecy->reveal());
    }

    private function mockItemMutationResolverFactory($item, array $identifiers, $flatId, ObjectProphecy $dataPersisterProphecy): ItemMutationResolverFactory
    {
        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromResourceClass('resourceClass')->willReturn(array_keys($identifiers));

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProviderProphecy->getItem('resourceClass', $flatId)->willReturn($item);

        return new ItemMutationResolverFactory(
            $identifiersExtractorProphecy->reveal(),
            $itemDataProviderProphecy->reveal(),
            $dataPersisterProphecy->reveal()
        );
    }
}
