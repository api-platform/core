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

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Lock\LockFactory;

/**
 * Coordinates registry mutations and stores independently versioned fingerprints.
 *
 * @internal
 */
final class SubscriptionStore implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(private readonly CacheItemPoolInterface $registry, private readonly CacheItemPoolInterface $fingerprints, private readonly ?LockFactory $lockFactory)
    {
    }

    /** @return array<string, list<array{string, array}>> */
    public function all(string $key): array
    {
        $item = $this->registry->getItem($key);

        return $item->isHit() ? $item->get() : [];
    }

    /** @param callable(): string $generateId */
    public function register(string $key, string $operation, array $fields, ?array $payload, bool $collection, callable $generateId): string
    {
        $fingerprint = $collection ? null : $this->fingerprint($payload);

        return $this->synchronized($key, function () use ($key, $operation, $fields, $fingerprint, $generateId): string {
            $item = $this->registry->getItem($key);
            $entries = $item->isHit() ? $item->get() : [];
            foreach ($entries[$operation] ?? [] as [$id, $selection]) {
                if ($selection === $fields) {
                    return $id;
                }
            }
            $id = $generateId();
            if (null !== $fingerprint) {
                $this->initialize($id, $fingerprint);
            }
            $entries[$operation][] = [$id, $fields];
            try {
                $this->save($this->registry, $item->set($entries));
            } catch (\Exception $e) {
                if (null !== $fingerprint) {
                    $this->discardFingerprint($id);
                }
                throw $e;
            }

            return $id;
        });
    }

    /** @return array<string, list<array{string, array}>> */
    public function remove(string $key): array
    {
        return $this->synchronized($key, function () use ($key): array {
            $entries = $this->all($key);
            if ([] === $entries) {
                return [];
            }
            $this->discard($this->registry, $key, 'Could not clean up deleted GraphQL subscriptions.');
            foreach ($this->subscriptionIds($entries) as $id) {
                try {
                    $this->synchronized($this->fingerprintKey($id), fn () => $this->discardFingerprint($id));
                } catch (\Exception $e) {
                    $this->logger?->warning('Could not lock deleted GraphQL subscription state for cleanup.', ['exception' => $e]);
                }
            }

            return $entries;
        });
    }

    /** @return array<string, list<RegisteredSubscription>> */
    public function getSubscriptions(string $key, bool $collection): array
    {
        $entries = $this->all($key);
        $snapshots = $collection ? [] : $this->snapshots($this->subscriptionIds($entries));
        $subscriptions = [];
        foreach ($entries as $operation => $registrations) {
            foreach ($registrations as [$id, $fields]) {
                $subscriptions[$operation][] = new RegisteredSubscription($key, $id, $fields, $collection, $snapshots[$id] ?? null);
            }
        }

        return $subscriptions;
    }

    public function prepareUpdate(RegisteredSubscription $subscription, array $data): ?SubscriptionUpdate
    {
        if ($subscription->collection) {
            return new SubscriptionUpdate($subscription, $data, null);
        }
        $hash = $this->fingerprint($data);
        if ($hash === ($subscription->fingerprint['hash'] ?? null)) {
            return null;
        }

        return new SubscriptionUpdate($subscription, $data, $hash);
    }

    /**
     * @param array<string, array> $payloads Payloads for the active operations
     *
     * @return iterable<array{string, SubscriptionUpdate}>
     */
    public function getDeleteUpdates(string $key, bool $collection, array $payloads): iterable
    {
        $entries = $collection ? $this->all($key) : $this->remove($key);
        foreach ($payloads as $operation => $data) {
            foreach ($entries[$operation] ?? [] as [$id, $fields]) {
                yield [$operation, new SubscriptionUpdate(new RegisteredSubscription($key, $id, $fields, $collection, null), $data, null)];
            }
        }
    }

    public function acknowledge(SubscriptionUpdate $update): void
    {
        if (null === $update->fingerprint) {
            return;
        }
        $subscription = $update->subscription;
        try {
            $this->recordFingerprint($subscription, $update->fingerprint);
        } catch (\Exception $e) {
            // Suppression state is expendable; its failure must not prevent
            // delivery to the remaining subscriptions.
            $this->discardFingerprint($subscription->id);
            $this->logger?->warning('Could not record a GraphQL subscription publication.', ['exception' => $e]);
        }
    }

    /**
     * @param array<string, list<array{string, array}>> $operations
     *
     * @return list<string>
     */
    private function subscriptionIds(array $operations): array
    {
        $ids = [];
        foreach ($operations as $registrations) {
            foreach ($registrations as [$id]) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /** @return array<string, array{hash: string, version: string}|null> */
    private function snapshots(array $ids): array
    {
        $keys = array_map($this->fingerprintKey(...), $ids);
        $items = iterator_to_array($this->fingerprints->getItems($keys));
        $snapshots = [];
        foreach ($ids as $i => $id) {
            $item = $items[$keys[$i]];
            $snapshots[$id] = $item->isHit() ? $item->get() : null;
        }

        return $snapshots;
    }

    private function recordFingerprint(RegisteredSubscription $subscription, string $hash): void
    {
        $key = $this->fingerprintKey($subscription->id);
        $this->synchronized($key, function () use ($key, $subscription, $hash): void {
            $item = $this->fingerprints->getItem($key);
            $current = $item->isHit() ? $item->get() : null;
            $previous = $subscription->fingerprint;
            // Concurrent publications may complete in either order. Keeping either
            // different hash could suppress a later correction, so forget it.
            if ($current !== $previous) {
                if (null !== $current && $current['hash'] !== $hash) {
                    $this->discardFingerprint($subscription->id);
                }

                return;
            }
            // With an existing fingerprint, deletion will either have removed it
            // already or remove it after acquiring this lock. A cache miss needs
            // an additional membership check to avoid recreating orphaned state.
            if (null === $previous && !\in_array($subscription->id, $this->subscriptionIds($this->all($subscription->key)), true)) {
                return;
            }
            $this->writeFingerprint($item, $hash);
        });
    }

    private function initialize(string $id, string $hash): void
    {
        $key = $this->fingerprintKey($id);
        $this->synchronized($key, function () use ($key, $hash): void {
            $this->writeFingerprint($this->fingerprints->getItem($key), $hash);
        });
    }

    private function writeFingerprint(CacheItemInterface $item, string $hash): void
    {
        $this->save($this->fingerprints, $item->set(['hash' => $hash, 'version' => bin2hex(random_bytes(16))]));
    }

    private function discardFingerprint(string $id): void
    {
        $this->discard($this->fingerprints, $this->fingerprintKey($id), 'Could not discard a GraphQL subscription fingerprint.');
    }

    private function fingerprint(?array $payload): string
    {
        return hash('sha256', serialize($payload));
    }

    private function fingerprintKey(string $id): string
    {
        return 'graphql_subscription_fingerprint_'.rawurlencode($id);
    }

    private function save(CacheItemPoolInterface $pool, CacheItemInterface $item): void
    {
        if (!$pool->save($item)) {
            throw new \RuntimeException('Cannot save GraphQL subscription state.');
        }
    }

    /**
     * Deletes expendable state, logging instead of throwing on failure.
     */
    private function discard(CacheItemPoolInterface $pool, string $key, string $warning): void
    {
        try {
            if (!$pool->deleteItem($key)) {
                throw new \RuntimeException('Cannot delete GraphQL subscription state.');
            }
        } catch (\Exception $e) {
            $this->logger?->warning($warning, ['exception' => $e]);
        }
    }

    private function synchronized(string $key, callable $callback): mixed
    {
        if (null === $this->lockFactory) {
            throw new \LogicException('GraphQL subscriptions require a configured Symfony Lock factory. Enable framework.lock or configure api_platform.graphql.subscription.lock_factory.');
        }
        $lock = $this->lockFactory->createLock('api_platform.'.$key);
        if (!$lock->acquire(true)) {
            throw new \RuntimeException('Cannot acquire the GraphQL subscription lock.');
        }
        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }
}
