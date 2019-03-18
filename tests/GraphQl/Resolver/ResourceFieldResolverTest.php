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

namespace ApiPlatform\Core\Tests\GraphQl\Resolver;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\GraphQl\Resolver\ResourceFieldResolver;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;

class ResourceFieldResolverTest extends TestCase
{
    public function testId()
    {
        $dummy = new Dummy();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->shouldBeCalled()->willReturn(true);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummies/1')->shouldBeCalled();

        $resolveInfo = new ResolveInfo('id', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $resolver = new ResourceFieldResolver($iriConverterProphecy->reveal(), $resourceClassResolverProphecy->reveal());
        $this->assertEquals('/dummies/1', $resolver([ItemNormalizer::ITEM_KEY => serialize($dummy)], [], [], $resolveInfo));
    }

    public function testOriginalId()
    {
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resolveInfo = new ResolveInfo('_id', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $resolver = new ResourceFieldResolver($iriConverterProphecy->reveal(), $resourceClassResolverProphecy->reveal());
        $this->assertEquals(1, $resolver(['id' => 1], [], [], $resolveInfo));
    }

    public function testDirectAccess()
    {
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resolveInfo = new ResolveInfo('foo', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $resolver = new ResourceFieldResolver($iriConverterProphecy->reveal(), $resourceClassResolverProphecy->reveal());
        $this->assertEquals('bar', $resolver(['foo' => 'bar'], [], [], $resolveInfo));
        $this->assertEquals('bar', $resolver((object) ['foo' => 'bar'], [], [], $resolveInfo));
    }

    public function testNonResource()
    {
        $dummy = new Dummy();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->shouldBeCalled()->willReturn(false);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummies/1')->shouldNotBeCalled();

        $resolveInfo = new ResolveInfo('id', [], new ObjectType(['name' => '']), new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $resolver = new ResourceFieldResolver($iriConverterProphecy->reveal(), $resourceClassResolverProphecy->reveal());
        $this->assertNull($resolver([ItemNormalizer::ITEM_KEY => serialize($dummy)], [], [], $resolveInfo));
    }
}
