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

namespace ApiPlatform\Core\Tests\Graphql\Resolver;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Graphql\Resolver\ItemMutationResolverFactory;
use ApiPlatform\Core\Graphql\Resolver\ResolverFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

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

        $resolverFactory = $this->createItemMutationResolverFactory(null, $dataPersisterProphecy);
        $resolver = $resolverFactory(Dummy::class, Dummy::class, 'delete');

        $resolver(null, ['input' => ['id' => '/dummies/3']], null, new ResolveInfo([]));
    }

    public function testCreateItemDeleteMutationResolver()
    {
        $dummy = new Dummy();

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->remove($dummy)->shouldBeCalled();
        $resolverFactory = $this->createItemMutationResolverFactory($dummy, $dataPersisterProphecy);
        $resolver = $resolverFactory(Dummy::class, null, 'delete');

        $this->assertEquals(['id' => '/dummies/3'], $resolver(null, ['input' => ['id' => '/dummies/3']], null, new ResolveInfo([])));
    }

    private function createItemMutationResolverFactory($item, ObjectProphecy $dataPersisterProphecy): ResolverFactoryInterface
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $getItemFromIri = $iriConverterProphecy->getItemFromIri('/dummies/3');
        null === $item ? $getItemFromIri->willThrow(new ItemNotFoundException()) : $getItemFromIri->willReturn($item);

        $normalizerProphecy = $this->prophesize(NormalizerInterface::class)->willImplement(DenormalizerInterface::class);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Argument::type('string'))->willReturn(new ResourceMetadata());

        return new ItemMutationResolverFactory(
            $iriConverterProphecy->reveal(),
            $dataPersisterProphecy->reveal(),
            $normalizerProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal()
        );
    }
}
