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
        $this->assertSame(64, \strlen($snapshot['hash']));
        $this->assertSame(32, \strlen($snapshot['version']));
        $this->assertStringNotContainsString('private payload', serialize($snapshot));
        $this->assertSame($id, $store->register('bucket', 'watch', ['name' => true], ['name' => 'new subscriber response'], false, static fn () => 'different'));
        $this->assertSame($snapshot, $fingerprints->getItem(array_key_first($fingerprints->getValues()))->get(), 'Re-enrollment must not reset the publication fingerprint.');
        $this->assertSame([], self::publish($store, $payload));
    }

    public function testConflictingPublicationInvalidatesFingerprintEvenAfterValueReturns(): void
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

        $this->assertSame([['id', $a]], self::publish($store, $a), 'An ambiguous publication order must not suppress the next update.');
        $this->assertSame([], self::publish($store, $a));
    }

    public function testDeletedSubscriptionIsNotRecreatedAndReregistrationSurvivesOldPublication(): void
    {
        $registry = new ArrayAdapter();
        $fingerprints = new ArrayAdapter();
        $store = new SubscriptionStore($registry, $fingerprints, new LockFactory(new InMemoryStore()));
        $store->register('bucket', 'watch', ['name' => true], ['name' => 'A'], false, static fn () => 'id');
        $pending = $store->prepareUpdate($store->getSubscriptions('bucket', false)['watch'][0], ['name' => 'late']);
        $this->assertNotNull($pending);
        $this->assertSame(['watch' => [['id', ['name' => true]]]], $store->remove('bucket'));
        $store->acknowledge($pending);
        $this->assertSame([], $store->all('bucket'));
        $this->assertSame([], self::publish($store, ['name' => 'late']));
        $this->assertEmpty(array_filter($fingerprints->getValues()));

        $store->register('bucket', 'watch', ['name' => true], ['name' => 'A'], false, static fn () => 'id');
        $pending = $store->prepareUpdate($store->getSubscriptions('bucket', false)['watch'][0], ['name' => 'late']);
        $this->assertNotNull($pending);
        $store->remove('bucket');
        $store->register('bucket', 'watch', ['name' => true], ['name' => 'A'], false, static fn () => 'id');
        $store->acknowledge($pending);
        $this->assertSame(['watch' => [['id', ['name' => true]]]], $store->all('bucket'));
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
        for ($i = 0; $i < 2; ++$i) {
            $this->assertSame([['id', ['name' => 'A']]], self::publish($store, ['name' => 'A'], true));
        }
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
        $this->assertSame(['watch' => [['id', ['name' => true]]]], $store->all('bucket'));
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

    public function testMissingLockFactoryCannotRegisterWithoutLocking(): void
    {
        $store = new SubscriptionStore(new ArrayAdapter(), new ArrayAdapter(), null);
        $this->assertSame([], $store->all('bucket'));
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('GraphQL subscriptions require a configured Symfony Lock factory.');
        $store->register('bucket', 'watch', [], [], false, static fn () => 'id');
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

    #[TestWith([false])]
    #[TestWith([true])]
    public function testCleanupFailureDoesNotDiscardDeleteRecipients(bool $failRegistry): void
    {
        $failingCache = new class extends ArrayAdapter {
            public function deleteItem(mixed $key): bool
            {
                return false;
            }
        };
        $registry = $failRegistry ? $failingCache : new ArrayAdapter();
        $fingerprints = $failRegistry ? new ArrayAdapter() : $failingCache;
        $store = new SubscriptionStore($registry, $fingerprints, new LockFactory(new InMemoryStore()));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');
        $store->setLogger($logger);
        $store->register('bucket', 'watch', ['name' => true], [], false, static fn () => 'id');

        $this->assertSame(['watch' => [['id', ['name' => true]]]], $store->remove('bucket'));
        $this->assertSame($failRegistry, $registry->hasItem('bucket'));
    }

    public function testLatePublicationDoesNotRestoreFingerprintForRemovedSelection(): void
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

        $this->assertSame(['watch' => [['new', ['id' => true]]]], $store->all('bucket'));
        $this->assertCount(1, array_filter($fingerprints->getValues()));
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
