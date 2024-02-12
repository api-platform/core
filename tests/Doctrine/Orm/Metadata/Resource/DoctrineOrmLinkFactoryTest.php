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

namespace ApiPlatform\Tests\Doctrine\Orm\Metadata\Resource;

use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Doctrine\Orm\Metadata\Resource\DoctrineOrmLinkFactory;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Metadata;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Resource\Factory\LinkFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\PropertyLinkFactoryInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\Car;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

final class DoctrineOrmLinkFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateLinksFromRelations(): void
    {
        $class = Dummy::class;
        $operation = (new Get())->withClass($class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->hasAssociation('name')->willReturn(false);
        $classMetadataProphecy->hasAssociation('relatedNonResource')->willReturn(true);
        $classMetadataProphecy->hasAssociation('relatedDummy')->willReturn(true);
        $classMetadataProphecy->hasAssociation('relatedDummies')->willReturn(true);
        $classMetadataProphecy->getAssociationTargetClass('relatedNonResource')->willReturn(Car::class);
        $classMetadataProphecy->getAssociationTargetClass('relatedDummy')->willReturn(RelatedDummy::class);
        $classMetadataProphecy->getAssociationTargetClass('relatedDummies')->willReturn(RelatedDummy::class);
        $classMetadataProphecy->getAssociationTargetClass('noMappedBy')->willReturn('NoMappedByClass');
        $classMetadataProphecy->getAssociationMappedByTargetField('relatedNonResource')->willReturn('dummies');
        $classMetadataProphecy->getAssociationMappedByTargetField('relatedDummy')->shouldNotBeCalled();
        $classMetadataProphecy->getAssociationMappedByTargetField('relatedDummies')->willReturn('dummies');
        $classMetadataProphecy->isAssociationInverseSide('relatedNonResource')->willReturn(true);
        $classMetadataProphecy->isAssociationInverseSide('relatedDummy')->willReturn(false);
        $classMetadataProphecy->isAssociationInverseSide('relatedDummies')->willReturn(true);
        $classMetadataProphecy->isAssociationInverseSide('noMappedBy')->willReturn(false);

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getClassMetadata($class)->willReturn($classMetadataProphecy->reveal());
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass($class)->willReturn($entityManagerProphecy->reveal());
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create($class)->willReturn(new PropertyNameCollection(['name', 'relatedNonResource', 'relatedDummy', 'relatedDummies']));
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Car::class)->willReturn(false);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);

        $doctrineOrmLinkFactory = new DoctrineOrmLinkFactory($managerRegistryProphecy->reveal(), $propertyNameCollectionFactoryProphecy->reveal(), $resourceClassResolverProphecy->reveal(), new LinkFactoryStub());

        self::assertEquals([
            new Link(
                fromProperty: 'relatedDummies',
                toProperty: 'dummies',
                fromClass: Dummy::class,
                toClass: RelatedDummy::class,
            ),
        ], $doctrineOrmLinkFactory->createLinksFromRelations($operation));
    }
}

class LinkFactoryStub implements LinkFactoryInterface, PropertyLinkFactoryInterface
{
    public function createLinkFromProperty(Metadata $operation, string $property): Link
    {
        return new Link();
    }

    public function createLinksFromIdentifiers(Metadata $operation): array
    {
        return [];
    }

    public function createLinksFromRelations(Metadata $operation): array
    {
        return [];
    }

    public function createLinksFromAttributes(Metadata $operation): array
    {
        return [];
    }

    public function completeLink(Link $link): Link
    {
        return $link;
    }
}
