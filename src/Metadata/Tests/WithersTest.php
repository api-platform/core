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

namespace ApiPlatform\Metadata\Tests;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Error;
use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query as GraphQlQuery;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\HeaderParameter;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\McpResource;
use ApiPlatform\Metadata\McpTool;
use ApiPlatform\Metadata\McpToolCollection;
use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Query;
use ApiPlatform\Metadata\QueryParameter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type;

final class WithersTest extends TestCase
{
    #[DataProvider('provideMetadata')]
    public function testWithersReturnANewInstance(object $metadata): void
    {
        foreach ((new \ReflectionObject($metadata))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (!str_starts_with($method->getName(), 'with') || $method->isStatic()) {
                continue;
            }

            $arguments = [];
            foreach (\array_slice($method->getParameters(), 0, $method->getNumberOfRequiredParameters()) as $parameter) {
                $arguments[] = $this->createArgument($parameter, $metadata);
            }

            $copy = $metadata->{$method->getName()}(...$arguments);

            $this->assertInstanceOf($metadata::class, $copy, \sprintf('%s::%s() must return the metadata.', $metadata::class, $method->getName()));
            $this->assertNotSame($metadata, $copy, \sprintf('%s::%s() must return a new instance.', $metadata::class, $method->getName()));
        }
    }

    public static function provideMetadata(): iterable
    {
        yield [new ApiResource()];
        yield [new ApiProperty()];
        yield [new Link()];
        yield [new QueryParameter()];
        yield [new HeaderParameter()];
        yield [new Get()];
        yield [new GetCollection()];
        yield [new Post()];
        yield [new Put()];
        yield [new Patch()];
        yield [new Delete()];
        yield [new Query()];
        yield [new NotExposed()];
        yield [new Error()];
        yield [new ErrorResource()];
        yield [new GraphQlQuery()];
        yield [new QueryCollection()];
        yield [new Mutation()];
        yield [new DeleteMutation()];
        yield [new Subscription()];
        yield [new McpTool()];
        yield [new McpToolCollection()];
        yield [new McpResource('file://foo')];
    }

    private function createArgument(\ReflectionParameter $parameter, object $metadata): mixed
    {
        $type = $parameter->getType();
        if (null === $type || $type->allowsNull()) {
            return null;
        }

        foreach ($type instanceof \ReflectionUnionType ? $type->getTypes() : [$type] as $namedType) {
            $name = $namedType instanceof \ReflectionNamedType ? $namedType->getName() : null;

            if ('self' === $name || (null !== $name && is_a($metadata, $name))) {
                return $metadata;
            }

            switch ($name) {
                case 'bool':
                    return true;
                case 'false':
                    return false;
                case 'int':
                    return 1;
                case 'string':
                    return 'foo';
                case 'array':
                    return [];
                case Operations::class:
                    return new Operations();
                case Type::class:
                    return Type::string();
            }
        }

        $this->fail(\sprintf('Cannot create an argument for $%s of %s::%s().', $parameter->getName(), $metadata::class, $parameter->getDeclaringFunction()->getName()));
    }
}
