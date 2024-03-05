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

namespace ApiPlatform\Doctrine\Orm\Metadata\Resource;

use ApiPlatform\Api\ResourceClassResolverInterface as LegacyResourceClassResolverInterface;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Metadata;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\LinkFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\PropertyLinkFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @internal
 */
final class DoctrineOrmLinkFactory implements LinkFactoryInterface, PropertyLinkFactoryInterface
{
    public function __construct(private readonly ManagerRegistry $managerRegistry, private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, private readonly ResourceClassResolverInterface|LegacyResourceClassResolverInterface $resourceClassResolver, private readonly LinkFactoryInterface&PropertyLinkFactoryInterface $linkFactory)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createLinkFromProperty(Metadata $operation, string $property): Link
    {
        return $this->linkFactory->createLinkFromProperty($operation, $property);
    }

    /**
     * {@inheritdoc}
     */
    public function createLinksFromIdentifiers(Metadata $operation): array
    {
        return $this->linkFactory->createLinksFromIdentifiers($operation);
    }

    /**
     * {@inheritdoc}
     */
    public function createLinksFromRelations(Metadata $operation): array
    {
        $links = $this->linkFactory->createLinksFromRelations($operation);

        $resourceClass = $operation->getClass();
        if (!($manager = $this->managerRegistry->getManagerForClass($resourceClass)) instanceof EntityManagerInterface) {
            return $links;
        }

        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
            $doctrineMetadata = $manager->getClassMetadata($resourceClass);
            if (!$doctrineMetadata->hasAssociation($property)) {
                continue;
            }

            if (!$doctrineMetadata->isAssociationInverseSide($property)) {
                continue;
            }

            if (!($mappedBy = $doctrineMetadata->getAssociationMappedByTargetField($property))) {
                continue;
            }

            $relationClass = $doctrineMetadata->getAssociationTargetClass($property);
            if (!$this->resourceClassResolver->isResourceClass($relationClass)) {
                continue;
            }

            $link = new Link(fromProperty: $property, toProperty: $mappedBy, fromClass: $resourceClass, toClass: $relationClass);
            $link = $this->completeLink($link);
            $links[] = $link;
        }

        return $links;
    }

    /**
     * {@inheritdoc}
     */
    public function createLinksFromAttributes(Metadata $operation): array
    {
        return $this->linkFactory->createLinksFromAttributes($operation);
    }

    /**
     * {@inheritdoc}
     */
    public function completeLink(Link $link): Link
    {
        return $this->linkFactory->completeLink($link);
    }
}
