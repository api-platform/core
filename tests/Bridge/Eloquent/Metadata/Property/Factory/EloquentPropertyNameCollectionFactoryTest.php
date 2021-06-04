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

namespace ApiPlatform\Core\Tests\Bridge\Eloquent\Metadata\Property\Factory;

use ApiPlatform\Core\Bridge\Eloquent\Metadata\Property\Factory\EloquentPropertyNameCollectionFactory;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\Fixtures\NotAResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;

/**
 * @group eloquent
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class EloquentPropertyNameCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    private $decoratedPropertyNameCollectionFactoryProphecy;
    private $eloquentPropertyNameCollectionFactory;

    protected function setUp(): void
    {
        $this->decoratedPropertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $this->eloquentPropertyNameCollectionFactory = new EloquentPropertyNameCollectionFactory($this->decoratedPropertyNameCollectionFactoryProphecy->reveal());
    }

    public function testCreateNotModel(): void
    {
        $decoratedPropertyNameCollection = new PropertyNameCollection(['foo']);

        $this->decoratedPropertyNameCollectionFactoryProphecy->create(NotAResource::class, [])->willReturn($decoratedPropertyNameCollection);

        self::assertSame($decoratedPropertyNameCollection, $this->eloquentPropertyNameCollectionFactory->create(NotAResource::class, []));
    }

    public function testCreateNotModelNoDecorated(): void
    {
        $this->expectException(ResourceClassNotFoundException::class);
        $this->expectExceptionMessage('The resource class "ApiPlatform\Core\Tests\Fixtures\NotAResource" is not an Eloquent model.');

        $this->decoratedPropertyNameCollectionFactoryProphecy->create(NotAResource::class, [])->willThrow(new ResourceClassNotFoundException());

        $this->eloquentPropertyNameCollectionFactory->create(NotAResource::class, []);
    }

    public function testCreate(): void
    {
        $this->decoratedPropertyNameCollectionFactoryProphecy->create(Dummy::class, [])->willReturn(new PropertyNameCollection(['name', 'foo']));

        self::assertEquals(new PropertyNameCollection(['id', 'name', 'foo']), $this->eloquentPropertyNameCollectionFactory->create(Dummy::class, []));
    }
}
