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

use ApiPlatform\GraphQl\Subscription\SubscriptionIdentifierGenerator;
use ApiPlatform\GraphQl\Subscription\SubscriptionManager;
use ApiPlatform\GraphQl\Subscription\SubscriptionStore;
use ApiPlatform\GraphQl\Tests\Fixtures\ApiResource\Dummy;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\GraphQl\SubscriptionCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\State\ProcessorInterface;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Lock\BlockingStoreInterface;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

final class SubscriptionConcurrencyTest extends TestCase
{
    private CooperativeSubscriptionLockStore $locks;
    private ArrayAdapter $fingerprints;

    protected function setUp(): void
    {
        $this->locks = new CooperativeSubscriptionLockStore();
        $this->fingerprints = new ArrayAdapter();
    }

    private static function publish(SubscriptionManager $manager, object $object, Subscription $operation, string $type = 'update'): array
    {
        $payloads = [];
        foreach ($manager->getUpdates([['object' => $object, 'operation' => $operation]], $type) as [, $update]) {
            $payloads[] = [$update->getId(), $update->data];
            $manager->acknowledge($update);
        }

        return $payloads;
    }

    public function testConcurrentPublicationsLeaveAmbiguousFingerprintUncached(): void
    {
        $cache = new InterleavedSubscriptionCache();
        $first = $this->createManager($cache);
        $second = $this->createManager($cache);
        $operation = new Subscription(name: 'watch', class: Dummy::class, mercure: true);
        $id = $first->retrieveSubscriptionId($this->context(['name' => true]), ['dummy' => ['name' => 'initial']], $operation);
        $earlier = new Dummy();
        $earlier->description = 'earlier';
        $later = new Dummy();
        $later->description = 'later';
        $pending = iterator_to_array($first->getUpdates([['object' => $earlier, 'operation' => $operation]]));
        $this->assertCount(1, $pending);
        // Preparing an update holds no lock while another publisher completes.
        $this->assertSame([[$id, ['dummy' => ['name' => 'later']]]], self::publish($second, $later, $operation));
        $first->acknowledge($pending[0][1]);

        $this->assertSame([[$id, ['dummy' => ['name' => 'later']]]], self::publish($first, $later, $operation));
        $this->assertSame([], self::publish($first, $later, $operation));
    }

    public function testInitialItemPayloadIsSuppressedButCollectionsKeepPublishing(): void
    {
        $cache = new InterleavedSubscriptionCache();
        $manager = $this->createManager($cache);
        $item = new Subscription(name: 'watch', class: Dummy::class, mercure: true);
        $collection = new SubscriptionCollection(name: 'watchCollection', class: Dummy::class, mercure: true);
        $initial = ['dummy' => ['name' => 'Changed'], 'clientSubscriptionId' => 'client'];
        $manager->retrieveSubscriptionId($this->context(['name' => true]), $initial, $item);
        $collectionId = $manager->retrieveSubscriptionId($this->context(['name' => true]), $initial, $collection);

        $this->assertSame([], self::publish($manager, new Dummy(), $item));
        foreach (['create', 'update', 'update'] as $type) {
            $this->assertSame([[$collectionId, ['dummy' => ['name' => 'Changed']]]], self::publish($manager, new Dummy(), $collection, $type));
        }
    }

    public function testFailedPublicationDoesNotSuppressRetry(): void
    {
        $cache = new InterleavedSubscriptionCache();
        $manager = $this->createManager($cache);
        $operation = new Subscription(name: 'watch', class: Dummy::class, mercure: true);
        $id = $manager->retrieveSubscriptionId($this->context(['name' => true]), [], $operation);
        $pending = iterator_to_array($manager->getUpdates([['object' => new Dummy(), 'operation' => $operation]]));
        $this->assertCount(1, $pending);
        // Failed delivery does not acknowledge the prepared update.

        $this->assertSame([[$id, ['dummy' => ['name' => 'Changed']]]], self::publish($manager, new Dummy(), $operation));
        $this->assertSame([], self::publish($manager, new Dummy(), $operation));
    }

    #[TestWith([false, false])]
    #[TestWith([true, false])]
    #[TestWith([false, true])]
    #[TestWith([true, true])]
    public function testConcurrentRegistrationsBothReceiveUpdates(bool $collection, bool $differentOperations): void
    {
        $cache = new InterleavedSubscriptionCache();
        $firstManager = $this->createManager($cache);
        $secondManager = $this->createManager($cache);
        $operation = $collection
            ? new SubscriptionCollection(name: 'watch', class: Dummy::class, mercure: true)
            : new Subscription(name: 'watch', class: Dummy::class, mercure: true);

        $secondOperation = $differentOperations ? $operation->withName('other') : $operation;
        $secondFields = $differentOperations ? ['id' => true] : ['name' => true];

        // Pause A after reading its cache snapshot; B registers before A saves.
        $cache->pauseNextRead = true;
        $first = new \Fiber(fn () => $firstManager->retrieveSubscriptionId($this->context(['id' => true]), [], $operation));
        $first->start();
        $this->assertTrue($first->isSuspended());
        $second = new \Fiber(fn () => $secondManager->retrieveSubscriptionId($this->context($secondFields), [], $secondOperation));
        $second->start();
        $this->assertTrue($second->isSuspended(), 'Registration must wait for the same bucket lock.');
        $first->resume();
        $second->resume();
        $secondId = $second->getReturn();
        $firstId = $first->getReturn();
        $this->assertNotSame($firstId, $secondId);

        $publications = [['object' => new Dummy(), 'operation' => $operation]];
        if ($differentOperations) {
            $publications[] = ['object' => $publications[0]['object'], 'operation' => $secondOperation];
        }
        $updates = [];
        foreach ($firstManager->getUpdates($publications) as [, $update]) {
            $updates[] = [$update->getId(), $update->data];
            $firstManager->acknowledge($update);
        }
        $this->assertEqualsCanonicalizing([
            [$firstId, ['dummy' => ['id' => '/dummies/1']]],
            [$secondId, ['dummy' => $differentOperations ? ['id' => '/dummies/1'] : ['name' => 'Changed']]],
        ], $updates);
    }

