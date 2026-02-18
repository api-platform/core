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
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\ResourceClassInfoTrait;
use Doctrine\Common\EventArgs;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs as MongoDbOdmOnFlushEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs as OrmOnFlushEventArgs;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Update;
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
    private const ALLOWED_KEYS = [
        'topics' => true,
        'data' => true,
        'private' => true,
        'id' => true,
        'type' => true,
        'retry' => true,
        'normalization_context' => true,
        'hub' => true,
        'enable_async_update' => true,
    ];
    private readonly ?ExpressionLanguage $expressionLanguage;
    /** @var list<array{object: object, options: array, operation: ?Operation}> */
    private array $createdObjects;
    /** @var list<array{object: object, options: array, operation: ?Operation}> */
    private array $updatedObjects;
    /** @var list<array{object: object, options: array, operation: ?Operation}> */
    private array $deletedObjects;

    /**
     * @param array<string, string[]|string> $formats
     */
    public function __construct(ResourceClassResolverInterface $resourceClassResolver, private readonly IriConverterInterface $iriConverter, ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, private readonly SerializerInterface $serializer, private readonly array $formats, ?MessageBusInterface $messageBus = null, private readonly ?HubRegistry $hubRegistry = null, private readonly ?GraphQlSubscriptionManagerInterface $graphQlSubscriptionManager = null, private readonly ?GraphQlMercureSubscriptionIriGeneratorInterface $graphQlMercureSubscriptionIriGenerator = null, ?ExpressionLanguage $expressionLanguage = null, private bool $includeType = false)
    {
        if (null === $messageBus && null === $hubRegistry) {
            throw new InvalidArgumentException('A message bus or a hub registry must be provided.');
        }

        $this->resourceClassResolver = $resourceClassResolver;

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->messageBus = $messageBus;
        $this->expressionLanguage = $expressionLanguage ?? (class_exists(ExpressionLanguage::class) ? new ExpressionLanguage() : null);
        $this->reset();

        if ($this->expressionLanguage) {
            $rawurlencode = ExpressionFunction::fromPhp('rawurlencode', 'escape');
            $this->expressionLanguage->addFunction($rawurlencode);

            $this->expressionLanguage->addFunction(
                new ExpressionFunction('get_operation', static fn (string $apiResource, string $name): string => \sprintf('getOperation(%s, %s)', $apiResource, $name), static fn (array $arguments, $apiResource, string $name): Operation => $resourceMetadataFactory->create($resourceClassResolver->getResourceClass($apiResource))->getOperation($name))
            );
            $this->expressionLanguage->addFunction(
                new ExpressionFunction('iri', static fn (string $apiResource, int $referenceType = UrlGeneratorInterface::ABS_URL, ?string $operation = null): string => \sprintf('iri(%s, %d, %s)', $apiResource, $referenceType, $operation), static fn (array $arguments, $apiResource, int $referenceType = UrlGeneratorInterface::ABS_URL, $operation = null): string => $iriConverter->getIriFromResource($apiResource, $referenceType, $operation))
            );
        }
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
            foreach ($this->createdObjects as $entry) {
                $this->publishUpdate($entry['object'], $entry['options'], 'create', $entry['operation']);
            }
            $this->createdObjects = [];

            foreach ($this->updatedObjects as $entry) {
                $this->publishUpdate($entry['object'], $entry['options'], 'update', $entry['operation']);
            }
            $this->updatedObjects = [];

            foreach ($this->deletedObjects as $entry) {
                $this->publishUpdate($entry['object'], $entry['options'], 'delete', $entry['operation']);
            }
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

            $options = $operation->getMercure() ?? false;

            if (\is_string($options)) {
                if (null === $this->expressionLanguage) {
                    throw new RuntimeException('The Expression Language component is not installed. Try running "composer require symfony/expression-language".');
                }

                $options = $this->expressionLanguage->evaluate($options, ['object' => $object]);
            }

            if (false === $options) {
                continue;
            }

            if (true === $options) {
                $options = [];
            }

            if (!\is_array($options)) {
                throw new InvalidArgumentException(\sprintf('The value of the "mercure" attribute of the "%s" resource class must be a boolean, an array of options or an expression returning this array, "%s" given.', $resourceClass, \gettype($options)));
            }

            foreach ($options as $key => $value) {
                if (!isset(self::ALLOWED_KEYS[$key])) {
                    throw new InvalidArgumentException(\sprintf('The option "%s" set in the "mercure" attribute of the "%s" resource does not exist. Existing options: "%s"', $key, $resourceClass, implode('", "', array_keys(self::ALLOWED_KEYS))));
                }
            }

            $options['enable_async_update'] ??= true;

            if ('deletedObjects' === $property) {
                $types = $operation->getTypes();
                if (null === $types) {
                    $types = [$operation->getShortName()];
                }

                // We need to evaluate it here, because in publishUpdate() the resource would be already deleted
                $this->evaluateTopics($options, $object);

                $this->deletedObjects[] = [
                    'object' => (object) [
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

    private function publishUpdate(object $object, array $options, string $type, ?Operation $operation = null): void
    {
        if ($object instanceof \stdClass) {
            // By convention, if the object has been deleted, we send only its IRI and its type.
            // This may change in the feature, because it's not JSON Merge Patch compliant,
            // and I'm not a fond of this approach.
            $iri = $options['topics'] ?? $object->iri;
            /** @var non-empty-string $data */
            $data = json_encode(['@id' => $object->id] + ($this->includeType ? ['@type' => $object->type] : []), \JSON_THROW_ON_ERROR);
        } else {
            $context = $options['normalization_context'] ?? $operation?->getNormalizationContext() ?? [];

            // We need to evaluate it here, because in storeObjectToPublish() the resource would not have been persisted yet
            $this->evaluateTopics($options, $object);

            $iri = $options['topics'] ?? $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_URL, $operation);
            $data = $options['data'] ?? $this->serializer->serialize($object, key($this->formats), $context);
        }

        $updates = array_merge([$this->buildUpdate($iri, $data, $options)], $this->getGraphQlSubscriptionUpdates($object, $options, $type));
        foreach ($updates as $update) {
            if ($options['enable_async_update'] && $this->messageBus) {
                $this->dispatch($update);
                continue;
            }

            $this->hubRegistry->getHub($options['hub'] ?? null)->publish($update);
        }
    }

    private function evaluateTopics(array &$options, object $object): void
    {
        if (!($options['topics'] ?? false)) {
            return;
        }

        $topics = [];
        foreach ((array) $options['topics'] as $topic) {
            if (!\is_string($topic)) {
                $topics[] = $topic;
                continue;
            }

            if (!str_starts_with($topic, '@=')) {
                $topics[] = $topic;
                continue;
            }

            if (null === $this->expressionLanguage) {
                throw new \LogicException('The "@=" expression syntax cannot be used without the Expression Language component. Try running "composer require symfony/expression-language".');
            }

            $topics[] = $this->expressionLanguage->evaluate(substr($topic, 2), ['object' => $object]);
        }

        $options['topics'] = $topics;
    }

    /**
     * @return Update[]
     */
    private function getGraphQlSubscriptionUpdates(object $object, array $options, string $type): array
    {
        if ('update' !== $type || !$this->graphQlSubscriptionManager || !$this->graphQlMercureSubscriptionIriGenerator) {
            return [];
        }

        $payloads = $this->graphQlSubscriptionManager->getPushPayloads($object);

        $updates = [];
        foreach ($payloads as [$subscriptionId, $data]) {
            $updates[] = $this->buildUpdate(
                $this->graphQlMercureSubscriptionIriGenerator->generateTopicIri($subscriptionId),
                (string) (new JsonResponse($data))->getContent(),
                $options
            );
        }

        return $updates;
    }

    /**
     * @param string|string[] $iri
     */
    private function buildUpdate(string|array $iri, string $data, array $options): Update
    {
        return new Update($iri, $data, $options['private'] ?? false, $options['id'] ?? null, $options['type'] ?? null, $options['retry'] ?? null);
    }
}
