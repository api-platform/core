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

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\GraphQl\Resolver\Factory\ItemMutationResolverFactory;
use ApiPlatform\Core\GraphQl\Resolver\Factory\ResolverFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class ItemMutationResolverFactoryTest extends TestCase
{
    public function testCreateItemMutationResolverNoItem()
    {
        $this->expectException(Error::class);

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->remove(Argument::any())->shouldNotBeCalled();

        $resolverFactory = $this->createItemMutationResolverFactory(null, $dataPersisterProphecy);
        $resolver = $resolverFactory(Dummy::class, Dummy::class, 'delete');

        $resolveInfo = new ResolveInfo('', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $resolver(null, ['input' => ['id' => '/dummies/3', 'clientMutationId' => '1936']], null, $resolveInfo);
    }

    public function testCreateItemDeleteMutationResolver()
    {
        $dummy = new Dummy();

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->remove($dummy)->shouldBeCalled();

        $resolverFactory = $this->createItemMutationResolverFactory($dummy, $dataPersisterProphecy);
        $resolver = $resolverFactory(Dummy::class, null, 'delete');

        $resolveInfo = $this->prophesize(ResolveInfo::class);
        $resolveInfo->getFieldSelection(PHP_INT_MAX)->shouldBeCalled()->willReturn(['shortName' => []]);

        $this->assertEquals(['shortName' => ['id' => '/dummies/3'], 'clientMutationId' => '1936'], $resolver(null, ['input' => ['id' => '/dummies/3', 'clientMutationId' => '1936']], null, $resolveInfo->reveal()));
    }

    private function createItemMutationResolverFactory($item, $dataPersisterProphecy): ResolverFactoryInterface
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $getItemFromIri = $iriConverterProphecy->getItemFromIri('/dummies/3', ['attributes' => []]);
        null === $item ? $getItemFromIri->willThrow(new ItemNotFoundException()) : $getItemFromIri->willReturn($item);

        $normalizerProphecy = $this->prophesize(NormalizerInterface::class)->willImplement(DenormalizerInterface::class);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Argument::type('string'))->willReturn(new ResourceMetadata('shortName'));

        return new ItemMutationResolverFactory(
            $iriConverterProphecy->reveal(),
            $dataPersisterProphecy->reveal(),
            $normalizerProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal()
        );
    }
}
