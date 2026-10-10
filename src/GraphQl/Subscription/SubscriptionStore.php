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
use Symfony\Component\Lock\Exception\LockExpiredException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

/**
 * Coordinates registry mutations and stores independently versioned fingerprints.
 *
 * @internal
 */
final class SubscriptionStore implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(private readonly CacheItemPoolInterface $registry, private readonly CacheItemPoolInterface $fingerprints, private readonly LockFactory $lockFactory)
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

        return $this->synchronized($key, function (LockInterface $lock) use ($key, $operation, $fields, $fingerprint, $generateId): string {
            $item = $this->registry->getItem($key);
            $entries = $item->isHit() ? $item->get() : [];
            $this->assertNotExpired($lock);
            foreach ($entries[$operation] ?? [] as [$id, $selection]) {
                if ($selection === $fields) {
                    if (null !== $fingerprint) {
                        $this->invalidateIfChanged($id, $fingerprint, $lock);
                    }

                    return $id;
                }
            }
            $id = $generateId();
            if (null !== $fingerprint) {
                $this->initialize($id, $fingerprint, $lock);
            }
            $entries[$operation][] = [$id, $fields];
            try {
                $this->save($this->registry, $item->set($entries), $lock);
            } catch (\Exception $e) {
                if (null !== $fingerprint) {
                    $this->discardFingerprint($id, $lock);
                }
                throw $e;
            }

            return $id;
        });
    }

    /** @return array<string, list<array{string, array}>> */
    public function remove(string $key): array
    {
        return $this->synchronized($key, function (LockInterface $lock) use ($key): array {
            $entries = $this->all($key);
            if ([] === $entries) {
                return [];
            }
            $this->discard($this->registry, $key, 'Could not clean up deleted GraphQL subscriptions.', $lock);
            foreach ($this->subscriptionIds($entries) as $id) {
                try {
                    $this->assertNotExpired($lock);
                    $this->synchronized($this->fingerprintKey($id), function (LockInterface $fingerprintLock) use ($id, $lock): void {
                        $this->assertNotExpired($lock);
                        $this->discardFingerprint($id, $fingerprintLock);
                    });
                } catch (\Exception $e) {
                    $this->logger?->warning('Could not lock deleted GraphQL subscription state for cleanup.', ['exception' => $e]);
                    if ($lock->isExpired()) {
                        break;
                    }
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
            $this->synchronized($this->fingerprintKey($subscription->id), function (LockInterface $lock) use ($subscription, $update): void {
                try {
                    $this->recordFingerprint($subscription, $update->fingerprint, $lock);
                } catch (\Exception $e) {
                    $this->discardFingerprint($subscription->id, $lock);
                    throw $e;
                }
            });
        } catch (\Exception $e) {
            // Suppression state is expendable; its failure must not prevent
            // delivery to the remaining subscriptions.
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

    /** @return array<string, array{hash: string|null, version: string}|null> */
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

    private function recordFingerprint(RegisteredSubscription $subscription, string $hash, LockInterface $lock): void
    {
        $item = $this->fingerprints->getItem($this->fingerprintKey($subscription->id));
        $current = $item->isHit() ? $item->get() : null;
        $previous = $subscription->fingerprint;
        // Concurrent publications may complete in either order. Keeping either
        // different hash could suppress a later correction, so forget it.
        if ($current !== $previous) {
            if (null !== ($current['hash'] ?? null) && $current['hash'] !== $hash) {
                $this->discardFingerprint($subscription->id, $lock);
            }

            return;
        }
        // With an existing fingerprint, deletion will either have removed it
        // already or remove it after acquiring this lock. A cache miss needs
        // an additional membership check to avoid recreating orphaned state.
        if (null === $previous && !\in_array($subscription->id, $this->subscriptionIds($this->all($subscription->key)), true)) {
            return;
        }
        $this->writeFingerprint($item, $hash, $lock);
    }

    private function initialize(string $id, string $hash, LockInterface $registryLock): void
    {
        $key = $this->fingerprintKey($id);
        $this->synchronized($key, function (LockInterface $lock) use ($key, $hash, $registryLock): void {
            $item = $this->fingerprints->getItem($key);
            $this->assertNotExpired($registryLock);
            $this->writeFingerprint($item, $hash, $lock);
        });
    }

    private function invalidateIfChanged(string $id, string $hash, LockInterface $registryLock): void
    {
        $key = $this->fingerprintKey($id);
        $this->synchronized($key, function (LockInterface $lock) use ($key, $hash, $registryLock): void {
            $item = $this->fingerprints->getItem($key);
            $this->assertNotExpired($registryLock);
            if ($item->isHit() && $hash === $item->get()['hash']) {
                return;
            }
            // Subscribers sharing this ID may now have different payloads. Keep
            // a versioned invalidation so older acknowledgements cannot restore
            // suppression, even when their snapshot was a cache miss.
            $this->writeFingerprint($item, null, $lock);
        });
    }

    private function writeFingerprint(CacheItemInterface $item, ?string $hash, LockInterface $lock): void
    {
        $this->save($this->fingerprints, $item->set(['hash' => $hash, 'version' => bin2hex(random_bytes(16))]), $lock);
    }

    private function discardFingerprint(string $id, LockInterface $lock): void
    {
        $this->discard($this->fingerprints, $this->fingerprintKey($id), 'Could not discard a GraphQL subscription fingerprint.', $lock);
    }

    private function fingerprint(?array $payload): string
    {
        return hash('sha256', serialize($payload));
    }

    private function fingerprintKey(string $id): string
    {
        return 'graphql_subscription_fingerprint_'.rawurlencode($id);
    }

    private function save(CacheItemPoolInterface $pool, CacheItemInterface $item, LockInterface $lock): void
    {
        $this->assertNotExpired($lock);
        if (!$pool->save($item)) {
            throw new \RuntimeException('Cannot save GraphQL subscription state.');
        }
        $this->assertNotExpired($lock);
    }

    /**
     * Deletes expendable state, logging instead of throwing on failure.
     */
    private function discard(CacheItemPoolInterface $pool, string $key, string $warning, LockInterface $lock): void
    {
        try {
            $this->assertNotExpired($lock);
            if (!$pool->deleteItem($key)) {
                throw new \RuntimeException('Cannot delete GraphQL subscription state.');
            }
            $this->assertNotExpired($lock);
        } catch (\Exception $e) {
            $this->logger?->warning($warning, ['exception' => $e]);
        }
    }

    private function assertNotExpired(LockInterface $lock): void
    {
        if ($lock->isExpired()) {
            throw new LockExpiredException('The GraphQL subscription lock expired during a cache operation.');
        }
    }

    private function synchronized(string $key, callable $callback): mixed
    {
        $lock = $this->lockFactory->createLock('api_platform.'.$key, 5.0);
        if (!$lock->acquire(true)) {
            throw new \RuntimeException('Cannot acquire the GraphQL subscription lock.');
        }
        try {
            return $callback($lock);
        } finally {
            $lock->release();
        }
    }
}
