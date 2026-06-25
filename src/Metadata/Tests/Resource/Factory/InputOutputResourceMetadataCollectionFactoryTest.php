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

namespace ApiPlatform\Metadata\Tests\Resource\Factory;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\InputOutputResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Tests\Fixtures\DummyEntity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class InputOutputResourceMetadataCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[DataProvider('getAttributes')]
    public function testInputOutputMetadata(mixed $input, ?string $expected): void
    {
        $resourceCollection = new ResourceMetadataCollection('Foo', [new ApiResource(input: $input)]);
        $decoratedProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $decoratedProphecy->create('Foo')->willReturn($resourceCollection)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new InputOutputResourceMetadataCollectionFactory($decorated);
        $this->assertSame($expected, $factory->create('Foo')[0]->getInputClass());
    }

    public static function getAttributes(): array
    {
        return [
            // empty array — no class to resolve
            [[], null],
            // input is a string class name
            [DummyEntity::class, DummyEntity::class],
            // input: false disables deserialization, resolves to null
            [false, null],
            // input is an array with a class key
            [['class' => DummyEntity::class, 'type' => 'Foo'], DummyEntity::class],
        ];
    }

    /**
     * Regression test: a resource-level `output: false` (or `input: false`) must still resolve to
     * null on an operation that doesn't redeclare output/input itself, instead of silently falling
     * back to the resource class (see Metadata::withOutputClass()/withInputClass()).
     */
    #[DataProvider('getDisabledAttribute')]
    public function testResourceLevelDisabledStatePropagatesToNonOverridingOperation(string $attribute, string $getter, string $hasExplicitMethod): void
    {
        $resource = 'output' === $attribute ? new ApiResource(output: false) : new ApiResource(input: false);
        $resourceCollection = new ResourceMetadataCollection('Foo', [
            $resource->withOperations(new Operations(['get' => new Get()])),
        ]);
        $decoratedProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $decoratedProphecy->create('Foo')->willReturn($resourceCollection)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new InputOutputResourceMetadataCollectionFactory($decorated);
        $operation = $factory->create('Foo')[0]->getOperations()->getIterator()->current();

        $this->assertNull($operation->{$getter}());
        $this->assertTrue($operation->{$hasExplicitMethod}());
    }

    public static function getDisabledAttribute(): array
    {
        return [
            ['output', 'getOutputClass', 'hasExplicitOutputClass'],
            ['input', 'getInputClass', 'hasExplicitInputClass'],
        ];
    }

    /**
     * An operation can still re-enable its own output/input despite a resource-level disable.
     */
    #[DataProvider('getReEnablingAttribute')]
    public function testOperationCanReEnableDespiteResourceLevelDisable(string $attribute, string $getter): void
    {
        $resource = 'output' === $attribute ? new ApiResource(output: false) : new ApiResource(input: false);
        $operation = 'output' === $attribute ? new Get(output: DummyEntity::class) : new Get(input: DummyEntity::class);
        $resourceCollection = new ResourceMetadataCollection('Foo', [
            $resource->withOperations(new Operations(['get' => $operation])),
        ]);
        $decoratedProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $decoratedProphecy->create('Foo')->willReturn($resourceCollection)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new InputOutputResourceMetadataCollectionFactory($decorated);
        $operation = $factory->create('Foo')[0]->getOperations()->getIterator()->current();

        $this->assertSame(DummyEntity::class, $operation->{$getter}());
    }

    public static function getReEnablingAttribute(): array
    {
        return [
            ['output', 'getOutputClass'],
            ['input', 'getInputClass'],
        ];
    }
}
