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

namespace ApiPlatform\Core\Tests\Bridge\Eloquent\PropertyAccess;

use ApiPlatform\Core\Bridge\Eloquent\PropertyAccess\EloquentPropertyAccessor;
use ApiPlatform\Core\Tests\Fixtures\NotAResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @group eloquent
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class EloquentPropertyAccessorTest extends TestCase
{
    use ProphecyTrait;

    private $propertyAccessorProphecy;
    private $eloquentPropertyAccessor;

    protected function setUp(): void
    {
        $this->propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $this->eloquentPropertyAccessor = new EloquentPropertyAccessor($this->propertyAccessorProphecy->reveal());
    }

    public function testSetValue(): void
    {
        $arr = [];

        $this->propertyAccessorProphecy->setValue($arr, 'foo', true)->shouldBeCalled();

        $this->eloquentPropertyAccessor->setValue($arr, 'foo', true);
    }

    /**
     * @dataProvider provideSetValueModelRelationCases
     */
    public function testSetValueModelRelation($value, string $expectedValueType): void
    {
        $modelProphecy = $this->prophesize(Dummy::class);

        $modelProphecy->setRelation('relatedDummy', Argument::type($expectedValueType))->shouldBeCalled();

        $model = $modelProphecy->reveal();
        $this->eloquentPropertyAccessor->setValue($model, 'relatedDummy', $value);
    }

    public function provideSetValueModelRelationCases(): \Generator
    {
        yield 'relation' => [new Dummy(), Dummy::class];

        yield 'relation collection' => [[new Dummy()], Collection::class];
    }

    public function testGetValueNotObject(): void
    {
        $this->propertyAccessorProphecy->getValue([], 'foo')->willReturn('bar');

        self::assertSame('bar', $this->eloquentPropertyAccessor->getValue([], 'foo'));
    }

    public function testGetValueNotModel(): void
    {
        $object = new NotAResource('foo', 'bar');

        $this->propertyAccessorProphecy->getValue($object, 'foo')->willReturn('bar');

        self::assertSame('bar', $this->eloquentPropertyAccessor->getValue($object, 'foo'));
    }

    public function testGetValueModelDate(): void
    {
        $modelProphecy = $this->prophesize(Dummy::class);
        $modelProphecy->getAttribute('date')->willReturn(Carbon::create(2021, 4, 1, 12, 0, 42));

        self::assertEquals(new \DateTime('2021-04-01 12:00:42'), $this->eloquentPropertyAccessor->getValue($modelProphecy->reveal(), 'date'));
    }

    public function testGetValueModel(): void
    {
        $modelProphecy = $this->prophesize(Dummy::class);
        $modelProphecy->getAttribute('foo')->willReturn('bar');

        self::assertSame('bar', $this->eloquentPropertyAccessor->getValue($modelProphecy->reveal(), 'foo'));
    }

    public function testIsWritable(): void
    {
        $this->propertyAccessorProphecy->isWritable([], 'foo')->willReturn(true);

        self::assertTrue($this->eloquentPropertyAccessor->isWritable([], 'foo'));
    }

    public function testIsReadable(): void
    {
        $this->propertyAccessorProphecy->isReadable([], 'foo')->willReturn(true);

        self::assertTrue($this->eloquentPropertyAccessor->isReadable([], 'foo'));
    }
}
