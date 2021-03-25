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

namespace ApiPlatform\Core\Tests\Bridge\Eloquent\Metadata\Property;

use ApiPlatform\Core\Bridge\Eloquent\Metadata\Property\EloquentPropertyMetadataFactory;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;

/**
 * @group eloquent
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class EloquentPropertyMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    private $propertyMetadataFactory;
    private $eloquentPropertyMetadataFactory;

    protected function setUp(): void
    {
        $this->propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $this->eloquentPropertyMetadataFactory = new EloquentPropertyMetadataFactory($this->propertyMetadataFactory->reveal());
    }

    public function testCreateAlreadyIdentifier(): void
    {
        $propertyMetadata = new PropertyMetadata();
        $propertyMetadata = $propertyMetadata->withIdentifier(true);

        $this->propertyMetadataFactory->create(Dummy::class, 'id', [])->shouldBeCalled()->willReturn($propertyMetadata);

        self::assertEquals($this->eloquentPropertyMetadataFactory->create(Dummy::class, 'id'), $propertyMetadata);
    }

    public function testCreateNoAlreadyIdentifier(): void
    {
        $this->propertyMetadataFactory->create(Dummy::class, 'id', [])->shouldBeCalled()->willReturn(new PropertyMetadata());

        self::assertEquals(
            $this->eloquentPropertyMetadataFactory->create(Dummy::class, 'id'),
            (new PropertyMetadata())->withIdentifier(true)->withWritable(false)
        );
    }

    public function testCreateNoIdentifier(): void
    {
        $this->propertyMetadataFactory->create(Dummy::class, 'foo', [])->shouldBeCalled()->willReturn(new PropertyMetadata());

        self::assertEquals(
            $this->eloquentPropertyMetadataFactory->create(Dummy::class, 'foo'),
            (new PropertyMetadata())->withIdentifier(false)
        );
    }
}
