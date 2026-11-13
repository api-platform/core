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

namespace ApiPlatform\GraphQl\Tests\Type\Definition;

use ApiPlatform\GraphQl\Type\Definition\IterableType;
use GraphQL\Error\Error;
use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\NameNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\NullValueNode;
use GraphQL\Language\AST\ObjectFieldNode;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\StringValueNode;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class IterableTypeTest extends TestCase
{
    public function testGetName(): void
    {
        $iterableType = new IterableType();

        $this->assertSame('Iterable', $iterableType->getName());
    }

    public function testSerialize(): void
    {
        $iterableType = new IterableType();

        $this->assertEquals(['foo'], $iterableType->serialize(['foo']));
    }

    public function testSerializeNonIterable(): void
    {
        $iterableType = new IterableType();

        $this->expectException(Error::class);
        $this->expectExceptionMessageMatches('/`Iterable` cannot represent non iterable value: .+/');

        $iterableType->serialize('foo');
    }

    public function testParseValue(): void
    {
        $iterableType = new IterableType();

        $this->assertEquals(['foo'], $iterableType->parseValue(['foo']));
    }

    public function testParseValueNonIterable(): void
    {
        $iterableType = new IterableType();

        $this->expectException(Error::class);
        $this->expectExceptionMessageMatches('/`Iterable` cannot represent non iterable value: .+/');

        $iterableType->parseValue('foo');
    }

    public function testParseLiteral(): void
    {
        $iterableType = new IterableType();

        $listValueNode = new ListValueNode(['values' => new NodeList([])]);
        $this->assertEquals([], $iterableType->parseLiteral($listValueNode));

        $objectValueNode = new ObjectValueNode(['fields' => new NodeList([])]);
        $this->assertEquals([], $iterableType->parseLiteral($objectValueNode));

        $listValueNode = new ListValueNode([
            'values' => new NodeList([
                new StringValueNode(['value' => 'foo']),
                new BooleanValueNode(['value' => false]),
                new IntValueNode(['value' => '123']),
                new FloatValueNode(['value' => '9.4']),
                new NullValueNode([]),
                new ObjectValueNode([
                    'fields' => new NodeList([
                        new ObjectFieldNode(['value' => new StringValueNode(['value' => 'baz']), 'name' => new NameNode(['value' => 'bar'])]),
                    ]),
                ]),
                new ListValueNode([
                    'values' => new NodeList([
                        new BooleanValueNode(['value' => true]),
                    ]),
                ]),
            ]),
        ]);
        $this->assertEquals(['foo', false, 123, 9.4, null, ['bar' => 'baz'], [true]], $iterableType->parseLiteral($listValueNode));
    }

    public function testParseLiteralWithNonIterableNode(): void
    {
        $iterableType = new IterableType();

        $this->expectException(\Exception::class);

        $iterableType->parseLiteral(new IntValueNode(['value' => '1']));
    }
}
