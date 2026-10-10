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
 * Coordinates registry mutations and stores best-effort publication fingerprints.
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
    public function fetch(string $key): array
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
                    if (null !== $fingerprint) {
                        $fingerprintItem = $this->fingerprints->getItem($this->fingerprintKey($id));
                        if ($fingerprintItem->get() !== $fingerprint) {
                            // Existing and newly enrolled clients may hold different values.
                            $this->save($this->fingerprints, $fingerprintItem->set(null));
                        }
                    }

                    return $id;
                }
            }
            $id = $generateId();
            if (null !== $fingerprint) {
                $this->saveFingerprint($id, $fingerprint);
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
        $entries = $this->synchronized($key, function () use ($key): array {
            $entries = $this->fetch($key);
            if ([] === $entries) {
                return [];
            }
            try {
                if (!$this->registry->deleteItem($key)) {
                    $this->logger?->warning('Could not clean up deleted GraphQL subscriptions.');
                }
            } catch (\Exception $e) {
                $this->logger?->warning('Could not clean up deleted GraphQL subscriptions.', ['exception' => $e]);
            }

            return $entries;
        });
        foreach ($this->subscriptionIds($entries) as $id) {
            $this->discardFingerprint($id);
        }

        return $entries;
    }

    /** @return array<string, list<RegisteredSubscription>> */
    public function getSubscriptions(string $key, bool $collection): array
    {
        $entries = $this->fetch($key);
        $fingerprints = $collection ? [] : $this->fetchFingerprints($this->subscriptionIds($entries));
        $subscriptions = [];
        foreach ($entries as $operation => $registrations) {
            foreach ($registrations as [$id, $fields]) {
                $subscriptions[$operation][] = new RegisteredSubscription($id, $fields, $collection, $fingerprints[$id] ?? null);
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
        if ($hash === $subscription->fingerprint) {
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
        $entries = $collection ? $this->fetch($key) : $this->remove($key);
        foreach ($payloads as $operation => $data) {
            foreach ($entries[$operation] ?? [] as [$id, $fields]) {
                yield [$operation, new SubscriptionUpdate(new RegisteredSubscription($id, $fields, $collection, null), $data, null)];
            }
        }
    }

    public function acknowledge(SubscriptionUpdate $update): void
    {
        if (null === $update->fingerprint) {
            return;
        }
        $id = $update->getId();
        try {
            $this->saveFingerprint($id, $update->fingerprint);
        } catch (\Exception $e) {
            $this->discardFingerprint($id);
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

    /** @return array<string, string|null> */
    private function fetchFingerprints(array $ids): array
    {
        $keys = array_map($this->fingerprintKey(...), $ids);
        $items = iterator_to_array($this->fingerprints->getItems($keys));
        $fingerprints = [];
        foreach ($ids as $i => $id) {
            $item = $items[$keys[$i]];
            // Treat entries from the previous versioned format as cache misses.
            $fingerprints[$id] = \is_string($hash = $item->get()) ? $hash : null;
        }

        return $fingerprints;
    }

    private function saveFingerprint(string $id, string $hash): void
    {
        $item = $this->fingerprints->getItem($this->fingerprintKey($id));
        $this->save($this->fingerprints, $item->set($hash));
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
    private function discardFingerprint(string $id): void
    {
        try {
            if (!$this->fingerprints->deleteItem($this->fingerprintKey($id))) {
                $this->logger?->warning('Could not discard a GraphQL subscription fingerprint.');
            }
        } catch (\Exception $e) {
            $this->logger?->warning('Could not discard a GraphQL subscription fingerprint.', ['exception' => $e]);
        }
    }

    private function synchronized(string $key, callable $callback): mixed
    {
        $lock = $this->lockFactory->createLock('api_platform.'.$key, 5.0);
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
