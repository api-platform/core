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
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class ItemMutationResolverFactoryTest extends TestCase
{
    private $itemMutationResolverFactory;
    private $iriConverterProphecy;
    private $dataPersisterProphecy;
    private $mutationResolverLocatorProphecy;
    private $normalizerProphecy;
    private $resourceMetadataFactoryProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $this->dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $this->mutationResolverLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $this->normalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $this->normalizerProphecy->willImplement(DenormalizerInterface::class);
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $this->resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('Dummy'));
        $this->resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn(new ResourceMetadata('RelatedDummy'));

        $this->itemMutationResolverFactory = new ItemMutationResolverFactory(
            $this->iriConverterProphecy->reveal(),
            $this->dataPersisterProphecy->reveal(),
            $this->mutationResolverLocatorProphecy->reveal(),
            $this->normalizerProphecy->reveal(),
            $this->resourceMetadataFactoryProphecy->reveal()
        );
    }

    public function testCreateItemMutationResolverNoItem(): void
    {
        $this->iriConverterProphecy->getItemFromIri('/dummies/3', ['attributes' => []])->willThrow(new ItemNotFoundException());

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Item "/dummies/3" not found.');

        $this->dataPersisterProphecy->remove(Argument::any())->shouldNotBeCalled();

        $resolver = ($this->itemMutationResolverFactory)(Dummy::class, Dummy::class, 'delete');

        $resolveInfo = new ResolveInfo('', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $resolver(null, ['input' => ['id' => '/dummies/3', 'clientMutationId' => '1936']], null, $resolveInfo);
    }

    public function testCreateItemMutationResolverInvalidItem(): void
    {
        $relatedDummy = new RelatedDummy();

        $this->iriConverterProphecy->getItemFromIri('/dummies/3', ['attributes' => []])->willReturn($relatedDummy);

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Item "/dummies/3" did not match expected type "Dummy".');

        $this->dataPersisterProphecy->remove(Argument::any())->shouldNotBeCalled();

        $resolver = ($this->itemMutationResolverFactory)(Dummy::class, Dummy::class, 'delete');

        $resolveInfo = new ResolveInfo('', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $resolver(null, ['input' => ['id' => '/dummies/3', 'clientMutationId' => '1936']], null, $resolveInfo);
    }

    public function testCreateItemDeleteMutationResolver(): void
    {
        $dummy = new Dummy();

        $this->iriConverterProphecy->getItemFromIri('/dummies/3', ['attributes' => []])->willReturn($dummy);

        $this->dataPersisterProphecy->remove($dummy)->shouldBeCalled();

        $resolver = ($this->itemMutationResolverFactory)(Dummy::class, null, 'delete');

        $resolveInfo = $this->prophesize(ResolveInfo::class);
        $resolveInfo->getFieldSelection(PHP_INT_MAX)->shouldBeCalled()->willReturn(['shortName' => []]);

        $this->assertEquals(['dummy' => ['id' => '/dummies/3'], 'clientMutationId' => '1936'], $resolver(null, ['input' => ['id' => '/dummies/3', 'clientMutationId' => '1936']], null, $resolveInfo->reveal()));
    }

    public function testCreateItemMutationResolverCustom(): void
    {
        $item = new RelatedDummy();
        $returnedItem = new RelatedDummy();
        $returnedItem->setName('returned');

        $this->iriConverterProphecy->getItemFromIri('/related_dummies/3', ['attributes' => []])->willReturn($item);

        $this->resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn(new ResourceMetadata('RelatedDummy', null, null, null, null, null, null, ['custom_mutation' => ['mutation' => 'mutation_resolver_id']]));

        $this->normalizerProphecy->denormalize(Argument::cetera())->willReturn(null);

        $this->mutationResolverLocatorProphecy->get('mutation_resolver_id')->shouldBeCalled()->willReturn(function () use ($returnedItem) {
            return $returnedItem;
        });

        $this->dataPersisterProphecy->persist($returnedItem, Argument::type('array'))->shouldBeCalled()->willReturn($returnedItem);
        $this->normalizerProphecy->normalize($returnedItem, Argument::cetera())->shouldBeCalled()->willReturn(['id' => '/related_dummies/3']);

        $resolver = ($this->itemMutationResolverFactory)(RelatedDummy::class, null, 'custom_mutation');

        $resolveInfo = $this->prophesize(ResolveInfo::class);
        $resolveInfo->getFieldSelection(PHP_INT_MAX)->shouldBeCalled()->willReturn(['shortName' => []]);

        $this->assertEquals(['relatedDummy' => ['id' => '/related_dummies/3'], 'clientMutationId' => '1936'], $resolver(null, ['input' => ['id' => '/related_dummies/3', 'clientMutationId' => '1936']], null, $resolveInfo->reveal()));
    }

    public function testCreateItemMutationResolverCustomNoPersist(): void
    {
        $item = new RelatedDummy();

        $this->iriConverterProphecy->getItemFromIri('/related_dummies/3', ['attributes' => []])->willReturn($item);

        $this->resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn(new ResourceMetadata('RelatedDummy', null, null, null, null, null, null, ['custom_mutation' => ['mutation' => 'mutation_resolver_id']]));

        $this->normalizerProphecy->denormalize(Argument::cetera())->willReturn(null);

        $this->mutationResolverLocatorProphecy->get('mutation_resolver_id')->shouldBeCalled()->willReturn(function () {
            return null;
        });

        $this->dataPersisterProphecy->persist(Argument::any())->shouldNotBeCalled();
        $this->normalizerProphecy->normalize(null, Argument::cetera())->shouldBeCalled()->willReturn(null);

        $resolver = ($this->itemMutationResolverFactory)(RelatedDummy::class, null, 'custom_mutation');

        $resolveInfo = $this->prophesize(ResolveInfo::class);
        $resolveInfo->getFieldSelection(PHP_INT_MAX)->shouldBeCalled()->willReturn(['shortName' => []]);

        $this->assertEquals(['relatedDummy' => null, 'clientMutationId' => '1936'], $resolver(null, ['input' => ['id' => '/related_dummies/3', 'clientMutationId' => '1936']], null, $resolveInfo->reveal()));
    }

    public function testCreateItemMutationResolverCustomInvalidReturnedClass(): void
    {
        $item = new RelatedDummy();

        $this->iriConverterProphecy->getItemFromIri('/related_dummies/3', ['attributes' => []])->willReturn($item);

        $this->resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn(new ResourceMetadata('RelatedDummy', null, null, null, null, null, null, ['custom_mutation' => ['mutation' => 'mutation_resolver_id']]));

        $this->mutationResolverLocatorProphecy->get('mutation_resolver_id')->shouldBeCalled()->willReturn(function () {
            return new Dummy();
        });

        $resolver = ($this->itemMutationResolverFactory)(RelatedDummy::class, null, 'custom_mutation');

        $resolveInfo = $this->prophesize(ResolveInfo::class);
        $resolveInfo->getFieldSelection(PHP_INT_MAX)->shouldBeCalled()->willReturn(['shortName' => []]);

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Custom mutation resolver "mutation_resolver_id" has to return an item of class RelatedDummy but returned an item of class Dummy.');

        $this->assertEquals(['relatedDummy' => ['id' => '/related_dummies/3'], 'clientMutationId' => '1936'], $resolver(null, ['input' => ['id' => '/related_dummies/3', 'clientMutationId' => '1936']], null, $resolveInfo->reveal()));
    }
}
