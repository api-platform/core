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

use ApiPlatform\Doctrine\Common\Messenger\DispatchTrait;
use ApiPlatform\GraphQl\Subscription\MercureSubscriptionIriGeneratorInterface as GraphQlMercureSubscriptionIriGeneratorInterface;
use ApiPlatform\GraphQl\Subscription\SubscriptionManagerInterface as GraphQlSubscriptionManagerInterface;
use ApiPlatform\GraphQl\Util\PropertyAccessorValueExtractor;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\ResourceClassInfoTrait;
use ApiPlatform\State\Util\MercureOptionsResolver;
use ApiPlatform\Symfony\Messenger\MercureHubStamp;
use Doctrine\Common\EventArgs;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs as MongoDbOdmOnFlushEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs as OrmOnFlushEventArgs;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Publishes resources updates to the Mercure hub.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class PublishMercureUpdatesListener
{
    use DispatchTrait;
    use ResourceClassInfoTrait;
    private readonly MercureOptionsResolver $optionsResolver;
    /** @var list<array{object: object, options: array, operation: HttpOperation}|array{subscriptions: list<array{object: object, operation: Subscription}>}> */
    private array $createdObjects;
    /** @var list<array{object: object, options: array, operation: HttpOperation}|array{subscriptions: list<array{object: object, operation: Subscription}>}> */
    private array $updatedObjects;
    /** @var list<array{object: object, options: array, operation: HttpOperation}|array{subscriptions: list<array{object: object, operation: Subscription}>}> */
    private array $deletedObjects;

    /**
     * @param array<string, string[]|string> $formats
     */
    public function __construct(ResourceClassResolverInterface $resourceClassResolver, private readonly IriConverterInterface $iriConverter, ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, private readonly SerializerInterface $serializer, private readonly array $formats, ?MessageBusInterface $messageBus = null, private readonly ?HubRegistry $hubRegistry = null, private readonly ?GraphQlSubscriptionManagerInterface $graphQlSubscriptionManager = null, private readonly ?GraphQlMercureSubscriptionIriGeneratorInterface $graphQlMercureSubscriptionIriGenerator = null, ?ExpressionLanguage $expressionLanguage = null, private bool $includeType = false, private readonly ?IdentifiersExtractorInterface $identifiersExtractor = null, ?MercureOptionsResolver $optionsResolver = null)
    {
        if (null === $messageBus && null === $hubRegistry) {
            throw new InvalidArgumentException('A message bus or a hub registry must be provided.');
        }

        $this->resourceClassResolver = $resourceClassResolver;

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->messageBus = $messageBus;
        $this->optionsResolver = $optionsResolver ?? new MercureOptionsResolver($resourceClassResolver, $iriConverter, $resourceMetadataFactory, $expressionLanguage);
        $this->reset();
    }

    /**
     * Collects created, updated and deleted objects.
     */
    public function onFlush(EventArgs $eventArgs): void
    {
        if ($eventArgs instanceof OrmOnFlushEventArgs) {
            // @phpstan-ignore-next-line
            $uow = method_exists($eventArgs, 'getObjectManager') ? $eventArgs->getObjectManager()->getUnitOfWork() : $eventArgs->getEntityManager()->getUnitOfWork();
        } elseif ($eventArgs instanceof MongoDbOdmOnFlushEventArgs) {
            $uow = $eventArgs->getDocumentManager()->getUnitOfWork();
        } else {
            return;
        }

        $methodName = $eventArgs instanceof OrmOnFlushEventArgs ? 'getScheduledEntityInsertions' : 'getScheduledDocumentInsertions';
        foreach ($uow->{$methodName}() as $object) {
            $this->storeObjectToPublish($object, 'createdObjects');
        }

        $methodName = $eventArgs instanceof OrmOnFlushEventArgs ? 'getScheduledEntityUpdates' : 'getScheduledDocumentUpdates';
        foreach ($uow->{$methodName}() as $object) {
            $this->storeObjectToPublish($object, 'updatedObjects');
        }

        $methodName = $eventArgs instanceof OrmOnFlushEventArgs ? 'getScheduledEntityDeletions' : 'getScheduledDocumentDeletions';
        foreach ($uow->{$methodName}() as $object) {
            $this->storeObjectToPublish($object, 'deletedObjects');
        }
    }

    /**
     * Publishes updates for changes collected on flush, and resets the store.
     */
    public function postFlush(): void
    {
        try {
            $this->publishUpdates($this->createdObjects, 'create');
            $this->createdObjects = [];

            $this->publishUpdates($this->updatedObjects, 'update');
            $this->updatedObjects = [];

            $this->publishUpdates($this->deletedObjects, 'delete');
            $this->deletedObjects = [];
        } finally {
            $this->reset();
        }
    }

    private function reset(): void
    {
        $this->createdObjects = [];
        $this->updatedObjects = [];
        $this->deletedObjects = [];
    }

    private function storeObjectToPublish(object $object, string $property): void
    {
        if (null === $resourceClass = $this->getResourceClass($object)) {
            return;
        }

        $resourceMetadataCollection = $this->resourceMetadataFactory->create($resourceClass);
        $this->collectHttpPublications($object, $resourceClass, $resourceMetadataCollection, $property);
        $this->collectGraphQlPublications($object, $resourceClass, $resourceMetadataCollection, $property);
    }

    private function collectHttpPublications(object $object, string $resourceClass, ResourceMetadataCollection $resourceMetadataCollection, string $property): void
    {
        foreach ($resourceMetadataCollection as $resourceMetadata) {
            /** @var ?HttpOperation $operation */
            $operation = null;
            foreach ($resourceMetadata->getOperations() ?? [] as $op) {
                if (!$op instanceof CollectionOperationInterface) {
                    $operation = $op;
                    break;
                }
            }

            if (null === $operation) {
                continue;
            }

            if (null === $options = $this->optionsResolver->resolve($operation, $object, $resourceClass)) {
                continue;
            }

            if ('deletedObjects' === $property) {
                $types = $operation->getTypes();
                if (null === $types) {
                    $types = [$operation->getShortName()];
                }

                // We need to evaluate it here, because in publishHttpUpdate() the resource would be already deleted
                $this->optionsResolver->evaluateTopics($options, $object);

                $this->deletedObjects[] = [
                    'object' => (object) [
                        'resourceClass' => $resourceClass,
                        'id' => $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_PATH, $operation),
                        'iri' => $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_URL, $operation),
                        'type' => 1 === \count($types) ? $types[0] : $types,
                    ],
                    'options' => $options,
                    'operation' => $operation,
                ];

                continue;
            }

            $this->{$property}[] = ['object' => $object, 'options' => $options, 'operation' => $operation];
        }
    }

    private function collectGraphQlPublications(object $object, string $resourceClass, ResourceMetadataCollection $resourceMetadataCollection, string $property): void
    {
        if (!$this->graphQlSubscriptionManager || !$this->graphQlMercureSubscriptionIriGenerator) {
            return;
        }
        $privateValues = [];
        $publications = [];
        foreach ($resourceMetadataCollection as $resourceMetadata) {
            foreach ($resourceMetadata->getGraphQlOperations() ?? [] as $operation) {
                if (!$operation instanceof Subscription || ('createdObjects' === $property && !$operation instanceof CollectionOperationInterface)) {
                    continue;
                }
                if (null === $options = $this->optionsResolver->resolve($operation, $object, $resourceClass)) {
                    continue;
                }
                $operation = $operation->withMercure($options);
                $toPublish = $object;
                if ('deletedObjects' === $property) {
                    $private = [];
                    foreach (($options['private'] ?? false) ? ($options['private_fields'] ?? []) : [] as $field) {
                        $private[$field] = $privateValues[$field] ??= PropertyAccessorValueExtractor::getValue($object, $field, $this->identifiersExtractor, $this->resourceClassResolver);
                    }
                    $types = $resourceMetadata->getTypes() ?? [$resourceMetadata->getShortName()];
                    $toPublish = (object) [
                        'resourceClass' => $resourceClass,
                        'id' => $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_PATH),
                        'iri' => $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_URL),
                        'type' => 1 === \count($types) ? $types[0] : $types,
                        'private' => $private,
                    ];
                }
                $publications[] = ['object' => $toPublish, 'operation' => $operation];
            }
        }
        if ([] !== $publications) {
            $this->{$property}[] = ['subscriptions' => $publications];
        }
    }

    /**
     * @param list<array{object: object, options: array, operation: HttpOperation}|array{subscriptions: list<array{object: object, operation: Subscription}>}> $entries
     */
    private function publishUpdates(array $entries, string $type): void
    {
        foreach ($entries as $entry) {
            if (isset($entry['subscriptions'])) {
                foreach ($this->graphQlSubscriptionManager->getUpdates($entry['subscriptions'], $type) as [$operation, $update]) {
                    $options = $operation->getMercure();
                    $this->publish($this->buildUpdate(
                        $this->graphQlMercureSubscriptionIriGenerator->generateTopicIri($update->getId()),
                        (string) (new JsonResponse($update->data))->getContent(),
                        $options
                    ), $options);
                    $this->graphQlSubscriptionManager->acknowledge($update);
                }

                continue;
            }

            $this->publishHttpUpdate($entry['object'], $entry['options'], $entry['operation']);
        }
    }

    private function publishHttpUpdate(object $object, array $options, HttpOperation $operation): void
    {
        if ($object instanceof \stdClass) {
            // By convention, if the object has been deleted, we send only its IRI and its type.
            // This may change in the feature, because it's not JSON Merge Patch compliant,
            // and I'm not a fond of this approach.
            $iri = $options['topics'] ?? $object->iri;
            /** @var non-empty-string $data */
            $data = json_encode(['@id' => $object->id] + ($this->includeType ? ['@type' => $object->type] : []), \JSON_THROW_ON_ERROR);
        } else {
            $context = $options['normalization_context'] ?? $operation->getNormalizationContext() ?? [];

            // We need to evaluate it here, because in storeObjectToPublish() the resource would not have been persisted yet
            $this->optionsResolver->evaluateTopics($options, $object);

            $iri = $options['topics'] ?? $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_URL, $operation);
            $data = $options['data'] ?? $this->serializer->serialize($object, key($this->formats), $context);
        }

        $this->publish($this->buildUpdate($iri, $data, $options), $options);
    }

    private function publish(Update $update, array $options): void
    {
        if ($options['enable_async_update'] && $this->messageBus) {
            $this->dispatch(new Envelope($update, [new MercureHubStamp($options['hub'] ?? null)]));

            return;
        }

        $this->hubRegistry->getHub($options['hub'] ?? null)->publish($update);
    }

    /**
     * @param string|string[] $iri
     */
    private function buildUpdate(string|array $iri, string $data, array $options): Update
    {
        return new Update($iri, $data, $options['private'] ?? false, $options['id'] ?? null, $options['type'] ?? null, $options['retry'] ?? null);
    }
}
