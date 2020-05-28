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
use ApiPlatform\Core\Bridge\Symfony\Messenger\DispatchTrait;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\ResourceClassInfoTrait;
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
    private const ALLOWED_KEYS = [
        'topics' => true,
        'data' => true,
        'private' => true,
        'id' => true,
        'type' => true,
        'retry' => true,
    ];

    use DispatchTrait;
    use ResourceClassInfoTrait;

    private $iriConverter;
    private $serializer;
    private $publisher;
    private $expressionLanguage;
    private $createdEntities;
    private $updatedEntities;
    private $deletedEntities;
    private $formats;

    /**
     * @param array<string, string[]|string> $formats
     */
    public function __construct(ResourceClassResolverInterface $resourceClassResolver, IriConverterInterface $iriConverter, ResourceMetadataFactoryInterface $resourceMetadataFactory, SerializerInterface $serializer, array $formats, MessageBusInterface $messageBus = null, callable $publisher = null, ExpressionLanguage $expressionLanguage = null)
    {
        if (null === $messageBus && null === $publisher) {
            throw new InvalidArgumentException('A message bus or a publisher must be provided.');
        }

        $this->resourceClassResolver = $resourceClassResolver;
        $this->iriConverter = $iriConverter;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->serializer = $serializer;
        $this->formats = $formats;
        $this->messageBus = $messageBus;
        $this->publisher = $publisher;
        $this->expressionLanguage = $expressionLanguage ?? class_exists(ExpressionLanguage::class) ? new ExpressionLanguage() : null;
        $this->reset();
    }

    /**
     * Collects created, updated and deleted entities.
     */
    public function onFlush(OnFlushEventArgs $eventArgs): void
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
    public function postFlush(): void
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
        if (null === $resourceClass = $this->getResourceClass($entity)) {
            return;
        }

        $options = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('mercure', false);
        if (false === $options) {
            return;
        }

        if (\is_string($options)) {
            if (null === $this->expressionLanguage) {
                throw new RuntimeException('The Expression Language component is not installed. Try running "composer require symfony/expression-language".');
            }

            $options = $this->expressionLanguage->evaluate($options, ['object' => $entity]);
        }

        if (true === $options) {
            $options = [];
        }

        if (!\is_array($options)) {
            throw new InvalidArgumentException(sprintf('The value of the "mercure" attribute of the "%s" resource class must be a boolean, an array of options or an expression returning this array, "%s" given.', $resourceClass, \gettype($options)));
        }

        foreach ($options as $key => $value) {
            if (0 === $key) {
                if (method_exists(Update::class, 'isPrivate')) {
                    throw new \InvalidArgumentException('Targets do not exist anymore since Mercure 0.10. Mark the update as private instead or downgrade the Mercure Component to version 0.3');
                }

                @trigger_error('Targets do not exist anymore since Mercure 0.10. Mark the update as private instead.', E_USER_DEPRECATED);
                break;
            }

            if (!isset(self::ALLOWED_KEYS[$key])) {
                throw new InvalidArgumentException(sprintf('The option "%s" set in the "mercure" attribute of the "%s" resource does not exist. Existing options: "%s"', $key, $resourceClass, implode('", "', self::ALLOWED_KEYS)));
            }
        }

        if ('deletedEntities' === $property) {
            $this->deletedEntities[(object) [
                'id' => $this->iriConverter->getIriFromItem($entity),
                'iri' => $this->iriConverter->getIriFromItem($entity, UrlGeneratorInterface::ABS_URL),
            ]] = $options;

            return;
        }

        $this->{$property}[$entity] = $options;
    }

    /**
     * @param object $entity
     */
    private function publishUpdate($entity, array $options): void
    {
        if ($entity instanceof \stdClass) {
            // By convention, if the entity has been deleted, we send only its IRI
            // This may change in the feature, because it's not JSON Merge Patch compliant,
            // and I'm not a fond of this approach
            $iri = $options['topics'] ?? $entity->iri;
            /** @var string $data */
            $data = $options['data'] ?? json_encode(['@id' => $entity->id]);
        } else {
            $resourceClass = $this->getObjectClass($entity);
            $context = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('normalization_context', []);

            $iri = $options['topics'] ?? $this->iriConverter->getIriFromItem($entity, UrlGeneratorInterface::ABS_URL);
            $data = $options['data'] ?? $this->serializer->serialize($entity, key($this->formats), $context);
        }

        if (method_exists(Update::class, 'isPrivate')) {
            $update = new Update($iri, $data, $options['private'] ?? false, $options['id'] ?? null, $options['type'] ?? null, $options['retry'] ?? null);
        } else {
            /**
             * Mercure Component < 0.4.
             *
             * @phpstan-ignore-next-line
             */
            $update = new Update($iri, $data, $options);
        }
        $this->messageBus ? $this->dispatch($update) : ($this->publisher)($update);
    }
}
