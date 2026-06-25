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

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ObjectMapperMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Metadata\ReflectionObjectMapperMetadataFactory;

class ObjectMapperMetadataCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testSetsMapTrueWhenResourceClassHasMapAttribute(): void
    {
        $operation = $this->createFactory(
            new Get(class: ObjectMapperMetaDummyResource::class),
        );

        $this->assertTrue($operation->canMap());
    }

    public function testDoesNotSetMapWhenNoMappingMetadata(): void
    {
        $operation = $this->createFactory(
            new Get(class: ObjectMapperMetaDummyTarget::class),
        );

        $this->assertNull($operation->canMap());
    }

    /**
     * Regression-adjacent test: input explicitly disabled (`input: false`) must NOT fall back to
     * checking the resource class for #[Map] metadata — getInputClass() correctly returns null for
     * "no input", and canBeMapped(null) correctly returns false, so canMap() stays unset here.
     */
    public function testDoesNotFallBackToResourceClassWhenInputDisabled(): void
    {
        $operation = $this->createFactory(
            new Get(class: ObjectMapperMetaDummyResource::class, input: false),
        );

        $this->assertNull($operation->canMap());
    }

    public function testSetsMapTrueWhenEntityClassFromStateOptionsMatches(): void
    {
        $operation = $this->createFactory(
            new Get(
                class: ObjectMapperMetaDummyResource::class,
                input: false,
                stateOptions: new Options(entityClass: ObjectMapperMetaDummyEntity::class),
            ),
        );

        $this->assertTrue($operation->canMap());
    }

    public function testDoesNotOverwriteAlreadySetMap(): void
    {
        $operation = $this->createFactory(
            new Get(class: ObjectMapperMetaDummyTarget::class, map: false),
        );

        $this->assertFalse($operation->canMap());
    }

    private function createFactory(Get $operation): Get
    {
        $resourceCollection = new ResourceMetadataCollection('Foo', [
            (new ApiResource())->withOperations(new Operations(['get' => $operation])),
        ]);
        $decoratedProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $decoratedProphecy->create('Foo')->willReturn($resourceCollection)->shouldBeCalled();

        $factory = new ObjectMapperMetadataCollectionFactory($decoratedProphecy->reveal(), new ReflectionObjectMapperMetadataFactory());

        return $factory->create('Foo')[0]->getOperations()->getIterator()->current();
    }
}

#[Map(target: ObjectMapperMetaDummyTarget::class)]
class ObjectMapperMetaDummyResource
{
}

class ObjectMapperMetaDummyTarget
{
}

#[Map(target: ObjectMapperMetaDummyResource::class)]
class ObjectMapperMetaDummyEntity
{
}
