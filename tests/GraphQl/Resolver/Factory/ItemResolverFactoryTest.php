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
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\GraphQl\Resolver\Factory\ItemResolverFactory;
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
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ItemResolverFactoryTest extends TestCase
{
    private $itemResolverFactory;
    private $iriConverterProphecy;
    private $queryResolverLocatorProphecy;
    private $normalizerProphecy;
    private $resourceMetadataFactoryProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $this->queryResolverLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $this->normalizerProphecy = $this->prophesize(NormalizerInterface::class);

        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $this->resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata('Dummy', null, null, null, null, ['normalization_context' => ['groups' => ['foo']]]));
        $this->resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn(new ResourceMetadata('RelatedDummy', null, null, null, null, ['normalization_context' => ['groups' => ['foo']]]));

        $this->itemResolverFactory = new ItemResolverFactory(
            $this->iriConverterProphecy->reveal(),
            $this->queryResolverLocatorProphecy->reveal(),
            $this->normalizerProphecy->reveal(),
            $this->resourceMetadataFactoryProphecy->reveal()
        );
    }

    public function testCreateItemResolverNoItem(): void
    {
        $this->iriConverterProphecy->getItemFromIri('/related_dummies/3', ['attributes' => []])->willThrow(new ItemNotFoundException());

        $resolveInfo = new ResolveInfo('name', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $this->assertNull(($this->itemResolverFactory)(RelatedDummy::class)(null, ['id' => '/related_dummies/3'], null, $resolveInfo));
    }

    public function testCreateItemResolver(): void
    {
        $item = new RelatedDummy();
        $this->iriConverterProphecy->getItemFromIri('/related_dummies/3', ['attributes' => []])->willReturn($item);
        $this->normalizerProphecy->normalize($item, Argument::cetera())->willReturn('normalizedItem');

        $resolveInfo = new ResolveInfo('name', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $this->assertEquals('normalizedItem', ($this->itemResolverFactory)(RelatedDummy::class)(null, ['id' => '/related_dummies/3'], null, $resolveInfo));
    }

    public function testCreateItemResolverInvalidItem(): void
    {
        $item = new Dummy();
        $this->iriConverterProphecy->getItemFromIri('/dummies/3', ['attributes' => []])->willReturn($item);

        $resolveInfo = new ResolveInfo('name', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $this->expectExceptionMessage('Resolver only handles items of class ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy but retrieved item is of class ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy');

        $this->assertEquals('normalizedItem', ($this->itemResolverFactory)(RelatedDummy::class)(null, ['id' => '/dummies/3'], null, $resolveInfo));
    }

    public function testCreateItemResolverCustomInvalidReturnedClass(): void
    {
        $item = new RelatedDummy();
        $this->iriConverterProphecy->getItemFromIri('/related_dummies/3', ['attributes' => []])->willReturn($item);
        $this->normalizerProphecy->normalize($item, Argument::cetera())->willReturn('normalizedItem');

        $resolveInfo = new ResolveInfo('name', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $this->resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn(new ResourceMetadata('RelatedDummy', null, null, null, null, null, null, ['custom_query' => ['item_query' => 'query_resolver_id']]));

        $this->queryResolverLocatorProphecy->get('query_resolver_id')->shouldBeCalled()->willReturn(function () {
            return new Dummy();
        });

        $this->expectExceptionMessage('Custom query resolver "query_resolver_id" has to return an item of class ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy but returned an item of class ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy');

        ($this->itemResolverFactory)(null, null, 'custom_query')(null, ['id' => '/related_dummies/3'], null, $resolveInfo);
    }

    public function testCreateItemResolverCustom(): void
    {
        $item = new RelatedDummy();
        $returnedItem = new RelatedDummy();
        $returnedItem->setName('returned');
        $this->iriConverterProphecy->getItemFromIri('/related_dummies/3', ['attributes' => []])->willReturn($item);
        $this->normalizerProphecy->normalize($returnedItem, Argument::cetera())->willReturn('normalizedItem');

        $resolveInfo = new ResolveInfo('name', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $this->resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn(new ResourceMetadata('RelatedDummy', null, null, null, null, null, null, ['custom_query' => ['item_query' => 'query_resolver_id']]));

        $this->queryResolverLocatorProphecy->get('query_resolver_id')->shouldBeCalled()->willReturn(function () use ($returnedItem) {
            return $returnedItem;
        });

        $this->assertEquals('normalizedItem', ($this->itemResolverFactory)(null, null, 'custom_query')(null, ['id' => '/related_dummies/3'], null, $resolveInfo));
    }

    /**
     * @dataProvider subresourceProvider
     */
    public function testCreateSubresourceItemResolver($normalizedSubresource): void
    {
        $item = new Dummy();
        $this->iriConverterProphecy->getItemFromIri('/dummies/3', ['attributes' => []])->willReturn($item);
        $this->normalizerProphecy->normalize($item, Argument::cetera())->willReturn(null);

        $resolveInfo = new ResolveInfo('dummy', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $this->assertEquals($normalizedSubresource, ($this->itemResolverFactory)(Dummy::class)(['dummy' => $normalizedSubresource], ['id' => '/dummies/3'], null, $resolveInfo));
    }

    public function subresourceProvider(): array
    {
        return [
            ['/related_dummies/3'],
            [null],
        ];
    }
}
