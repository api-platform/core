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
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use Doctrine\Common\EventArgs;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs as MongoDbOdmOnFlushEventArgs;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs as OrmOnFlushEventArgs;
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
    use ClassInfoTrait;
    private readonly PropertyAccessorInterface $propertyAccessor;
    private array $tags = [];

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
    public function onFlush(EventArgs $eventArgs): void
    {
        if ($eventArgs instanceof MongoDbOdmOnFlushEventArgs) {
            $this->onFlushOdm($eventArgs->getDocumentManager());

            return;
        }

        if (!$eventArgs instanceof OrmOnFlushEventArgs) {
            return;
        }

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

    private function gatherResourceAndItemTags(object $entity, bool $purgeItem): void
    {
        $resources = $this->getResourcesForEntity($entity);

        foreach ($resources as $resource) {
            try {
                foreach ($this->getCollectionOperations($resource) as $operation) {
                    $iri = $this->iriConverter->getIriFromResource($resource, UrlGeneratorInterface::ABS_PATH, $operation);
                    $this->tags[$iri] = $iri;
                }

                if ($purgeItem) {
                    $this->addTagForItem($entity);
                }
            } catch (OperationNotFoundException|InvalidArgumentException) {
            }
        }
    }

    /**
     * @return iterable<Operation&CollectionOperationInterface>
     */
    private function getCollectionOperations(object $resource): iterable
    {
        if (!$this->resourceMetadataCollectionFactory) {
            yield new GetCollection();

            return;
        }

        foreach ($this->resourceMetadataCollectionFactory->create($this->getObjectClass($resource)) as $apiResource) {
            foreach ($apiResource->getOperations() ?? [] as $operation) {
                if (!$operation instanceof CollectionOperationInterface || !$operation instanceof HttpOperation) {
                    continue;
                }

                if (!empty($operation->getUriVariables())) {
                    continue;
                }

                yield $operation;
            }
        }
    }

    private function gatherRelationTags(EntityManagerInterface $em, object $entity): void
    {
        $associationMappings = $em->getClassMetadata($entity::class)->getAssociationMappings();

        /** @var array|AssociationMapping $associationMapping according to the version of doctrine orm */
        foreach ($associationMappings as $property => $associationMapping) {
            if ($associationMapping instanceof AssociationMapping && !$this->resourceClassResolver->isResourceClass($associationMapping->targetEntity)) {
                return;
            }
            if (!$this->propertyAccessor->isReadable($entity, $property)) {
                return;
            }

            if (
                \is_array($associationMapping)
                && \array_key_exists('targetEntity', $associationMapping)
                && ($targetEntity = $associationMapping['targetEntity'])
                && !$this->resourceClassResolver->isResourceClass($targetEntity)
            ) {
                if (!$this->objectMapper) {
                    return;
                }

                $targetRefl = new \ReflectionClass($targetEntity);
                if ($this->objectMapperMetadata) {
                    if (!$this->objectMapperMetadata->create($targetRefl->newInstanceWithoutConstructor())) {
                        return;
                    }
                } elseif (!$targetRefl->getAttributes(Map::class)) {
                    return;
                }
            }

            $this->addTagsFor($this->propertyAccessor->getValue($entity, $property));
        }
    }

    private function onFlushOdm(DocumentManager $documentManager): void
    {
        $uow = $documentManager->getUnitOfWork();
        $upserts = $uow->getScheduledDocumentUpserts();
        $this->scheduledInsertions = array_merge($uow->getScheduledDocumentInsertions(), $upserts);

        foreach ($this->scheduledInsertions as $document) {
            $this->gatherOdmRelationTags($documentManager, $document);
        }

        foreach ($upserts as $document) {
            $this->gatherResourceAndItemTags($document, true);
        }

        foreach ($uow->getScheduledDocumentUpdates() as $document) {
            $this->gatherResourceAndItemTags($document, true);
            $metadata = $documentManager->getClassMetadata($this->getObjectClass($document));

            foreach ($uow->getDocumentChangeSet($document) as $property => $change) {
                if (!$metadata->hasReference($property)) {
                    continue;
                }

                foreach ($change as $value) {
                    $this->addTagsFor($value);

                    if ($value instanceof PersistentCollectionInterface) {
                        $this->addTagsFor($value->getDeleteDiff());
                    }
                }
            }

            $this->gatherOdmRelationTags($documentManager, $document);
        }

        foreach ($uow->getScheduledDocumentDeletions() as $document) {
            $this->gatherResourceAndItemTags($document, true);
            $this->gatherOdmRelationTags($documentManager, $document);
        }
    }

    private function gatherOdmRelationTags(DocumentManager $documentManager, object $document): void
    {
        $uow = $documentManager->getUnitOfWork();
        $metadata = $documentManager->getClassMetadata($this->getObjectClass($document));

        foreach ($metadata->getAssociationNames() as $property) {
            if (!$metadata->hasReference($property)) {
                continue;
            }

            $targetDocument = $metadata->getAssociationTargetClass($property);
            if (null !== $targetDocument && !$this->isMappedToResource($targetDocument)) {
                continue;
            }

            if (!$this->propertyAccessor->isReadable($document, $property)) {
                continue;
            }

            $value = $this->propertyAccessor->getValue($document, $property);

            if (!$value || \is_scalar($value)) {
                continue;
            }

            if (!is_iterable($value)) {
                if (!$uow->isScheduledForInsert($value)) {
                    $this->addTagsFor($value);
                }

                continue;
            }

            foreach ($value as $related) {
                if (!$uow->isScheduledForInsert($related)) {
                    $this->addTagsFor($related);
                }
            }
        }
    }

    private function isMappedToResource(string $class): bool
    {
        if ($this->resourceClassResolver->isResourceClass($class)) {
            return true;
        }

        if (!$this->objectMapper) {
            return false;
        }

        $reflection = new \ReflectionClass($class);
        if ($this->objectMapperMetadata) {
            return (bool) $this->objectMapperMetadata->create($reflection->newInstanceWithoutConstructor());
        }

        return [] !== $reflection->getAttributes(Map::class);
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

    private function addTagForItem(mixed $value): void
    {
        $resources = $this->getResourcesForEntity($value);

        foreach ($resources as $resource) {
            try {
                $iri = $this->iriConverter->getIriFromResource($resource);
                $this->tags[$iri] = $iri;
            } catch (OperationNotFoundException|InvalidArgumentException) {
            }
        }
    }

    private function getResourcesForEntity(object $entity): array
    {
        $resources = [];

        if (!$this->resourceClassResolver->isResourceClass($class = $this->getObjectClass($entity))) {
            // is the entity mapped to resource(s)?
            if (!$this->objectMapper) {
                return [];
            }

            if ($this->objectMapperMetadata) {
                $mappings = $this->objectMapperMetadata->create($entity);

                if (!$mappings) {
                    return [];
                }

                $resources = array_map(
                    fn ($mapping) => $this->objectMapper->map($entity, $mapping->target),
                    $mappings
                );
            } else {
                $mapAttributes = (new \ReflectionClass($class))->getAttributes(Map::class);

                if (!$mapAttributes) {
                    return [];
                }

                // loop over all mappings to fetch all resources mapped to this entity
                $resources = array_map(
                    fn ($mapAttribute) => $this->objectMapper->map($entity, $mapAttribute->newInstance()->target),
                    $mapAttributes
                );
            }
        } else {
            $resources[] = $entity;
        }

        return $resources;
    }
}
