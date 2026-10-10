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

use ApiPlatform\GraphQl\Subscription\RegisteredSubscription;
use ApiPlatform\GraphQl\Subscription\SubscriptionStore;
use ApiPlatform\GraphQl\Subscription\SubscriptionUpdate;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Lock\BlockingStoreInterface;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\Exception\LockExpiredException;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\Lock\Store\InMemoryStore;

final class SubscriptionLockTest extends TestCase
{
    public function testFailedBlockingAcquisitionDoesNotReadOrWriteTheRegistry(): void
    {
        $registry = $this->createMock(CacheItemPoolInterface::class);
        $registry->expects($this->never())->method('getItem');
        $registry->expects($this->never())->method('save');
        $lock = $this->createMock(SharedLockInterface::class);
        $lock->expects($this->once())->method('acquire')->with(true)->willReturn(false);
        $lock->expects($this->never())->method('release');
        $factory = $this->createStub(LockFactory::class);
        $factory->method('createLock')->willReturn($lock);
        $store = new SubscriptionStore($registry, new ArrayAdapter(), $factory);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot acquire the GraphQL subscription lock.');
        $store->register('bucket', 'watch', [], null, true, static fn () => 'id');
    }

    public function testBlockingRegistrationRequestsFiveSecondLease(): void
    {
        $lock = $this->createMock(SharedLockInterface::class);
        $lock->expects($this->once())->method('acquire')->with(true)->willReturn(true);
        $lock->method('isExpired')->willReturn(false);
        $lock->expects($this->once())->method('release');
        $factory = $this->createMock(LockFactory::class);
        $factory->expects($this->once())->method('createLock')->with('api_platform.bucket', 5.0)->willReturn($lock);
        $store = new SubscriptionStore(new ArrayAdapter(), new ArrayAdapter(), $factory);

        $this->assertSame('id', $store->register('bucket', 'watch', [], null, true, static fn () => 'id'));
        $this->assertSame(['watch' => [['id', []]]], $store->all('bucket'));
    }

    public function testExpiredRegistryReadCannotOverwriteAnotherRegistration(): void
    {
        $expired = false;
        $lock = $this->createMock(SharedLockInterface::class);
        $lock->expects($this->once())->method('acquire')->willReturn(true);
        // Capture by reference so the simulated slow read expires the held lease.
        $lock->method('isExpired')->willReturnCallback(static function () use (&$expired): bool { return $expired; });
        $lock->expects($this->once())->method('release');
        $factory = $this->createStub(LockFactory::class);
        $factory->method('createLock')->willReturn($lock);
        $registry = $this->createMock(CacheItemPoolInterface::class);
        $registry->expects($this->once())->method('getItem')->willReturnCallback(static function () use (&$expired): CacheItem {
            $expired = true;

            return new CacheItem();
        });
        $registry->expects($this->never())->method('save');
        $store = new SubscriptionStore($registry, new ArrayAdapter(), $factory);

        $this->expectException(LockExpiredException::class);
        $store->register('bucket', 'watch', [], null, true, static fn () => 'id');
    }

    public function testBlockingRegistrationResumesWhenDeadOwnersLeaseExpires(): void
    {
        $locks = new ExpiringSubscriptionLockStore();
        $factory = new LockFactory($locks);
        $owner = $factory->createLock('api_platform.bucket', 5.0, false);
        $this->assertTrue($owner->acquire());
        $store = new SubscriptionStore(new ArrayAdapter(), new ArrayAdapter(), $factory);
        $registration = new \Fiber(static fn () => $store->register('bucket', 'watch', [], null, true, static fn () => 'id'));
        $registration->start();
        $this->assertTrue($registration->isSuspended());
        $this->assertSame([], $store->all('bucket'));

        $locks->advance(5);
        $registration->resume();
        $this->assertSame('id', $registration->getReturn());
        $this->assertSame(['watch' => [['id', []]]], $store->all('bucket'));
    }

