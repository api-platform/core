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

namespace ApiPlatform\Symfony\Doctrine\Odm\EventListener;

use ApiPlatform\HttpCache\PurgerInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Symfony\Doctrine\EventListener\PurgeHttpCacheTagsTrait;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Symfony\Component\ObjectMapper\Metadata\ObjectMapperMetadataFactoryInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Purges responses containing modified documents from the proxy cache.
 */
final class PurgeHttpCacheListener
{
    use PurgeHttpCacheTagsTrait;
    private readonly PropertyAccessorInterface $propertyAccessor;
    private array $scheduledInsertions = [];

    public function __construct(
        private readonly PurgerInterface $purger,
        private readonly IriConverterInterface $iriConverter,
        private readonly ResourceClassResolverInterface $resourceClassResolver,
        ?PropertyAccessorInterface $propertyAccessor = null,
        private readonly ?ObjectMapperInterface $objectMapper = null,
        private readonly ?ObjectMapperMetadataFactoryInterface $objectMapperMetadata = null,
        private readonly ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null,
    ) {
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * Collects tags from inserted, updated and deleted documents, including their previous and current references.
     */
    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $documentManager = $eventArgs->getDocumentManager();
        $uow = $documentManager->getUnitOfWork();

        $upserts = $uow->getScheduledDocumentUpserts();
        $this->scheduledInsertions = array_merge($uow->getScheduledDocumentInsertions(), $upserts);
        foreach ($this->scheduledInsertions as $document) {
            $this->gatherRelationTags($documentManager, $document);
        }

        foreach ($upserts as $document) {
            $this->gatherResourceAndItemTags($document, true);
        }

        foreach ($uow->getScheduledDocumentUpdates() as $document) {
            $this->gatherResourceAndItemTags($document, true);
            $this->gatherChangeSetTags($documentManager, $uow, $document);
            $this->gatherRelationTags($documentManager, $document);
        }

        foreach ($uow->getScheduledDocumentDeletions() as $document) {
            $this->gatherResourceAndItemTags($document, true);
            $this->gatherRelationTags($documentManager, $document);
        }

        foreach ([...$uow->getScheduledCollectionUpdates(), ...$uow->getScheduledCollectionDeletions()] as $collection) {
            $this->gatherCollectionTags($documentManager, $collection);
        }
    }

    /**
     * Purges tags collected during this request, and clears the tag list.
     */
    public function postFlush(): void
    {
        // since IRIs can't always be generated for new documents (missing auto-generated IDs), we need to gather the related IRIs after flush()
        foreach ($this->scheduledInsertions as $document) {
            $this->gatherResourceAndItemTags($document, false);
        }

        $this->scheduledInsertions = [];

        if (empty($this->tags)) {
            return;
        }

        $this->purger->purge(array_values($this->tags));

        $this->tags = [];
    }

    private function gatherChangeSetTags(DocumentManager $documentManager, UnitOfWork $uow, object $document): void
    {
        $metadata = $documentManager->getClassMetadata($this->getObjectClass($document));

        foreach ($uow->getDocumentChangeSet($document) as $property => $change) {
            if (!$metadata->hasReference($property)) {
                continue;
            }

            $this->addTagsFor($change[0] ?? null);
            $this->addTagsFor($change[1] ?? null);
        }
    }

    private function gatherCollectionTags(DocumentManager $documentManager, PersistentCollectionInterface $collection): void
    {
        $this->addTagsFor($collection->getDeleteDiff());

        $owner = $collection->getOwner();
        if (!$owner) {
            return;
        }

        $this->gatherResourceAndItemTags($owner, true);
        $this->gatherRelationTags($documentManager, $owner);
    }

    private function gatherRelationTags(DocumentManager $documentManager, object $document): void
    {
        $metadata = $documentManager->getClassMetadata($this->getObjectClass($document));

        foreach ($metadata->getAssociationNames() as $property) {
            if (!$metadata->hasReference($property)) {
                continue;
            }

            $targetDocument = $metadata->getAssociationTargetClass($property);
            if (null !== $targetDocument && !$this->objectMapper && !$this->resourceClassResolver->isResourceClass($targetDocument)) {
                continue;
            }

            if (!$this->propertyAccessor->isReadable($document, $property)) {
                continue;
            }

            $this->addTagsFor($this->propertyAccessor->getValue($document, $property));
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

        foreach ($value as $v) {
            $this->addTagForItem($v);
        }
    }
}
