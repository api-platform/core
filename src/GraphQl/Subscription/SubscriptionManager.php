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

namespace ApiPlatform\GraphQl\Subscription;

use ApiPlatform\GraphQl\Resolver\Util\IdentifierTrait;
use ApiPlatform\GraphQl\Util\PropertyAccessorValueExtractor;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\ResourceClassInfoTrait;
use ApiPlatform\Metadata\Util\SortTrait;
use ApiPlatform\State\ProcessorInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Manages all the queried subscriptions by creating their ID
 * and saving to a cache the information needed to publish updated data.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class SubscriptionManager implements OperationAwareSubscriptionManagerInterface, SubscriptionPayloadProviderInterface
{
    use IdentifierTrait;
    use ResourceClassInfoTrait;
    use SortTrait;

    public function __construct(private readonly CacheItemPoolInterface $subscriptionsCache, private readonly SubscriptionIdentifierGeneratorInterface $subscriptionIdentifierGenerator, private readonly ProcessorInterface $normalizeProcessor, private readonly IriConverterInterface $iriConverter, private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, private readonly ?IdentifiersExtractorInterface $identifiersExtractor = null, ?ResourceClassResolverInterface $resourceClassResolver = null)
    {
        $this->resourceClassResolver = $resourceClassResolver;
    }

    public function retrieveSubscriptionId(array $context, ?array $result, ?Operation $operation = null): ?string
    {
        $iri = $operation ? $this->getIdentifierFromOperation($operation, $context['args'] ?? []) : $this->getIdentifierFromContext($context);
        if (empty($iri)) {
            return null;
        }

        /** @var ResolveInfo $info */
        $info = $context['info'];
        $fields = $info->getFieldSelection(\PHP_INT_MAX);
        $this->arrayRecursiveSort($fields, 'ksort');

        $previousObject = $context['graphql_context']['previous_object'] ?? null;
        $privateFieldData = $this->getPrivateFieldData($operation, $previousObject);
        $privatePartitionKey = $this->getPrivatePartitionKey($privateFieldData);
        $subscriptionKey = $this->getSubscriptionKey($iri, $operation);

        if ($operation instanceof CollectionOperationInterface) {
            $subscriptionId = $this->updateSubscriptionCollectionCacheData(
                $subscriptionKey,
                $fields,
                $privatePartitionKey
            );
        } else {
            $subscriptionId = $this->updateSubscriptionItemCacheData(
                $subscriptionKey,
                $fields,
                $result,
                $privatePartitionKey
            );
        }

        return $subscriptionId;
    }

    public function getPushPayloads(object $object, string $type = 'update'): array
    {
        $resourceClass = 'delete' === $type ? $object->resourceClass : $this->getObjectClass($object);
        $payloads = [];
        foreach ($this->resourceMetadataCollectionFactory->create($resourceClass) as $resource) {
            foreach ($resource->getGraphQlOperations() ?? [] as $operation) {
                if (!$operation instanceof Subscription) {
                    continue;
                }
                foreach ($this->getPushPayloadsForOperation($object, $operation, $type) as [$id, $data]) {
                    $payloads[$id] = [$id, $data];
                }
            }
        }

        return array_values($payloads);
    }

    public function getPushPayloadsForOperation(object $object, Subscription $operation, string $type = 'update'): array
    {
        if (false === $operation->getMercure()) {
            return [];
        }
        if ('delete' === $type) {
            return $this->getDeletePushPayloads($object, $operation);
        }
        if ('create' === $type && !$operation instanceof CollectionOperationInterface) {
            return [];
        }

        $privateFieldData = $this->getPrivateFieldData($operation, $object);
        $privatePartitionKey = $this->getPrivatePartitionKey($privateFieldData);
        $subscriptionKey = $this->getSubscriptionKey($this->iriConverter->getIriFromResource($object), $operation);
        $payloads = [];
        if ($operation instanceof CollectionOperationInterface) {
            $this->appendNormalizedPayloads($payloads, $this->getSubscriptions($subscriptionKey, $privatePartitionKey), $object, $operation);

            return array_values($payloads);
        }

        $cacheItem = $this->getSubscriptionsCacheItem($subscriptionKey, $privatePartitionKey);
        $subscriptions = $cacheItem->isHit() ? $cacheItem->get() : [];
        $updated = $this->appendNormalizedPayloads($payloads, $subscriptions, $object, $operation, true);
        if ($updated !== $subscriptions) {
            $cacheItem->set($updated);
            $this->subscriptionsCache->save($cacheItem);
        }

        return array_values($payloads);
    }

    /**
     * @return array<array>
     */
    private function getSubscriptions(string $subscriptionKey, ?string $privatePartitionKey = null): array
    {
        $subscriptionsCacheItem = $this->getSubscriptionsCacheItem($subscriptionKey, $privatePartitionKey);

        if ($subscriptionsCacheItem->isHit()) {
            return $subscriptionsCacheItem->get();
        }

        return [];
    }

    private function getSubscriptionsCacheItem(string $subscriptionKey, ?string $privatePartitionKey = null): CacheItemInterface
    {
        return $this->subscriptionsCache->getItem($this->generateCacheKey($subscriptionKey, $privatePartitionKey));
    }

    private function removeItemFromSubscriptionCache(string $subscriptionKey, ?string $privatePartitionKey = null): void
    {
        $cacheKey = $this->generateCacheKey($subscriptionKey, $privatePartitionKey);
        if ($this->subscriptionsCache->hasItem($cacheKey)) {
            $this->subscriptionsCache->deleteItem($cacheKey);
        }
    }

    private function getSubscriptionKey(string $iri, ?Operation $operation): string
    {
        $collection = $operation instanceof CollectionOperationInterface;

        return 'graphql_subscription_'.hash('sha256', serialize([
            'resource' => $operation?->getClass(),
            'operation' => $operation?->getName(),
            'collection' => $collection,
            'iri' => $collection ? null : $iri,
        ]));
    }

    /**
     * @return string[]
     */
    private function getPrivateFields(?Operation $operation): array
    {
        $options = $operation?->getMercure() ?? false;

        return ($options['private'] ?? false) ? ($options['private_fields'] ?? []) : [];
    }

    /**
     * @return array<string, string>
     */
    private function getPrivateFieldData(?Operation $operation, ?object $object): array
    {
        $privateFields = $this->getPrivateFields($operation);
        if ([] === $privateFields) {
            return [];
        }
        if (null === $object) {
            throw new RuntimeException(\sprintf('Cannot resolve private fields for subscription "%s" without a resource object.', $operation?->getName()));
        }

        $privateFieldData = [];
        foreach ($privateFields as $privateField) {
            $privateFieldData[$privateField] = PropertyAccessorValueExtractor::getValue($object, $privateField, $this->identifiersExtractor, $this->resourceClassResolver);
        }

        return $privateFieldData;
    }

    private function getPrivatePartitionKey(array $privateFieldData): ?string
    {
        if ([] === $privateFieldData) {
            return null;
        }

        return hash('sha256', serialize($privateFieldData));
    }

    /**
     * @param array<string, array{string, mixed}> $payloadsBySubscriptionId
     *
     * @param-out array<string, array{string, mixed}>                          $payloadsBySubscriptionId
     *
     * @param array<array{string, array<string, mixed>, array<string, mixed>}> $subscriptions
     *
     * @return array<array{string, array<string, mixed>, array<string, mixed>}>
     */
    private function appendNormalizedPayloads(array &$payloadsBySubscriptionId, array $subscriptions, object $object, Subscription $operation, bool $updateCachedResult = false): array
    {
        foreach ($subscriptions as $index => [$subscriptionId, $subscriptionFields, $subscriptionResult]) {
            $data = $this->normalizeProcessor->process($object, $operation, [], ['fields' => $subscriptionFields]);

            unset($data['clientSubscriptionId']);

            if ($data !== $subscriptionResult) {
                $payloadsBySubscriptionId[$subscriptionId] = [$subscriptionId, $data];

                if ($updateCachedResult) {
                    $subscriptions[$index][2] = $data;
                }
            }
        }

        return $subscriptions;
    }

    private function getDeletePushPayloads(object $object, Subscription $operation): array
    {
        $privateFieldData = [];
        foreach ($this->getPrivateFields($operation) as $privateField) {
            if (!\array_key_exists($privateField, $object->private)) {
                throw new RuntimeException(\sprintf('Private field "%s" is missing from the delete snapshot for subscription "%s".', $privateField, $operation->getName()));
            }
            $privateFieldData[$privateField] = $object->private[$privateField];
        }
        $partition = $this->getPrivatePartitionKey($privateFieldData);
        $key = $this->getSubscriptionKey($object->id, $operation);
        $payload = ['type' => 'delete', 'payload' => ['id' => $object->id, 'iri' => $object->iri, 'type' => $object->type]];
        $payloads = [];
        foreach ($this->getSubscriptions($key, $partition) as [$id]) {
            $payloads[] = [$id, $payload];
        }
        if (!$operation instanceof CollectionOperationInterface) {
            $this->removeItemFromSubscriptionCache($key, $partition);
        }

        return $payloads;
    }

    private function updateSubscriptionItemCacheData(
        string $subscriptionKey,
        array $fields,
        ?array $result,
        ?string $privatePartitionKey = null,
    ): string {
        $cacheKey = $this->generateCacheKey($subscriptionKey, $privatePartitionKey);
        $subscriptionsCacheItem = $this->subscriptionsCache->getItem($cacheKey);
        $subscriptions = [];
        if ($subscriptionsCacheItem->isHit()) {
            /*
             * @var array<array{string, array<string, string|array>, array<string, string|array>}>
             */
            $subscriptions = $subscriptionsCacheItem->get();
            foreach ($subscriptions as [$subscriptionId, $subscriptionFields, $subscriptionResult]) {
                if ($subscriptionFields === $fields) {
                    return $subscriptionId;
                }
            }
        }

        unset($result['clientSubscriptionId']);
        $subscriptionId = $this->subscriptionIdentifierGenerator->generateSubscriptionIdentifier($fields + ['__subscription_scope' => $cacheKey]);
        $subscriptions[] = [$subscriptionId, $fields, $result];
        $subscriptionsCacheItem->set($subscriptions);
        $this->subscriptionsCache->save($subscriptionsCacheItem);

        return $subscriptionId;
    }

    private function updateSubscriptionCollectionCacheData(
        string $subscriptionKey,
        array $fields,
        ?string $privatePartitionKey = null,
    ): string {
        $cacheKey = $this->generateCacheKey($subscriptionKey, $privatePartitionKey);
        $subscriptionCollectionCacheItem = $this->subscriptionsCache->getItem($cacheKey);
        $collectionSubscriptions = [];
        if ($subscriptionCollectionCacheItem->isHit()) {
            $collectionSubscriptions = $subscriptionCollectionCacheItem->get();
            foreach ($collectionSubscriptions as [$subscriptionId, $subscriptionFields, $result]) {
                if ($subscriptionFields === $fields) {
                    return $subscriptionId;
                }
            }
        }
        $subscriptionId = $this->subscriptionIdentifierGenerator->generateSubscriptionIdentifier($fields + ['__collection' => true, '__subscription_scope' => $cacheKey]);
        $collectionSubscriptions[] = [$subscriptionId, $fields, []];
        $subscriptionCollectionCacheItem->set($collectionSubscriptions);
        $this->subscriptionsCache->save($subscriptionCollectionCacheItem);

        return $subscriptionId;
    }

    private function generateCacheKey(string $subscriptionKey, ?string $privatePartitionKey = null): string
    {
        if (null === $privatePartitionKey) {
            return $subscriptionKey;
        }

        return $subscriptionKey.'_'.$privatePartitionKey;
    }
}
