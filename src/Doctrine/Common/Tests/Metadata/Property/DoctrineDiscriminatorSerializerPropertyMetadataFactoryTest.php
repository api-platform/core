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

namespace ApiPlatform\Doctrine\Common\Tests\Metadata\Property;

use ApiPlatform\Doctrine\Common\Metadata\Property\DoctrineDiscriminatorSerializerPropertyMetadataFactory;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\Mapping\AttributeMetadata as SerializerAttributeMetadata;
use Symfony\Component\Serializer\Mapping\ClassMetadata as SerializerClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface as SerializerClassMetadataFactoryInterface;
use Symfony\Component\TypeInfo\Type;

final class DoctrineDiscriminatorSerializerPropertyMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testItEnablesReadableLinkWhenADiscriminatorSubclassDeclaresAMatchingGroup(): void
    {
        $factory = $this->createFactory(
            propertyMetadata: (new ApiProperty())->withReadable(true)->withNativeType(Type::object(ParentResource::class)),
            discriminatorMap: ['a' => ChildA::class, 'b' => ChildB::class],
            subclassGroups: [ChildA::class => ['foo'], ChildB::class => ['foo']],
        );

        $result = $factory->create(OwnerResource::class, 'relation', [
            'normalization_groups' => ['foo'],
            'denormalization_groups' => null,
        ]);

        $this->assertTrue($result->isReadableLink());
    }

    public function testItLeavesLinkStatusUntouchedWhenNoSubclassGroupMatches(): void
    {
        $factory = $this->createFactory(
            propertyMetadata: (new ApiProperty())->withReadable(true)->withNativeType(Type::object(ParentResource::class)),
            discriminatorMap: ['a' => ChildA::class],
            subclassGroups: [ChildA::class => ['bar']],
        );

        $result = $factory->create(OwnerResource::class, 'relation', [
            'normalization_groups' => ['foo'],
            'denormalization_groups' => null,
        ]);

        $this->assertNull($result->isReadableLink());
    }

    public function testItLeavesLinkStatusUntouchedWithoutADiscriminatorMap(): void
    {
        $factory = $this->createFactory(
            propertyMetadata: (new ApiProperty())->withReadable(true)->withNativeType(Type::object(ParentResource::class)),
            discriminatorMap: [],
            subclassGroups: [],
        );

        $result = $factory->create(OwnerResource::class, 'relation', [
            'normalization_groups' => ['foo'],
            'denormalization_groups' => null,
        ]);

        $this->assertNull($result->isReadableLink());
    }

    public function testItDoesNotDowngradeAnAlreadyEmbeddedRelation(): void
    {
        $factory = $this->createFactory(
            propertyMetadata: (new ApiProperty())->withReadable(true)->withReadableLink(true)->withWritableLink(true)->withNativeType(Type::object(ParentResource::class)),
            discriminatorMap: ['a' => ChildA::class],
            subclassGroups: [ChildA::class => ['bar']],
        );

        $result = $factory->create(OwnerResource::class, 'relation', [
            'normalization_groups' => ['foo'],
            'denormalization_groups' => null,
        ]);

        $this->assertTrue($result->isReadableLink());
    }

    /**
     * @param array<string, class-string>   $discriminatorMap
     * @param array<class-string, string[]> $subclassGroups
     */
    private function createFactory(ApiProperty $propertyMetadata, array $discriminatorMap, array $subclassGroups): DoctrineDiscriminatorSerializerPropertyMetadataFactory
    {
        $decorated = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $decorated->create(OwnerResource::class, 'relation', Argument::any())->willReturn($propertyMetadata);

        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->isResourceClass(ParentResource::class)->willReturn(true);
        $resourceClassResolver->getResourceClass(null, ParentResource::class)->willReturn(ParentResource::class);

        $classMetadata = $this->prophesize(ClassMetadata::class);
        $classMetadata->discriminatorMap = $discriminatorMap;

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->getClassMetadata(ParentResource::class)->willReturn($classMetadata->reveal());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(ParentResource::class)->willReturn($objectManager->reveal());

        $serializerClassMetadataFactory = $this->prophesize(SerializerClassMetadataFactoryInterface::class);
        foreach ($subclassGroups as $class => $groups) {
            $serializerClassMetadata = new SerializerClassMetadata($class);
            foreach ($groups as $i => $group) {
                $attribute = new SerializerAttributeMetadata('prop'.$i);
                $attribute->addGroup($group);
                $serializerClassMetadata->addAttributeMetadata($attribute);
            }
            $serializerClassMetadataFactory->getMetadataFor($class)->willReturn($serializerClassMetadata);
        }

        return new DoctrineDiscriminatorSerializerPropertyMetadataFactory(
            $managerRegistry->reveal(),
            $decorated->reveal(),
            $serializerClassMetadataFactory->reveal(),
            $resourceClassResolver->reveal(),
        );
    }
}

class OwnerResource
{
}

class ParentResource
{
}

class ChildA extends ParentResource
{
}

class ChildB extends ParentResource
{
}
