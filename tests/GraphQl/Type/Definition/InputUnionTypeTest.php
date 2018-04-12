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

namespace ApiPlatform\Core\Tests\GraphQl\Type\Definition;

use ApiPlatform\Core\GraphQl\Type\Definition\InputUnionType;
use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\LeafType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\StringType;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class InputUnionTypeTest extends TestCase
{
    public function testGetTypesNotSet()
    {
        $inputUnionType = new InputUnionType([]);

        $this->expectException(InvariantViolation::class);
        $this->expectExceptionMessage('InputUnion types must be an Array or a callable which returns an Array.');

        $inputUnionType->getTypes();
    }

    public function testGetTypesInvalid()
    {
        $inputUnionType = new InputUnionType(['types' => 1]);

        $this->expectException(InvariantViolation::class);
        $this->expectExceptionMessage('InputUnion types must be an Array or a callable which returns an Array.');

        $inputUnionType->getTypes();
    }

    public function testGetTypesCallable()
    {
        $inputUnionType = new InputUnionType(['types' => function () {
            return ['foo'];
        }]);

        $this->assertEquals(['foo'], $inputUnionType->getTypes());
    }

    public function testGetTypes()
    {
        $inputUnionType = new InputUnionType(['types' => ['bar']]);

        $this->assertEquals(['bar'], $inputUnionType->getTypes());
    }

    public function testAssertValidEmptyTypes()
    {
        $inputUnionType = new InputUnionType(['types' => []]);

        $this->expectException(InvariantViolation::class);
        $this->expectExceptionMessage('InputUnion types must not be empty');

        $inputUnionType->assertValid();
    }

    public function testAssertValidNotInputObjectTypes()
    {
        $inputUnionType = new InputUnionType(['types' => ['foo']]);

        $this->expectException(InvariantViolation::class);
        $this->expectExceptionMessage('InputUnion may only contain input types, it cannot contain: "foo".');

        $inputUnionType->assertValid();
    }

    public function testAssertValidDuplicateTypes()
    {
        $type = $this->prophesize(StringType::class)->reveal();
        $inputUnionType = new InputUnionType(['types' => [$type, $type]]);

        $this->expectException(InvariantViolation::class);
        $this->expectExceptionMessage('InputUnion can include String type only once.');

        $inputUnionType->assertValid();
    }

    public function testSerializeNotLeafType()
    {
        $type = $this->prophesize(ObjectType::class)->reveal();
        $inputUnionType = new InputUnionType(['types' => [$type]]);

        $this->expectException(InvariantViolation::class);
        $this->expectExceptionMessage('Types in union cannot represent value: "foo"');

        $inputUnionType->serialize('foo');
    }

    public function testSerialize()
    {
        $type = $this->prophesize(LeafType::class);
        $type->serialize('foo')->shouldBeCalled();
        $inputUnionType = new InputUnionType(['types' => [$type->reveal()]]);

        $inputUnionType->serialize('foo');
    }

    public function testParseValueNotLeafType()
    {
        $type = $this->prophesize(ObjectType::class)->reveal();
        $inputUnionType = new InputUnionType(['types' => [$type]]);

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Types in union cannot represent value: "foo"');

        $inputUnionType->parseValue('foo');
    }

    public function testParseValue()
    {
        $type = $this->prophesize(LeafType::class);
        $type->parseValue('foo')->shouldBeCalled();
        $inputUnionType = new InputUnionType(['types' => [$type->reveal()]]);

        $inputUnionType->parseValue('foo');
    }

    public function testParseLiteralNotLeafType()
    {
        $type = $this->prophesize(ObjectType::class)->reveal();
        $inputUnionType = new InputUnionType(['types' => [$type]]);

        $this->assertNull($inputUnionType->parseLiteral(new StringValueNode(['value' => 'foo'])));
    }

    public function testParseLiteral()
    {
        $type = $this->prophesize(LeafType::class);
        $node = new StringValueNode(['value' => 'foo']);
        $type->parseLiteral($node)->shouldBeCalled();
        $inputUnionType = new InputUnionType(['types' => [$type->reveal()]]);

        $inputUnionType->parseLiteral($node);
    }

    public function testIsValidValueNotLeafType()
    {
        $type = $this->prophesize(ObjectType::class)->reveal();
        $inputUnionType = new InputUnionType(['types' => [$type]]);

        $this->assertFalse($inputUnionType->isValidValue('foo'));
    }

    public function testIsValidValueInvalid()
    {
        $type = $this->prophesize(LeafType::class);
        $type->isValidValue('foo')->willReturn(false)->shouldBeCalled();
        $inputUnionType = new InputUnionType(['types' => [$type->reveal()]]);

        $this->assertFalse($inputUnionType->isValidValue('foo'));
    }

    public function testIsValidValue()
    {
        $type = $this->prophesize(LeafType::class);
        $type->isValidValue('foo')->willReturn(true)->shouldBeCalled();
        $inputUnionType = new InputUnionType(['types' => [$type->reveal()]]);

        $this->assertTrue($inputUnionType->isValidValue('foo'));
    }

    public function testIsValidLiteralNotLeafType()
    {
        $type = $this->prophesize(ObjectType::class)->reveal();
        $inputUnionType = new InputUnionType(['types' => [$type]]);

        $this->assertFalse($inputUnionType->isValidLiteral(new StringValueNode(['value' => 'foo'])));
    }

    public function testIsValidLiteralInvalid()
    {
        $type = $this->prophesize(LeafType::class);
        $node = new StringValueNode(['value' => 'foo']);
        $type->isValidLiteral($node)->willReturn(false)->shouldBeCalled();
        $inputUnionType = new InputUnionType(['types' => [$type->reveal()]]);

        $this->assertFalse($inputUnionType->isValidLiteral($node));
    }

    public function testIsValidLiteral()
    {
        $type = $this->prophesize(LeafType::class);
        $node = new StringValueNode(['value' => 'foo']);
        $type->isValidLiteral($node)->willReturn(true)->shouldBeCalled();
        $inputUnionType = new InputUnionType(['types' => [$type->reveal()]]);

        $this->assertTrue($inputUnionType->isValidLiteral($node));
    }
}
