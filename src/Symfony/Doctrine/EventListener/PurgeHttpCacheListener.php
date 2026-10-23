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

namespace ApiPlatform\Symfony\Doctrine\EventListener;

use ApiPlatform\HttpCache\PurgerInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Metadata\ObjectMapperMetadataFactoryInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Purges responses containing modified entities from the proxy cache.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class PurgeHttpCacheListener
{
    use PurgeHttpCacheTagsTrait;
    private readonly PropertyAccessorInterface $propertyAccessor;
    private array $scheduledInsertions = [];

    public function __construct(private readonly PurgerInterface $purger,
        private readonly IriConverterInterface $iriConverter,
        private readonly ResourceClassResolverInterface $resourceClassResolver,
        ?PropertyAccessorInterface $propertyAccessor = null,
        private readonly ?ObjectMapperInterface $objectMapper = null,
        private readonly ?ObjectMapperMetadataFactoryInterface $objectMapperMetadata = null,
        private readonly ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null)
    {
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * Collects tags from the previous and the current version of the updated entities to purge related documents.
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs): void
    {
        $object = $eventArgs->getObject();
        $this->gatherResourceAndItemTags($object, true);

        $changeSet = $eventArgs->getEntityChangeSet();
        // @phpstan-ignore-next-line
        $objectManager = method_exists($eventArgs, 'getObjectManager') ? $eventArgs->getObjectManager() : $eventArgs->getEntityManager();
        $associationMappings = $objectManager->getClassMetadata(\get_class($eventArgs->getObject()))->getAssociationMappings();

        foreach ($changeSet as $key => $value) {
            if (!isset($associationMappings[$key])) {
                continue;
            }

            $this->addTagsFor($value[0]);
            $this->addTagsFor($value[1]);
        }
    }

    /**
     * Collects tags from updated and deleted entities, including relations.
     */
    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        // @phpstan-ignore-next-line
        $em = method_exists($eventArgs, 'getObjectManager') ? $eventArgs->getObjectManager() : $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($this->scheduledInsertions = $uow->getScheduledEntityInsertions() as $entity) {
            // inserts shouldn't add new related entities, we should be able to gather related tags already
            $this->gatherRelationTags($em, $entity);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->gatherResourceAndItemTags($entity, true);
            $this->gatherRelationTags($em, $entity);
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->gatherResourceAndItemTags($entity, true);
            $this->gatherRelationTags($em, $entity);
        }
    }

    /**
     * Purges tags collected during this request, and clears the tag list.
     */
    public function postFlush(): void
    {
        // since IRIs can't always be generated for new entities (missing auto-generated IDs), we need to gather the related IRIs after flush()
        foreach ($this->scheduledInsertions as $entity) {
            $this->gatherResourceAndItemTags($entity, false);
        }

        if (empty($this->tags)) {
            return;
        }

        $this->purger->purge(array_values($this->tags));

        $this->tags = [];
    }

    private function gatherRelationTags(EntityManagerInterface $em, object $entity): void
    {
        $associationMappings = $em->getClassMetadata($entity::class)->getAssociationMappings();

        /** @var array|AssociationMapping $associationMapping according to the version of doctrine orm */
        foreach ($associationMappings as $property => $associationMapping) {
            if ($associationMapping instanceof AssociationMapping && !$this->resourceClassResolver->isResourceClass($associationMapping->targetEntity)) {
                continue;
            }
            if (!$this->propertyAccessor->isReadable($entity, $property)) {
                continue;
            }

            if (
                \is_array($associationMapping)
                && \array_key_exists('targetEntity', $associationMapping)
                && ($targetEntity = $associationMapping['targetEntity'])
                && !$this->resourceClassResolver->isResourceClass($targetEntity)
            ) {
                if (!$this->objectMapper) {
                    continue;
                }

                $targetRefl = new \ReflectionClass($targetEntity);
                if ($this->objectMapperMetadata) {
                    if (!$this->objectMapperMetadata->create($targetRefl->newInstanceWithoutConstructor())) {
                        continue;
                    }
                } elseif (!$targetRefl->getAttributes(Map::class)) {
                    continue;
                }
            }

            $this->addTagsFor($this->propertyAccessor->getValue($entity, $property));
        }
    }

    private function addTagsFor(mixed $value): void
    {
        if (!$value || \is_scalar($value)) {
            return;
        }

        if (!is_iterable($value)) {
            $this->addTagForItem($value);

            return;
        }

        if ($value instanceof PersistentCollection) {
            $value = clone $value;
        }

        foreach ($value as $v) {
            $this->addTagForItem($v);
        }
    }
}
