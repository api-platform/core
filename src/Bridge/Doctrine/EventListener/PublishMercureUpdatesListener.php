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

namespace ApiPlatform\Core\Bridge\Doctrine\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Publishes resources updates to the Mercure hub.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @experimental
 */
final class PublishMercureUpdatesListener
{
    use ClassInfoTrait;

    private $resourceClassResolver;
    private $iriConverter;
    private $resourceMetadataFactory;
    private $serializer;
    private $messageBus;
    private $publisher;
    private $expressionLanguage;
    private $createdEntities;
    private $updatedEntities;
    private $deletedEntities;

    public function __construct(ResourceClassResolverInterface $resourceClassResolver, IriConverterInterface $iriConverter, ResourceMetadataFactoryInterface $resourceMetadataFactory, SerializerInterface $serializer, MessageBusInterface $messageBus = null, callable $publisher = null, ExpressionLanguage $expressionLanguage = null)
    {
        if (null === $messageBus && null === $publisher) {
            throw new InvalidArgumentException('A message bus or a publisher must be provided.');
        }

        $this->resourceClassResolver = $resourceClassResolver;
        $this->iriConverter = $iriConverter;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->serializer = $serializer;
        $this->messageBus = $messageBus;
        $this->publisher = $publisher;
        $this->expressionLanguage = $expressionLanguage ?? class_exists(ExpressionLanguage::class) ? new ExpressionLanguage() : null;
        $this->reset();
    }

    /**
     * Collects created, updated and deleted entities.
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $uow = $eventArgs->getEntityManager()->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->storeEntityToPublish($entity, 'createdEntities');
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->storeEntityToPublish($entity, 'updatedEntities');
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->storeEntityToPublish($entity, 'deletedEntities');
        }
    }

    /**
     * Publishes updates for changes collected on flush, and resets the store.
     */
    public function postFlush()
    {
        try {
            foreach ($this->createdEntities as $entity) {
                $this->publishUpdate($entity, $this->createdEntities[$entity]);
            }

            foreach ($this->updatedEntities as $entity) {
                $this->publishUpdate($entity, $this->updatedEntities[$entity]);
            }

            foreach ($this->deletedEntities as $entity) {
                $this->publishUpdate($entity, $this->deletedEntities[$entity]);
            }
        } finally {
            $this->reset();
        }
    }

    private function reset(): void
    {
        $this->createdEntities = new \SplObjectStorage();
        $this->updatedEntities = new \SplObjectStorage();
        $this->deletedEntities = new \SplObjectStorage();
    }

    /**
     * @param object $entity
     */
    private function storeEntityToPublish($entity, string $property): void
    {
        $resourceClass = $this->getObjectClass($entity);
        if (!$this->resourceClassResolver->isResourceClass($resourceClass)) {
            return;
        }

        $value = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('mercure', false);
        if (false === $value) {
            return;
        }

        if (\is_string($value)) {
            if (null === $this->expressionLanguage) {
                throw new RuntimeException('The Expression Language component is not installed. Try running "composer require symfony/expression-language".');
            }

            $value = $this->expressionLanguage->evaluate($value, ['object' => $entity]);
        }

        if (true === $value) {
            $value = [];
        }

        if (!\is_array($value)) {
            throw new InvalidArgumentException(sprintf('The value of the "mercure" attribute of the "%s" resource class must be a boolean, an array of targets or a valid expression, "%s" given.', $resourceClass, \gettype($value)));
        }

        if ('deletedEntities' === $property) {
            $this->deletedEntities[(object) [
                'id' => $this->iriConverter->getIriFromItem($entity),
                'iri' => $this->iriConverter->getIriFromItem($entity, UrlGeneratorInterface::ABS_URL),
            ]] = $value;

            return;
        }

        $this->$property[$entity] = $value;
    }

    /**
     * @param object|string $entity
     */
    private function publishUpdate($entity, array $targets): void
    {
        if ($entity instanceof \stdClass) {
            // By convention, if the entity has been deleted, we send only its IRI
            // This may change in the feature, because it's not JSON Merge Patch compliant,
            // and I'm not a fond of this approach
            $iri = $entity->iri;
            $data = json_encode(['@id' => $entity->id]);
        } else {
            $iri = $this->iriConverter->getIriFromItem($entity, UrlGeneratorInterface::ABS_URL);
            $data = $this->serializer->serialize($entity, 'jsonld');
        }

        $update = new Update($iri, $data, $targets);
        $this->messageBus ? $this->messageBus->dispatch($update) : ($this->publisher)($update);
    }
}
