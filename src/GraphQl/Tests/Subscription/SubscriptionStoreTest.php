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

namespace ApiPlatform\GraphQl\Tests\Subscription;

use ApiPlatform\GraphQl\Subscription\SubscriptionStore;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

final class SubscriptionStoreTest extends TestCase
{
    public function testRegistryContainsNoPayloadAndFingerprintHasFixedSize(): void
    {
        $registry = new ArrayAdapter();
        $fingerprints = new ArrayAdapter();
        $store = new SubscriptionStore($registry, $fingerprints, new LockFactory(new InMemoryStore()));
        $payload = ['name' => str_repeat('private payload', 10000)];
        $id = $store->register('bucket', 'watch', ['name' => true], $payload, false, static fn () => 'id');

        $this->assertSame(['watch' => [['id', ['name' => true]]]], $registry->getItem('bucket')->get());
        $this->assertSame(['graphql_subscription_fingerprint_id'], array_keys($fingerprints->getValues()));
        $snapshot = $fingerprints->getItem('graphql_subscription_fingerprint_id')->get();
        $this->assertIsString($snapshot);
        $this->assertSame(64, \strlen($snapshot));
        $this->assertStringNotContainsString('private payload', serialize($snapshot));
        $this->assertSame($id, $store->register('bucket', 'watch', ['name' => true], $payload, false, static fn () => 'different'));
        $this->assertSame($snapshot, $fingerprints->getItem(array_key_first($fingerprints->getValues()))->get(), 'Re-enrollment with the same payload must preserve suppression.');
        $this->assertSame([], self::publish($store, $payload));
    }

