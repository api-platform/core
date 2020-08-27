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
use ApiPlatform\Core\GraphQl\Resolver\ResourceFieldResolver;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;

class ResourceFieldResolverTest extends TestCase
{
    use ProphecyTrait;

    public function testId()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getItemIriFromResourceClass(Dummy::class, ['id' => 1])->willReturn('/dummies/1')->shouldBeCalled();

        $resolveInfo = new ResolveInfo(FieldDefinition::create(['name' => 'id', 'type' => new ObjectType(['name' => ''])]), [], new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $resolver = new ResourceFieldResolver($iriConverterProphecy->reveal());
        $this->assertEquals('/dummies/1', $resolver([ItemNormalizer::ITEM_RESOURCE_CLASS_KEY => Dummy::class, ItemNormalizer::ITEM_IDENTIFIERS_KEY => ['id' => 1]], [], [], $resolveInfo));
    }

    public function testOriginalId()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resolveInfo = new ResolveInfo(FieldDefinition::create(['name' => '_id', 'type' => new ObjectType(['name' => ''])]), [], new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $resolver = new ResourceFieldResolver($iriConverterProphecy->reveal());
        $this->assertEquals(1, $resolver(['id' => 1], [], [], $resolveInfo));
    }

    public function testDirectAccess()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resolveInfo = new ResolveInfo(FieldDefinition::create(['name' => 'foo', 'type' => new ObjectType(['name' => ''])]), [], new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $resolver = new ResourceFieldResolver($iriConverterProphecy->reveal());
        $this->assertEquals('bar', $resolver(['foo' => 'bar'], [], [], $resolveInfo));
    }

    public function testNonResource()
    {
        $dummy = new Dummy();
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummies/1')->shouldNotBeCalled();

        $resolveInfo = new ResolveInfo(FieldDefinition::create(['name' => 'id', 'type' => new ObjectType(['name' => ''])]), [], new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);

        $resolver = new ResourceFieldResolver($iriConverterProphecy->reveal());
        $this->assertNull($resolver([], [], [], $resolveInfo));
    }
}
