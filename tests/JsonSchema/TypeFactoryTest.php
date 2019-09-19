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

namespace ApiPlatform\Core\Tests\JsonSchema;

use ApiPlatform\Core\JsonSchema\Schema;
use ApiPlatform\Core\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Core\JsonSchema\TypeFactory;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\PropertyInfo\Type;

class TypeFactoryTest extends TestCase
{
    /**
     * @dataProvider typeProvider
     */
    public function testGetType(array $schema, Type $type): void
    {
        $typeFactory = new TypeFactory();
        $this->assertSame($schema, $typeFactory->getType($type));
    }

    public function typeProvider(): iterable
    {
        yield [['type' => 'integer'], new Type(Type::BUILTIN_TYPE_INT)];
        yield [['type' => 'number'], new Type(Type::BUILTIN_TYPE_FLOAT)];
        yield [['type' => 'boolean'], new Type(Type::BUILTIN_TYPE_BOOL)];
        yield [['type' => 'string'], new Type(Type::BUILTIN_TYPE_OBJECT)];
        yield [['type' => 'string', 'format' => 'date-time'], new Type(Type::BUILTIN_TYPE_OBJECT, false, \DateTimeImmutable::class)];
        yield [['type' => 'string'], new Type(Type::BUILTIN_TYPE_OBJECT, false, Dummy::class)];
        yield [['type' => 'array', 'items' => ['type' => 'string']], new Type(Type::BUILTIN_TYPE_STRING, false, null, true)];
    }

    public function testGetClassType(): void
    {
        $schemaFactoryProphecy = $this->prophesize(SchemaFactoryInterface::class);

        $schemaFactoryProphecy->buildSchema(Dummy::class, 'jsonld', Schema::TYPE_OUTPUT, null, null, Argument::type(Schema::class), ['foo' => 'bar'])->will(function (array $args) {
            $args[5]['$ref'] = 'ref';

            return $args[5];
        });

        $typeFactory = new TypeFactory();
        $typeFactory->setSchemaFactory($schemaFactoryProphecy->reveal());

        $this->assertSame(['$ref' => 'ref'], $typeFactory->getType(new Type(Type::BUILTIN_TYPE_OBJECT, false, Dummy::class), 'jsonld', true, ['foo' => 'bar'], new Schema()));
    }
}
