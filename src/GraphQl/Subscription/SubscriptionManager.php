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
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Util\ResourceClassInfoTrait;
use ApiPlatform\Metadata\Util\SortTrait;
use ApiPlatform\State\ProcessorInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Psr\Cache\CacheItemPoolInterface;

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

        /** @var ResolveInfo $info */
        $info = $context['info'];
        $fields = $info->getFieldSelection(\PHP_INT_MAX);
        $this->arrayRecursiveSort($fields, 'ksort');
        $iri = $operation ? $this->getIdentifierFromOperation($operation, $context['args'] ?? []) : $this->getIdentifierFromContext($context);
        if (empty($iri)) {
            return null;
        }
        $options = $operation->getMercure() ?? false;
        $private = $options['private'] ?? false;
        $privateFields = $options['private_fields'] ?? [];
        $previousObject = $context['graphql_context']['previous_object'] ?? null;
        if ($private && $privateFields && $previousObject) {
            foreach ($options['private_fields'] as $privateField) {
                $fields['__private_field_'.$privateField] = $this->getResourceId($privateField, $previousObject);
            }
        }
        $subscriptionsCacheItem = $this->subscriptionsCache->getItem($this->encodeIriToCacheKey($iri));
        $subscriptions = [];
        if ($subscriptionsCacheItem->isHit()) {
            $subscriptions = $subscriptionsCacheItem->get();
            foreach ($subscriptions as [$subscriptionId, $subscriptionFields, $subscriptionResult]) {
                if ($subscriptionFields === $fields) {
                    return $subscriptionId;
                }
            }
        }

        $subscriptionId = $this->subscriptionIdentifierGenerator->generateSubscriptionIdentifier($fields);
        unset($result['clientSubscriptionId']);
        if ($private && $privateFields && $previousObject) {
            foreach ($options['private_fields'] as $privateField) {
                unset($result['__private_field_'.$privateField]);
            }
        }
        $subscriptions[] = [$subscriptionId, $fields, $result];
        $subscriptionsCacheItem->set($subscriptions);
        $this->subscriptionsCache->save($subscriptionsCacheItem);

        $this->updateSubscriptionCollectionCacheData(
            $iri,
            $fields,
            $subscriptions,
        );

        return $subscriptionId;
    }

    public function getPushPayloads(object $object, string $type): array
    {
        if ('delete' === $type) {
            $payloads =  $this->getDeletePushPayloads($object);
        } else {
            $payloads = $this->getCreatedOrUpdatedPayloads($object);
        }

        return $payloads;
    }

    /**
     * @return array<array>
     */
    private function getSubscriptionsFromIri(string $iri): array
    {
        $subscriptionsCacheItem = $this->subscriptionsCache->getItem($this->encodeIriToCacheKey($iri));

        if ($subscriptionsCacheItem->isHit()) {
            return $subscriptionsCacheItem->get();
        }

        return [];
    }

    private function removeItemFromSubscriptionCache(string $iri): void
    {
        $cacheKey = $this->encodeIriToCacheKey($iri);
        if ($this->subscriptionsCache->hasItem($cacheKey)) {
            $this->subscriptionsCache->deleteItem($cacheKey);
        }
    }

    private function encodeIriToCacheKey(string $iri): string
    {
        return str_replace('/', '_', $iri);
    }

    private function updateSubscriptionCollectionCacheData(
        ?string                       $iri,
        array                         $fields,
        array                         $subscriptions,
    ): void
    {
        $subscriptionCollectionCacheItem = $this->subscriptionsCache->getItem(
            $this->encodeIriToCacheKey($this->getCollectionIri($iri)),
        );
        if ($subscriptionCollectionCacheItem->isHit()) {
            $collectionSubscriptions = $subscriptionCollectionCacheItem->get();
            foreach ($collectionSubscriptions as [$subscriptionId, $subscriptionFields, $subscriptionResult]) {
                if ($subscriptionFields === $fields) {
                    return;
                }
            }
        }
        $subscriptionCollectionCacheItem->set($subscriptions);
        $this->subscriptionsCache->save($subscriptionCollectionCacheItem);
    }

    private function getResourceId(mixed $privateField, object $previousObject): string
    {
        $id = $previousObject->{'get' . ucfirst($privateField)}()->getId();
        if ($id instanceof \Stringable) {
            return (string)$id;
        }
        return $id;
    }

    private function getCollectionIri(string $iri): string
    {
        return substr($iri, 0, strrpos($iri, '/'));
    }

    private function getCreatedOrUpdatedPayloads(object $object): array
    {
        $iri = $this->iriConverter->getIriFromResource($object);
        $subscriptions = $this->getSubscriptionsFromIri($iri);
        if ($subscriptions === []) {
            // Get subscriptions from collection Iri
            $subscriptions = $this->getSubscriptionsFromIri($this->getCollectionIri($iri));
        }

        $resourceClass = $this->getObjectClass($object);
        $resourceMetadata = $this->resourceMetadataCollectionFactory->create($resourceClass);
        $shortName = $resourceMetadata->getOperation()->getShortName();

        $mercure = $resourceMetadata->getOperation()->getMercure() ?? false;
        $private = $mercure['private'] ?? false;
        $privateFieldsConfig = $mercure['private_fields'] ?? [];
        $privateFieldData = [];
        if ($private && $privateFieldsConfig) {
            foreach ($privateFieldsConfig as $privateField) {
                $privateFieldData['__private_field_'.$privateField] = $this->getResourceId($privateField, $object);
            }
        }

        $payloads = [];
        foreach ($subscriptions as [$subscriptionId, $subscriptionFields, $subscriptionResult]) {
            if ($privateFieldData) {
                $fieldDiff = array_intersect_assoc($subscriptionFields, $privateFieldData);
                if ($fieldDiff !== $privateFieldData) {
                    continue;
                }
            }
            $resolverContext = ['fields' => $subscriptionFields, 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true];
            $operation = (new Subscription())->withName('update_subscription')->withShortName($shortName);
            $data = $this->normalizeProcessor->process($object, $operation, [], $resolverContext);

            unset($data['clientSubscriptionId']);

            if ($data !== $subscriptionResult) {
                $payloads[] = [$subscriptionId, $data];
            }
        }
        return $payloads;
    }

    private function getDeletePushPayloads(object $object): array
    {
        $iri = $object->id;
        $subscriptions = $this->getSubscriptionsFromIri($iri);
        if ($subscriptions === []) {
            // Get subscriptions from collection Iri
            $subscriptions = $this->getSubscriptionsFromIri($this->getCollectionIri($iri));
        }

        $payloads = [];
        foreach ($subscriptions as [$subscriptionId, $subscriptionFields, $subscriptionResult]) {
            $payloads[] = [$subscriptionId, ['type' => 'delete', 'payload' => $object]];
        }
        $this->removeItemFromSubscriptionCache($iri);
        return $payloads;
    }

}
