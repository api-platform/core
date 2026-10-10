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
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Lock\BlockingStoreInterface;
use Symfony\Component\Lock\Exception\LockConflictedException;
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
        $this->assertSame(['watch' => [['id', []]]], $store->fetch('bucket'));
    }

    public function testLeaseExpiryDoesNotFailRegistration(): void
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
        $registry->expects($this->once())->method('save')->willReturn(true);
        $store = new SubscriptionStore($registry, new ArrayAdapter(), $factory);

        $this->assertSame('id', $store->register('bucket', 'watch', [], null, true, static fn () => 'id'));
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
        $this->assertSame([], $store->fetch('bucket'));

        $locks->advance(5);
        $registration->resume();
        $this->assertSame('id', $registration->getReturn());
        $this->assertSame(['watch' => [['id', []]]], $store->fetch('bucket'));
    }

    public function testLeaseExpiryDoesNotPreventDeletion(): void
    {
        $entries = ['watch' => [['id', []]]];
        $cache = new ArrayAdapter();
        $cache->save($cache->getItem('bucket')->set($entries));
        $expired = false;
        $lock = $this->createMock(SharedLockInterface::class);
        $lock->expects($this->once())->method('acquire')->with(true)->willReturn(true);
        $lock->method('isExpired')->willReturnCallback(static function () use (&$expired): bool { return $expired; });
        $lock->expects($this->once())->method('release');
        $factory = $this->createMock(LockFactory::class);
        $factory->expects($this->once())->method('createLock')->willReturn($lock);
        $registry = $this->createMock(CacheItemPoolInterface::class);
        $registry->expects($this->once())->method('getItem')->willReturnCallback(static function () use (&$expired, $cache): CacheItem {
            $expired = true;

            return $cache->getItem('bucket');
        });
        $registry->expects($this->once())->method('deleteItem')->with('bucket')->willReturnCallback($cache->deleteItem(...));
        $store = new SubscriptionStore($registry, new ArrayAdapter(), $factory);

        $this->assertSame($entries, $store->remove('bucket'));
        $this->assertFalse($cache->hasItem('bucket'));
    }

    public function testAcknowledgementDoesNotReadTheRegistryOrAcquireLocks(): void
    {
        $registry = $this->createMock(CacheItemPoolInterface::class);
        $registry->expects($this->never())->method('getItem');
        $registry->expects($this->never())->method('save');
        $factory = $this->createMock(LockFactory::class);
        $factory->expects($this->never())->method('createLock');
        $fingerprints = new ArrayAdapter();
        $store = new SubscriptionStore($registry, $fingerprints, $factory);

        $store->acknowledge(new SubscriptionUpdate(new RegisteredSubscription('id', [], false, null), [], 'hash'));

        $this->assertSame('hash', $fingerprints->getItem('graphql_subscription_fingerprint_id')->get());
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
