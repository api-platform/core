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
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\PropertyAccessorValueExtractor;
use ApiPlatform\Metadata\Util\ResourceClassInfoTrait;
use ApiPlatform\Metadata\Util\SortTrait;
use ApiPlatform\State\ProcessorInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
/**
 * Manages all the queried subscriptions by creating their ID
 * and saving to a cache the information needed to publish updated data.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class SubscriptionManager implements OperationAwareSubscriptionManagerInterface
{
    use IdentifierTrait;
    use ResourceClassInfoTrait;
    use SortTrait;

    public function __construct(private readonly CacheItemPoolInterface $subscriptionsCache, private readonly SubscriptionIdentifierGeneratorInterface $subscriptionIdentifierGenerator, private readonly ProcessorInterface $normalizeProcessor, private readonly IriConverterInterface $iriConverter, private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory)
    {
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

        $options = $operation ? ($operation->getMercure() ?? false) : false;
        $private = $options['private'] ?? false;
        $privateFields = $options['private_fields'] ?? [];
        $this->validateMercureOptions($private, $privateFields);
        $previousObject = $context['graphql_context']['previous_object'] ?? null;
        $privateFieldData = $this->getPrivateFieldData($private, $privateFields, $previousObject);
        $privatePartitionKey = $this->getPrivatePartitionKey($privateFieldData);

        if ($operation instanceof CollectionOperationInterface) {
            $subscriptionId = $this->updateSubscriptionCollectionCacheData(
                $this->getCollectionSubscriptionIriFromOperation($iri, $operation),
                $fields,
                $privatePartitionKey
            );
        } else {
            $subscriptionId = $this->updateSubscriptionItemCacheData(
                $iri,
                $fields,
                $result,
                $privatePartitionKey
            );
        }

        return $subscriptionId;
    }

    public function getPushPayloads(object $object, string $type = 'update'): array
    {
        if ('delete' === $type) {
            return $this->getDeletePushPayloads($object);
        }

        return $this->getCreatedOrUpdatedPayloads($object, $type);
    }

    /**
     * @return array<array>
     */
    private function getSubscriptionsFromIri(string $iri, ?string $privatePartitionKey = null): array
    {
        $subscriptionsCacheItem = $this->getSubscriptionsCacheItem($iri, $privatePartitionKey);

        if ($subscriptionsCacheItem->isHit()) {
            return $subscriptionsCacheItem->get();
        }

        return [];
    }

    private function getSubscriptionsCacheItem(string $iri, ?string $privatePartitionKey = null): CacheItemInterface
    {
        return $this->subscriptionsCache->getItem($this->generateCacheKey($iri, $privatePartitionKey));
    }

    private function removeItemFromSubscriptionCache(string $iri, ?string $privatePartitionKey = null): void
    {
        $cacheKey = $this->generateCacheKey($iri, $privatePartitionKey);
        if ($this->subscriptionsCache->hasItem($cacheKey)) {
            $this->subscriptionsCache->deleteItem($cacheKey);
        }
    }

    private function encodeIriToCacheKey(string $iri): string
    {
        return str_replace('/', '_', $iri);
    }

    private function getPrivateFieldValue(string $privateField, object $object): string
    {
        return PropertyAccessorValueExtractor::getValue($object, $privateField);
    }

    private function getCollectionSubscriptionIriFromOperation(string $iri, Operation $operation): string
    {
        if (null === $operation->getClass()) {
            return $this->getCollectionIri($iri);
        }

        return $this->iriConverter->getIriFromResource($operation->getClass(), UrlGeneratorInterface::ABS_PATH, $operation) ?? $this->getCollectionIri($iri);
    }

    private function getCollectionIri(string $iri): string
    {
        return substr($iri, 0, strrpos($iri, '/'));
    }

    private function getCollectionSubscriptionIri(string $resourceClass, object $object, ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory): string
    {
        $resourceMetadata = $resourceMetadataCollectionFactory->create($resourceClass);

        try {
            $collectionOperation = $resourceMetadata->getOperation(forceCollection: true, forceGraphQl: true);

            return $this->iriConverter->getIriFromResource($resourceClass, UrlGeneratorInterface::ABS_PATH, $collectionOperation) ?? $this->getCollectionIri($this->iriConverter->getIriFromResource($object));
        } catch (OperationNotFoundException) {
            return $this->getCollectionIri($this->iriConverter->getIriFromResource($object));
        }
    }

    /**
     * @return array<string, string>
     */
    private function getPrivateFieldData(bool $private, array $privateFields, ?object $object): array
    {
        if (!$private || [] === $privateFields || null === $object) {
            return [];
        }

        $privateFieldData = [];
        foreach ($privateFields as $privateField) {
            try {
                $privateFieldData[$privateField] = $this->getPrivateFieldValue($privateField, $object);
            } catch (NoSuchPropertyException|AccessException) {
                continue;
            }
        }

        return $privateFieldData;
    }

    private function getPrivatePartitionKey(array $privateFieldData): ?string
    {
        if ([] === $privateFieldData) {
            return null;
        }

        $privatePartitionData = [];
        foreach ($privateFieldData as $field => $value) {
            $privatePartitionData[] = \sprintf('%s=%s', $field, $value);
        }

        return hash('sha256', implode('|', $privatePartitionData));
    }

    private function validateMercureOptions(bool $private, array $privateFields): void
    {
        if ([] !== $privateFields && !$private) {
            throw new InvalidArgumentException('"private_fields" requires "mercure.private" to be true.');
        }
    }

    private function getCreatedOrUpdatedPayloads(object $object, string $type): array
    {
        $resourceClass = $this->getObjectClass($object);
        $resourceMetadata = $this->resourceMetadataCollectionFactory->create($resourceClass);
        $shortName = $resourceMetadata->getOperation()->getShortName();

        $payloadsBySubscriptionId = [];
        foreach ($resourceMetadata as $apiResource) {
            foreach ($apiResource->getGraphQlOperations() as $operation) {
                if (!$operation instanceof Subscription) {
                    continue;
                }
                if ('create' === $type && !$operation instanceof CollectionOperationInterface) {
                    continue;
                }
                $mercure = $operation->getMercure() ?? false;
                $private = $mercure['private'] ?? false;
                $privateFieldsConfig = $mercure['private_fields'] ?? [];
                $privateFieldData = $this->getPrivateFieldData($private, $privateFieldsConfig, $object);
                $privatePartitionKey = $this->getPrivatePartitionKey($privateFieldData);

                $iri = $this->iriConverter->getIriFromResource($object);
                $collectionIri = $this->getCollectionSubscriptionIri($resourceClass, $object, $this->resourceMetadataCollectionFactory);
                $this->appendNormalizedPayloads(
                    $payloadsBySubscriptionId,
                    $this->getSubscriptionsFromIri($collectionIri, $privatePartitionKey),
                    $object,
                    $shortName
                );

                if ('create' !== $type) {
                    $itemSubscriptionsCacheItem = $this->getSubscriptionsCacheItem($iri, $privatePartitionKey);
                    $itemSubscriptions = $itemSubscriptionsCacheItem->isHit() ? $itemSubscriptionsCacheItem->get() : [];
                    $updatedItemSubscriptions = $this->appendNormalizedPayloads(
                        $payloadsBySubscriptionId,
                        $itemSubscriptions,
                        $object,
                        $shortName,
                        true
                    );

                    if ($updatedItemSubscriptions !== $itemSubscriptions) {
                        $itemSubscriptionsCacheItem->set($updatedItemSubscriptions);
                        $this->subscriptionsCache->save($itemSubscriptionsCacheItem);
                    }
                }
            }
        }

        return array_values($payloadsBySubscriptionId);
    }

    /**
     * @param array<string, array{string, mixed}>                              $payloadsBySubscriptionId
     * @param-out array<string, array{string, mixed}>                          $payloadsBySubscriptionId
     * @param array<array{string, array<string, mixed>, array<string, mixed>}> $subscriptions
     *
     * @return array<array{string, array<string, mixed>, array<string, mixed>}>
     */
    private function appendNormalizedPayloads(array &$payloadsBySubscriptionId, array $subscriptions, object $object, string $shortName, bool $updateCachedResult = false): array
    {
        $subscriptionOperation = (new Subscription())->withName('mercure_subscription')->withShortName($shortName);

        foreach ($subscriptions as $index => [$subscriptionId, $subscriptionFields, $subscriptionResult]) {
            $resolverContext = [
                'fields' => $subscriptionFields,
                'is_collection' => false,
                'is_mutation' => false,
                'is_subscription' => true,
            ];
            $data = $this->normalizeProcessor->process($object, $subscriptionOperation, [], $resolverContext);

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

    private function getDeletePushPayloads(object $object): array
    {
        $iri = $object->id;
        $privatePartitionKey = $this->getPrivatePartitionKey($object->private);
        $payloads = [];
        $payload = ['type' => 'delete', 'payload' => ['id' => $object->id, 'iri' => $object->iri, 'type' => $object->type]];
        // Check for resource class
        $collectionIri = isset($object->resourceClass) ? $this->getCollectionSubscriptionIri($object->resourceClass, (object) ['id' => $iri], $this->resourceMetadataCollectionFactory) : $this->getCollectionIri($iri);
        foreach ($this->getSubscriptionsFromIri($iri, $privatePartitionKey) as [$subscriptionId, $subscriptionFields, $subscriptionResult]) {
            $payloads[] = [$subscriptionId, $payload];
        }
        foreach ($this->getSubscriptionsFromIri($collectionIri, $privatePartitionKey) as [$subscriptionId, $subscriptionFields, $subscriptionResult]) {
            $payloads[] = [$subscriptionId, $payload];
        }
        $this->removeItemFromSubscriptionCache($iri, $privatePartitionKey);

        return $payloads;
    }

    private function updateSubscriptionItemCacheData(
        string $iri,
        array $fields,
        ?array $result,
        ?string $privatePartitionKey = null,
    ): string {
        $subscriptionsCacheItem = $this->subscriptionsCache->getItem($this->generateCacheKey($iri, $privatePartitionKey));
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
        $subscriptionId = $this->subscriptionIdentifierGenerator->generateSubscriptionIdentifier($fields);
        $subscriptions[] = [$subscriptionId, $fields, $result];
        $subscriptionsCacheItem->set($subscriptions);
        $this->subscriptionsCache->save($subscriptionsCacheItem);

        return $subscriptionId;
    }

    private function updateSubscriptionCollectionCacheData(
        string $collectionIri,
        array $fields,
        ?string $privatePartitionKey = null,
    ): string {
        $subscriptionCollectionCacheItem = $this->subscriptionsCache->getItem($this->generateCacheKey($collectionIri, $privatePartitionKey));
        $collectionSubscriptions = [];
        if ($subscriptionCollectionCacheItem->isHit()) {
            $collectionSubscriptions = $subscriptionCollectionCacheItem->get();
            foreach ($collectionSubscriptions as [$subscriptionId, $subscriptionFields, $result]) {
                if ($subscriptionFields === $fields) {
                    return $subscriptionId;
                }
            }
        }
        $subscriptionId = $this->subscriptionIdentifierGenerator->generateSubscriptionIdentifier($fields + ['__collection' => true]);
        $collectionSubscriptions[] = [$subscriptionId, $fields, []];
        $subscriptionCollectionCacheItem->set($collectionSubscriptions);
        $this->subscriptionsCache->save($subscriptionCollectionCacheItem);

        return $subscriptionId;
    }

    private function generateCacheKey(string $iri, ?string $privatePartitionKey = null): string
    {
        $cacheKey = $this->encodeIriToCacheKey($iri);
        if (null === $privatePartitionKey) {
            return $cacheKey;
        }

        return $cacheKey.'_'.$privatePartitionKey;
    }
}
