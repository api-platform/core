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

namespace ApiPlatform\Doctrine\EventListener;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\HttpCache\PurgerInterface;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\PersistentCollection;
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

    public const IRI_RELATION_DELIMITER = '#';

    public function __construct(private readonly PurgerInterface $purger, private readonly IriConverterInterface $iriConverter, private readonly ResourceClassResolverInterface $resourceClassResolver, PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * Collects tags from the previous and the current version of the updated entities to purge related documents.
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs): void
    {
        $object = $eventArgs->getObject();
        $this->addTagForItem($object);

        $changeSet = $eventArgs->getEntityChangeSet();
        $objectManager = method_exists($eventArgs, 'getObjectManager') ? $eventArgs->getObjectManager() : $eventArgs->getEntityManager();
        $associationMappings = $objectManager->getClassMetadata(ClassUtils::getClass($eventArgs->getObject()))->getAssociationMappings();

        foreach ($changeSet as $key => $value) {
            if (!isset($associationMappings[$key])) {
                continue;
            }
            $mappings = $associationMappings[$key];
            $relatedProperty = $mappings['isOwningSide'] ? $mappings['inversedBy'] : $mappings['mappedBy'];
            if (!$relatedProperty) {
                continue;
            }

            $this->addTagsFor($value[0], $relatedProperty);
            $this->addTagsFor($value[1], $relatedProperty);
        }
    }

    /**
     * Collects tags from inserted and deleted entities, including relations.
     */
    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $em = method_exists($eventArgs, 'getObjectManager') ? $eventArgs->getObjectManager() : $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->gatherResourceTags($entity);
            $this->gatherRelationTags($em, $entity);
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->addTagForItem($entity);
            $this->gatherResourceTags($entity);
            $this->gatherRelationTags($em, $entity);
        }
    }

    /**
     * Purges tags collected during this request, and clears the tag list.
     */
    public function postFlush(): void
    {
        if (empty($this->tags)) {
            return;
        }

        $this->purger->purge(array_values($this->tags));

        $this->tags = [];
    }

    private function gatherResourceTags(object $entity): void
    {
        try {
            $resourceClass = $this->resourceClassResolver->getResourceClass($entity);
            $iri = $this->iriConverter->getIriFromResource($resourceClass, UrlGeneratorInterface::ABS_PATH, new GetCollection());
            $this->tags[$iri] = $iri;
        } catch (OperationNotFoundException|InvalidArgumentException) {
        }
    }

    private function gatherRelationTags(EntityManagerInterface $em, object $entity): void
    {
        $associationMappings = $em->getClassMetadata(ClassUtils::getClass($entity))->getAssociationMappings();
        foreach ($associationMappings as $property => $mappings) {
            $relatedProperty = $mappings['isOwningSide'] ? $mappings['inversedBy'] : $mappings['mappedBy'];
            if (!$relatedProperty) {
                continue;
            }

            if (!$this->propertyAccessor->isReadable($entity, $property)) {
                continue;
            }
            $relatedObject = $this->propertyAccessor->getValue($entity, $property);
            if ($relatedObject === $entity) {
                continue;
            }

            $this->addTagsFor(
                $relatedObject,
                $relatedProperty
            );
        }
    }

    private function addTagsFor(mixed $value, string $property = null): void
    {
        if (!$value || \is_scalar($value)) {
            return;
        }

        if (!is_iterable($value)) {
            $this->addTagForItem($value, $property);

            return;
        }

        if ($value instanceof PersistentCollection) {
            $value = clone $value;
        }

        foreach ($value as $v) {
            $this->addTagForItem($v, $property);
        }
    }

    private function addTagForItem(mixed $value, string $property = null): void
    {
        if (!$this->resourceClassResolver->isResourceClass($this->getObjectClass($value))) {
            return;
        }

        try {
            $iri = $this->iriConverter->getIriFromResource($value);
            if ($property) {
                $iri .= self::IRI_RELATION_DELIMITER.$property;
            }
            $this->tags[$iri] = $iri;
        } catch (RuntimeException|InvalidArgumentException) {
        }
    }
}
