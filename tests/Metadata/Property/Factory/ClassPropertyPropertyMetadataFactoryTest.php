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

namespace ApiPlatform\Core\Tests\Metadata\Property\Factory;

use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\ClassPropertyPropertyMetadataFactory;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\CustomMultipleIdentifierDummy as CustomMultipleIdentifierDummyModel;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ClassPropertyPropertyMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    private $propertyMetadataFactory;
    private $classPropertyPropertyMetadataFactory;

    protected function setUp(): void
    {
        $this->propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $this->classPropertyPropertyMetadataFactory = new ClassPropertyPropertyMetadataFactory($this->propertyMetadataFactory->reveal());
    }

    public function testCreateNoClassProperty(): void
    {
        $propertyMetadata = new PropertyMetadata();

        $this->propertyMetadataFactory->create(Dummy::class, 'name', [])->willReturn($propertyMetadata);

        self::assertSame($propertyMetadata, $this->classPropertyPropertyMetadataFactory->create(Dummy::class, 'name'));
    }

    public function testClassNotFound(): void
    {
        $this->expectException(PropertyNotFoundException::class);
        $this->expectExceptionMessage(sprintf('Property "name" of the resource class "%s" not found.', Dummy::class));

        $this->propertyMetadataFactory->create(Argument::cetera())->willThrow(new PropertyNotFoundException());

        $this->classPropertyPropertyMetadataFactory->create(Dummy::class, 'name');
    }

    public function testCreate(): void
    {
        $expectedPropertyMetadata = (new PropertyMetadata())->withIdentifier(true);

        $this->propertyMetadataFactory->create(Argument::cetera())->willThrow(new PropertyNotFoundException());

        self::assertEquals($expectedPropertyMetadata, $this->classPropertyPropertyMetadataFactory->create(CustomMultipleIdentifierDummyModel::class, 'secondId'));
    }
}