    public function testPayloadRefreshDoesNotEraseConcurrentRegistration(): void
    {
        $cache = new InterleavedSubscriptionCache();
        $publisher = $this->createManager($cache);
        $subscriber = $this->createManager($cache);
        $operation = new Subscription(name: 'watch', class: Dummy::class, mercure: true);
        $firstId = $subscriber->retrieveSubscriptionId($this->context(['id' => true]), [], $operation);

        $cache->pauseNextRead = true;
        $publication = new \Fiber(static fn () => self::publish($publisher, new Dummy(), $operation));
        $publication->start();
        $this->assertTrue($publication->isSuspended());
        $secondId = $subscriber->retrieveSubscriptionId($this->context(['name' => true]), [], $operation);
        $publication->resume();
        $this->assertSame([[$firstId, ['dummy' => ['id' => '/dummies/1']]]], $publication->getReturn());

        // The first selection is unchanged, but the newly registered one must survive.
        $this->assertSame([
            [$secondId, ['dummy' => ['name' => 'Changed']]],
        ], self::publish($publisher, new Dummy(), $operation));
    }

    public function testPayloadRefreshDoesNotRestoreDeletedSubscriptions(): void
    {
        $cache = new InterleavedSubscriptionCache();
        $publisher = $this->createManager($cache);
        $deleter = $this->createManager($cache);
        $operation = new Subscription(name: 'watch', class: Dummy::class, mercure: true);
        $id = $publisher->retrieveSubscriptionId($this->context(['id' => true]), [], $operation);

        $cache->pauseNextRead = true;
        $publication = new \Fiber(static fn () => self::publish($publisher, new Dummy(), $operation));
        $publication->start();
        $this->assertTrue($publication->isSuspended());
        $snapshot = (object) ['id' => '/dummies/1', 'iri' => 'https://example.com/dummies/1', 'type' => 'Dummy', 'private' => []];
        $this->assertSame([
            [$id, ['type' => 'delete', 'payload' => ['id' => $snapshot->id, 'iri' => $snapshot->iri, 'type' => $snapshot->type]]],
        ], self::publish($deleter, $snapshot, $operation, 'delete'));
        $this->assertCacheHasNoItems($cache);
        $publication->resume();

        $this->assertCacheHasNoItems($cache);
    }

    private function assertCacheHasNoItems(ArrayAdapter $cache): void
    {
        // ArrayAdapter records misses as null values; these are not cache entries.
        foreach ([$cache, $this->fingerprints] as $pool) {
            $this->assertEmpty(array_filter($pool->getValues(), static fn ($value): bool => null !== $value), 'An in-flight update must not recreate deleted subscription state.');
        }
    }

    private function createManager(InterleavedSubscriptionCache $cache): SubscriptionManager
    {
        $iriConverter = $this->createStub(IriConverterInterface::class);
        $iriConverter->method('getIriFromResource')->willReturn('/dummies/1');
        $normalizer = $this->createStub(ProcessorInterface::class);
        $normalizer->method('process')->willReturnCallback(static function ($object, $operation, $uriVariables, $context): array {
            return ['dummy' => array_intersect_key(['id' => '/dummies/1', 'name' => $object->description ?? 'Changed'], $context['fields']['dummy'])];
        });

        return new SubscriptionManager(new SubscriptionStore($cache, $this->fingerprints, new LockFactory($this->locks)), new SubscriptionIdentifierGenerator(), $normalizer, $iriConverter);
    }

    private function context(array $selection): array
    {
        $info = $this->createStub(ResolveInfo::class);
        $info->method('getFieldSelection')->willReturn(['dummy' => $selection]);

        return ['args' => ['input' => ['id' => '/dummies/1']], 'info' => $info];
    }
}

final class InterleavedSubscriptionCache extends ArrayAdapter
{
    public bool $pauseNextRead = false;

    public function getItem(mixed $key): CacheItem
    {
        $item = parent::getItem($key);
        if ($this->pauseNextRead) {
            $this->pauseNextRead = false;
            \Fiber::suspend();
        }

        return $item;
    }
}

final class CooperativeSubscriptionLockStore extends InMemoryStore implements BlockingStoreInterface
{
    public function waitAndSave(Key $key): void
    {
        while (true) {
            try {
                parent::save($key);

                return;
            } catch (LockConflictedException) {
                \Fiber::suspend();
            }
        }
    }
}
