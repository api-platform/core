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

use ApiPlatform\GraphQl\Resolver\Stage\SerializeStageInterface;
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

    public function __construct(private readonly CacheItemPoolInterface $subscriptionsCache, private readonly SubscriptionIdentifierGeneratorInterface $subscriptionIdentifierGenerator, private readonly ?SerializeStageInterface $serializeStage, private readonly IriConverterInterface $iriConverter, private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, private readonly ?ProcessorInterface $normalizeProcessor = null)
    {
    }

    public function retrieveSubscriptionId(array $context, ?array $result, ?Operation $operation = null): ?string
    {
        /** @var ResolveInfo $info */
        $info = $context['info'];
        $fields = $info->getFieldSelection(\PHP_INT_MAX);
        $this->arrayRecursiveSort($fields, 'ksort');
        $iri = $operation ? $this->getIdentifierFromOperation($operation, $context['args'] ?? []) : $this->getIdentifierFromContext($context);
        if (null === $iri) {
            return null;
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
        $subscriptions[] = [$subscriptionId, $fields, $result];
        $subscriptionsCacheItem->set($subscriptions);
        $this->subscriptionsCache->save($subscriptionsCacheItem);

        return $subscriptionId;
    }

    public function getPushPayloads(object $object): array
    {
        $iri = $this->iriConverter->getIriFromResource($object);
        $subscriptions = $this->getSubscriptionsFromIri($iri);

        $resourceClass = $this->getObjectClass($object);
        $resourceMetadata = $this->resourceMetadataCollectionFactory->create($resourceClass);
        $shortName = $resourceMetadata->getOperation()->getShortName();

        $payloads = [];
        foreach ($subscriptions as [$subscriptionId, $subscriptionFields, $subscriptionResult]) {
            $resolverContext = ['fields' => $subscriptionFields, 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true];
            /** @var Operation */
            $operation = (new Subscription())->withName('update_subscription')->withShortName($shortName);
            if ($this->normalizeProcessor) {
                $data = $this->normalizeProcessor->process($object, $operation, [], $resolverContext);
            } elseif ($this->serializeStage) {
                $data = ($this->serializeStage)($object, $resourceClass, $operation, $resolverContext);
            } else {
                throw new \LogicException();
            }

            unset($data['clientSubscriptionId']);

            if ($data !== $subscriptionResult) {
                $payloads[] = [$subscriptionId, $data];
            }
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

    private function encodeIriToCacheKey(string $iri): string
    {
        return str_replace('/', '_', $iri);
    }
}
