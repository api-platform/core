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

namespace ApiPlatform\GraphQl\Tests\Resolver;

use ApiPlatform\GraphQl\Resolver\ResourceFieldResolver;
use ApiPlatform\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\GraphQl\Tests\Fixtures\ApiResource\Dummy;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ResourceFieldResolverTest extends TestCase
{
    use ProphecyTrait;

    public function testId(): void
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, null, ['uri_variables' => ['id' => 1]])->willReturn('/dummies/1')->shouldBeCalled();

        // graphql-php < 15
        if (method_exists(FieldDefinition::class, 'create')) {
            $fieldDefinition = FieldDefinition::create(['name' => 'id', 'type' => new ObjectType(['name' => '', 'fields' => []])]);
        } else {
            $fieldDefinition = new FieldDefinition(['name' => 'id', 'type' => new ObjectType(['name' => '', 'fields' => []])]);
        }
        $resolveInfo = new ResolveInfo($fieldDefinition, new \ArrayObject(), new ObjectType(['name' => '', 'fields' => []]), [], new Schema([]), [], null, new OperationDefinitionNode([]), []);

        $resolver = new ResourceFieldResolver($iriConverterProphecy->reveal());
        $this->assertSame('/dummies/1', $resolver([ItemNormalizer::ITEM_RESOURCE_CLASS_KEY => Dummy::class, ItemNormalizer::ITEM_IDENTIFIERS_KEY => ['id' => 1]], [], [], $resolveInfo));
    }

    public function testOriginalId(): void
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        // graphql-php < 15
        if (method_exists(FieldDefinition::class, 'create')) {
            $fieldDefinition = FieldDefinition::create(['name' => '_id', 'type' => new ObjectType(['name' => '', 'fields' => []])]);
        } else {
            $fieldDefinition = new FieldDefinition(['name' => '_id', 'type' => new ObjectType(['name' => '', 'fields' => []])]);
        }
        $resolveInfo = new ResolveInfo($fieldDefinition, new \ArrayObject(), new ObjectType(['name' => '', 'fields' => []]), [], new Schema([]), [], null, new OperationDefinitionNode([]), []);

        $resolver = new ResourceFieldResolver($iriConverterProphecy->reveal());
        $this->assertSame(1, $resolver(['id' => 1], [], [], $resolveInfo));
    }

    public function testDirectAccess(): void
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        // graphql-php < 15
        if (method_exists(FieldDefinition::class, 'create')) {
            $fieldDefinition = FieldDefinition::create(['name' => 'foo', 'type' => new ObjectType(['name' => '', 'fields' => []])]);
        } else {
            $fieldDefinition = new FieldDefinition(['name' => 'foo', 'type' => new ObjectType(['name' => '', 'fields' => []])]);
        }
        $resolveInfo = new ResolveInfo($fieldDefinition, new \ArrayObject(), new ObjectType(['name' => '', 'fields' => []]), [], new Schema([]), [], null, new OperationDefinitionNode([]), []);

        $resolver = new ResourceFieldResolver($iriConverterProphecy->reveal());
        $this->assertSame('bar', $resolver(['foo' => 'bar'], [], [], $resolveInfo));
    }

    public function testNonResource(): void
    {
        $dummy = new Dummy();
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($dummy)->willReturn('/dummies/1')->shouldNotBeCalled();

        // graphql-php < 15
        if (method_exists(FieldDefinition::class, 'create')) {
            $fieldDefinition = FieldDefinition::create(['name' => 'id', 'type' => new ObjectType(['name' => '', 'fields' => []])]);
        } else {
            $fieldDefinition = new FieldDefinition(['name' => 'id', 'type' => new ObjectType(['name' => '', 'fields' => []])]);
        }
        $resolveInfo = new ResolveInfo($fieldDefinition, new \ArrayObject(), new ObjectType(['name' => '', 'fields' => []]), [], new Schema([]), [], null, new OperationDefinitionNode([]), []);

        $resolver = new ResourceFieldResolver($iriConverterProphecy->reveal());
        $this->assertNull($resolver([], [], [], $resolveInfo));
    }
}