    public function testResumingAfterLeaseExpiryDoesNotOverwriteTheNewOwner(): void
    {
        $locks = new ExpiringSubscriptionLockStore();
        $factory = new LockFactory($locks);
        $registry = new class extends ArrayAdapter {
            public bool $pause = true;

            public function getItem(mixed $key): CacheItem
            {
                $item = parent::getItem($key);
                if ($this->pause) {
                    $this->pause = false;
                    \Fiber::suspend();
                }

                return $item;
            }
        };
        $store = new SubscriptionStore($registry, new ArrayAdapter(), $factory);
        $first = new \Fiber(static fn () => $store->register('bucket', 'first', [], null, true, static fn () => 'old'));
        $first->start();
        $this->assertTrue($first->isSuspended());
        $locks->advance(5);
        $this->assertSame('new', $store->register('bucket', 'second', [], null, true, static fn () => 'new'));
        try {
            $first->resume();
            $this->fail('An expired owner must not persist its old cache snapshot.');
        } catch (LockExpiredException) {
            $this->assertSame(['second' => [['new', []]]], $store->all('bucket'));
        }
    }

    public function testDeletePreservesRecipientsWithoutCleaningNewStateAfterBucketExpiry(): void
    {
        $registry = new ArrayAdapter();
        $entries = ['watch' => [['first', []], ['second', []]]];
        $registry->save($registry->getItem('bucket')->set($entries));
        $fingerprints = $this->createMock(CacheItemPoolInterface::class);
        $fingerprints->expects($this->never())->method('deleteItem');
        $expired = false;
        $bucketLock = $this->createMock(SharedLockInterface::class);
        $bucketLock->expects($this->once())->method('acquire')->willReturn(true);
        $bucketLock->method('isExpired')->willReturnCallback(static function () use (&$expired): bool { return $expired; });
        $bucketLock->expects($this->once())->method('release');
        $fingerprintLock = $this->createMock(SharedLockInterface::class);
        $fingerprintLock->expects($this->once())->method('acquire')->willReturnCallback(static function () use (&$expired): bool {
            $expired = true;

            return true;
        });
        $fingerprintLock->expects($this->once())->method('release');
        $factory = $this->createMock(LockFactory::class);
        $factory->expects($this->exactly(2))->method('createLock')->willReturn($bucketLock, $fingerprintLock);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with('Could not lock deleted GraphQL subscription state for cleanup.', $this->isArray());
        $store = new SubscriptionStore($registry, $fingerprints, $factory);
        $store->setLogger($logger);

        $this->assertSame($entries, $store->remove('bucket'));
        $this->assertFalse($registry->hasItem('bucket'));
    }

    public function testAcknowledgementAcquisitionFailureLeavesAnotherWorkersFingerprintUntouched(): void
    {
        $lock = $this->createMock(SharedLockInterface::class);
        $lock->expects($this->once())->method('acquire')->with(true)->willThrowException(new LockAcquiringException('Backend unavailable.'));
        $lock->expects($this->never())->method('release');
        $factory = $this->createStub(LockFactory::class);
        $factory->method('createLock')->willReturn($lock);
        $fingerprints = $this->createMock(CacheItemPoolInterface::class);
        $fingerprints->expects($this->never())->method('getItem');
        $fingerprints->expects($this->never())->method('deleteItem');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with('Could not record a GraphQL subscription publication.', $this->isArray());
        $store = new SubscriptionStore(new ArrayAdapter(), $fingerprints, $factory);
        $store->setLogger($logger);

        $store->acknowledge(new SubscriptionUpdate(new RegisteredSubscription('bucket', 'id', [], false, null), [], 'hash'));
    }
}

/** Simulates expiry and dead owners without wall-clock waits or a network service. */
final class ExpiringSubscriptionLockStore extends InMemoryStore implements BlockingStoreInterface
{
    /** @var array<string, array{Key, float}> */
    private array $owners = [];

    private float $time = 0;

    public function advance(float $seconds): void
    {
        $this->time += $seconds;
    }

    public function waitAndSave(Key $key): void
    {
        while (true) {
            try {
                $this->save($key);

                return;
            } catch (LockConflictedException) {
                \Fiber::suspend();
            }
        }
    }

    public function save(Key $key): void
    {
        $this->expire($key);
        parent::save($key);
    }

    public function putOffExpiration(Key $key, float $ttl): void
    {
        $this->owners[(string) $key] = [$key, $this->time + $ttl];
    }

    public function exists(Key $key): bool
    {
        $this->expire($key);

        return parent::exists($key);
    }

    private function expire(Key $key): void
    {
        if (isset($this->owners[(string) $key]) && $this->owners[(string) $key][1] <= $this->time) {
            [$owner] = $this->owners[(string) $key];
            $owner->reduceLifetime(-1);
            parent::delete($owner);
            unset($this->owners[(string) $key]);
        }
    }
}