    #[TestWith(['A'])]
    #[TestWith(['B'])]
    public function testReregistrationWithDifferentPayloadDoesNotSuppressEitherClientsCorrection(string $nextValue): void
    {
        $store = new SubscriptionStore(new ArrayAdapter(), new ArrayAdapter(), new LockFactory(new InMemoryStore()));
        $store->register('bucket', 'watch', ['name' => true], ['name' => 'A'], false, static fn () => 'id');
        $pending = $store->prepareUpdate($store->getSubscriptions('bucket', false)['watch'][0], ['name' => 'B']);
        $this->assertNotNull($pending);
        // B failed delivery. Existing clients still have A; the new client reads B.
        $this->assertSame('id', $store->register('bucket', 'watch', ['name' => true], ['name' => 'B'], false, static fn () => 'different'));
        $this->assertSame(['watch' => [['id', ['name' => true]]]], $store->fetch('bucket'));

        $payload = ['name' => $nextValue];
        $this->assertSame([['id', $payload]], self::publish($store, $payload));
        $this->assertSame([], self::publish($store, $payload));
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testLastAcknowledgementWinsAfterReregistration(bool $missingFingerprint): void
    {
        $fingerprints = new ArrayAdapter();
        $store = new SubscriptionStore(new ArrayAdapter(), $fingerprints, new LockFactory(new InMemoryStore()));
        $store->register('bucket', 'watch', ['name' => true], ['name' => 'old'], false, static fn () => 'id');
        if ($missingFingerprint) {
            $fingerprints->clear();
        }
        $subscription = $store->getSubscriptions('bucket', false)['watch'][0];
        $first = $store->prepareUpdate($subscription, ['name' => 'A']);
        $second = $store->prepareUpdate($subscription, ['name' => 'A']);
        $this->assertNotNull($first);
        $this->assertNotNull($second);
        // Both deliveries precede enrollment, but their acknowledgements are delayed.
        $store->register('bucket', 'watch', ['name' => true], ['name' => 'B'], false, static fn () => 'different');
        $store->acknowledge($first);
        $store->acknowledge($second);

        $this->assertSame([], self::publish($store, ['name' => 'A']), 'Late acknowledgements may replace re-registration invalidation.');
        $this->assertSame([['id', ['name' => 'B']]], self::publish($store, ['name' => 'B']));
        $this->assertSame([], self::publish($store, ['name' => 'B']));
    }

    public function testReregistrationFailsWhenFingerprintInvalidationCannotBeSaved(): void
    {
        $fingerprints = new class extends ArrayAdapter {
            public function save(\Psr\Cache\CacheItemInterface $item): bool
            {
                return null !== $item->get() && parent::save($item);
            }
        };
        $store = new SubscriptionStore(new ArrayAdapter(), $fingerprints, new LockFactory(new InMemoryStore()));
        $store->register('bucket', 'watch', ['name' => true], ['name' => 'A'], false, static fn () => 'id');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot save GraphQL subscription state.');
        $store->register('bucket', 'watch', ['name' => true], ['name' => 'B'], false, static fn () => 'different');
    }

    public function testLastAcknowledgementWinsEvenAfterValueReturns(): void
    {
        $store = new SubscriptionStore(new ArrayAdapter(), new ArrayAdapter(), new LockFactory(new InMemoryStore()));
        $a = ['name' => 'A'];
        $b = ['name' => 'B'];
        $store->register('bucket', 'watch', ['name' => true], $a, false, static fn () => 'id');
        $pending = $store->prepareUpdate($store->getSubscriptions('bucket', false)['watch'][0], $b);
        $this->assertNotNull($pending);
        $this->assertSame([['id', $b]], self::publish($store, $b));
        $this->assertSame([['id', $a]], self::publish($store, $a));
        $store->acknowledge($pending);

        $this->assertSame([], self::publish($store, $b), 'The last acknowledgement replaces the fingerprint, regardless of preparation order.');
        $this->assertSame([['id', $a]], self::publish($store, $a));
        $this->assertSame([], self::publish($store, $a));
    }

    public function testLateAcknowledgementCanLeaveAFingerprintButCannotRecreateRegistration(): void
    {
        $registry = new ArrayAdapter();
        $fingerprints = new ArrayAdapter();
        $store = new SubscriptionStore($registry, $fingerprints, new LockFactory(new InMemoryStore()));
        $store->register('bucket', 'watch', ['name' => true], ['name' => 'A'], false, static fn () => 'id');
        $pending = $store->prepareUpdate($store->getSubscriptions('bucket', false)['watch'][0], ['name' => 'late']);
        $this->assertNotNull($pending);
        $this->assertSame(['watch' => [['id', ['name' => true]]]], $store->remove('bucket'));
        $store->acknowledge($pending);
        $this->assertSame([], $store->fetch('bucket'));
        $this->assertSame([], self::publish($store, ['name' => 'late']));
        $this->assertCount(1, $fingerprints->getValues(), 'Late acknowledgement may leave an unused fingerprint.');

        $store->register('bucket', 'watch', ['name' => true], ['name' => 'A'], false, static fn () => 'id');
        $pending = $store->prepareUpdate($store->getSubscriptions('bucket', false)['watch'][0], ['name' => 'late']);
        $this->assertNotNull($pending);
        $store->remove('bucket');
        $store->register('bucket', 'watch', ['name' => true], ['name' => 'A'], false, static fn () => 'id');
        $store->acknowledge($pending);
        $this->assertSame(['watch' => [['id', ['name' => true]]]], $store->fetch('bucket'));
        $this->assertSame([['id', ['name' => 'A']]], self::publish($store, ['name' => 'A']));
    }

    public function testCollectionsNeverReadOrWriteFingerprints(): void
    {
        $fingerprints = $this->createMock(\Psr\Cache\CacheItemPoolInterface::class);
        $fingerprints->expects($this->never())->method('getItem');
        $fingerprints->expects($this->never())->method('getItems');
        $fingerprints->expects($this->never())->method('save');
        $store = new SubscriptionStore(new ArrayAdapter(), $fingerprints, new LockFactory(new InMemoryStore()));
        $store->register('bucket', 'watch', ['name' => true], ['name' => 'A'], true, static fn () => 'id');
        $this->assertSame('id', $store->register('bucket', 'watch', ['name' => true], ['name' => 'B'], true, static fn () => 'different'));
        for ($i = 0; $i < 2; ++$i) {
            $this->assertSame([['id', ['name' => 'A']]], self::publish($store, ['name' => 'A'], true));
        }
    }

    public function testVersionedFingerprintFromPreviousFormatIsTreatedAsAMiss(): void
    {
        $fingerprints = new ArrayAdapter();
        $store = new SubscriptionStore(new ArrayAdapter(), $fingerprints, new LockFactory(new InMemoryStore()));
        $store->register('bucket', 'watch', ['name' => true], ['name' => 'A'], false, static fn () => 'id');
        $item = $fingerprints->getItem('graphql_subscription_fingerprint_id');
        $fingerprints->save($item->set(['hash' => $item->get(), 'version' => 'old']));

        $this->assertSame([['id', ['name' => 'A']]], self::publish($store, ['name' => 'A']));
        $this->assertIsString($fingerprints->getItem('graphql_subscription_fingerprint_id')->get());
        $this->assertSame([], self::publish($store, ['name' => 'A']));
    }

    public function testMissingFingerprintCanBeRepopulatedWithoutWritingRegistry(): void
    {
        $registry = new ArrayAdapter();
        $fingerprints = new ArrayAdapter();
        $store = new SubscriptionStore($registry, $fingerprints, new LockFactory(new InMemoryStore()));
        $store->register('bucket', 'watch', ['name' => true], [], false, static fn () => 'id');
        $before = $registry->getValues();
        $fingerprints->clear();
        $this->assertSame([['id', []]], self::publish($store, []));
        $this->assertSame([], self::publish($store, []));
        $this->assertSame($before, $registry->getValues());
    }

    public function testRegistryFailureReleasesTheLockAndDoesNotReportRegistrationSuccess(): void
    {
        $registry = new class extends ArrayAdapter {
            public bool $fail = true;

            public function save(\Psr\Cache\CacheItemInterface $item): bool
            {
                if ($this->fail) {
                    $this->fail = false;

                    return false;
                }

                return parent::save($item);
            }
        };
        $fingerprints = new ArrayAdapter();
        $store = new SubscriptionStore($registry, $fingerprints, new LockFactory(new InMemoryStore()));
        try {
            $store->register('bucket', 'watch', ['name' => true], [], false, static fn () => 'id');
            $this->fail('An unsuccessful cache write must fail registration.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Cannot save GraphQL subscription state.', $e->getMessage());
            $this->assertEmpty(array_filter($fingerprints->getValues()));
        }
        $this->assertSame('id', $store->register('bucket', 'watch', ['name' => true], [], false, static fn () => 'id'));
        $this->assertSame(['watch' => [['id', ['name' => true]]]], $store->fetch('bucket'));
    }

    public function testFingerprintPreservesStrictPayloadComparison(): void
    {
        $store = new SubscriptionStore(new ArrayAdapter(), new ArrayAdapter(), new LockFactory(new InMemoryStore()));
        $store->register('bucket', 'watch', ['value' => true], ['value' => 1], false, static fn () => 'id');
        $this->assertSame([], self::publish($store, ['value' => 1]));
        foreach ([['value' => '1'], ['value' => 1.0], ['value' => 1], ['nested' => [['name' => 'same']]]] as $payload) {
            $this->assertSame([['id', $payload]], self::publish($store, $payload));
            $this->assertSame([], self::publish($store, $payload));
        }
    }

    public function testFingerprintWriteFailureDoesNotStopRemainingPublications(): void
    {
        $fingerprints = new class extends ArrayAdapter {
            public bool $fail = false;

            public function save(\Psr\Cache\CacheItemInterface $item): bool
            {
                if ($this->fail) {
                    $this->fail = false;

                    return false;
                }

                return parent::save($item);
            }
        };
        $store = new SubscriptionStore(new ArrayAdapter(), $fingerprints, new LockFactory(new InMemoryStore()));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with('Could not record a GraphQL subscription publication.', $this->isArray());
        $store->setLogger($logger);
        $store->register('bucket', 'watch', ['name' => true], [], false, static fn () => 'first');
        $store->register('bucket', 'watch', ['id' => true], [], false, static fn () => 'second');
        $fingerprints->fail = true;

        $this->assertSame([['first', ['name' => 'changed']], ['second', ['name' => 'changed']]], self::publish($store, ['name' => 'changed']));
        $this->assertSame([['first', ['name' => 'changed']]], self::publish($store, ['name' => 'changed']));
        $this->assertSame([], self::publish($store, ['name' => 'changed']));
    }

    #[TestWith([false, false])]
    #[TestWith([true, false])]
    #[TestWith([false, true])]
    #[TestWith([true, true])]
    public function testCleanupFailureDoesNotDiscardDeleteRecipients(bool $failRegistry, bool $throw): void
    {
        $failingCache = new class extends ArrayAdapter {
            public bool $throw = false;

            public function deleteItem(mixed $key): bool
            {
                if ($this->throw) {
                    throw new \RuntimeException('Cache deletion failed.');
                }

                return false;
            }
        };
        $failingCache->throw = $throw;
        $registry = $failRegistry ? $failingCache : new ArrayAdapter();
        $fingerprints = $failRegistry ? new ArrayAdapter() : $failingCache;
        $store = new SubscriptionStore($registry, $fingerprints, new LockFactory(new InMemoryStore()));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with(
            $failRegistry ? 'Could not clean up deleted GraphQL subscriptions.' : 'Could not discard a GraphQL subscription fingerprint.',
            $throw ? $this->callback(static fn (array $context): bool => $context['exception'] instanceof \RuntimeException) : [],
        );
        $store->setLogger($logger);
        $store->register('bucket', 'watch', ['name' => true], [], false, static fn () => 'id');

        $this->assertSame(['watch' => [['id', ['name' => true]]]], $store->remove('bucket'));
        $this->assertSame($failRegistry, $registry->hasItem('bucket'));
    }

    public function testLatePublicationDoesNotRestoreRemovedSelection(): void
    {
        $registry = new ArrayAdapter();
        $fingerprints = new ArrayAdapter();
        $store = new SubscriptionStore($registry, $fingerprints, new LockFactory(new InMemoryStore()));
        $store->register('bucket', 'watch', ['name' => true], [], false, static fn () => 'old');
        $fingerprints->clear();
        $pending = $store->prepareUpdate($store->getSubscriptions('bucket', false)['watch'][0], ['name' => 'late']);
        $this->assertNotNull($pending);
        $store->remove('bucket');
        $store->register('bucket', 'watch', ['id' => true], [], false, static fn () => 'new');
        $store->acknowledge($pending);

        $this->assertSame(['watch' => [['new', ['id' => true]]]], $store->fetch('bucket'));
        $this->assertCount(2, $fingerprints->getValues());
        $this->assertSame([['new', ['name' => 'late']]], self::publish($store, ['name' => 'late']));
    }

    public function testPreparingUpdatesDoesNotAcknowledgeThem(): void
    {
        $fingerprints = new ArrayAdapter();
        $store = new SubscriptionStore(new ArrayAdapter(), $fingerprints, new LockFactory(new InMemoryStore()));
        $store->register('bucket', 'watch', ['name' => true], [], false, static fn () => 'first');
        $store->register('bucket', 'watch', ['id' => true], [], false, static fn () => 'second');
        $before = $fingerprints->getValues();
        $subscriptions = $store->getSubscriptions('bucket', false)['watch'];
        $first = $store->prepareUpdate($subscriptions[0], ['name' => 'changed']);
        $second = $store->prepareUpdate($subscriptions[1], ['name' => 'changed']);
        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($before, $fingerprints->getValues());

        $store->acknowledge($first);
        $this->assertSame([['second', ['name' => 'changed']]], self::publish($store, ['name' => 'changed']));
    }

    public function testCustomSubscriptionIdIsEscapedWithoutRehashing(): void
    {
        $fingerprints = new ArrayAdapter();
        $store = new SubscriptionStore(new ArrayAdapter(), $fingerprints, new LockFactory(new InMemoryStore()));
        $id = 'https://example.com/subscriptions/1';
        $store->register('bucket', 'watch', ['name' => true], [], false, static fn () => $id);

        $this->assertSame(['graphql_subscription_fingerprint_'.rawurlencode($id)], array_keys($fingerprints->getValues()));
        $this->assertSame([[$id, ['name' => 'changed']]], self::publish($store, ['name' => 'changed']));
        $this->assertSame([], self::publish($store, ['name' => 'changed']));
        $store->remove('bucket');
        $this->assertEmpty($fingerprints->getValues());
    }

    private static function publish(SubscriptionStore $store, array $payload, bool $collection = false): array
    {
        $published = [];
        foreach ($store->getSubscriptions('bucket', $collection)['watch'] ?? [] as $subscription) {
            $update = $store->prepareUpdate($subscription, $payload);
            if (null === $update) {
                continue;
            }
            $published[] = [$update->getId(), $update->data];
            $store->acknowledge($update);
        }

        return $published;
    }
}
