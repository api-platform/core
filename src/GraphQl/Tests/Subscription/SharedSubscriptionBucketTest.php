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
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\GraphQl\SubscriptionCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\State\ProcessorInterface;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

final class SharedSubscriptionBucketTest extends TestCase
{
    private ArrayAdapter $fingerprints;

    #[TestWith([false])]
    #[TestWith([true])]
    public function testOperationsShareOneReadWithoutSharingIdentityOrNormalization(bool $collection): void
    {
        $registry = new CountingSubscriptionRegistry();
        $manager = $this->manager($registry);
        $object = (object) ['id' => '/messages/42', 'tenant' => '7'];
        $operations = [];
        $ids = [];
        foreach (['watch', 'compact', 'details'] as $name) {
            $operation = $collection ? new SubscriptionCollection(name: $name, class: \stdClass::class) : new Subscription(name: $name, class: \stdClass::class);
            $operation = $operation->withMercure(['private' => true, 'private_fields' => ['tenant']]);
            $operations[] = $operation;
            $ids[] = $manager->retrieveSubscriptionId($this->context($object, ['id' => true]), [], $operation);
        }
        $extraId = $manager->retrieveSubscriptionId($this->context($object, ['name' => true]), [], $operations[0]);
        $this->assertSame($ids[0], $manager->retrieveSubscriptionId($this->context($object, ['id' => true]), [], $operations[0]));
        $this->assertCount(4, array_unique([...$ids, $extraId]));
        $this->assertCount(1, $registry->getValues());
        $bucket = $registry->getItem(array_key_first($registry->getValues()))->get();
        $this->assertSame([':watch', ':compact', ':details'], array_keys($bucket));
        $this->assertCount(2, $bucket[':watch']);

        $registry->reads = 0;
        $updates = iterator_to_array($manager->getUpdates($this->publications($object, $operations)));
        $this->assertSame(1, $registry->reads);
        $this->assertSame([$ids[0], $extraId, $ids[1], $ids[2]], array_map(static fn (array $entry) => $entry[1]->getId(), $updates));
        foreach ($updates as [$operation, $update]) {
            $this->assertSame($operation->getName(), $update->data['operation']);
            $manager->acknowledge($update);
        }
        $this->assertSame(['id' => true], $updates[0][1]->data['fields']);
        $this->assertSame(['name' => true], $updates[1][1]->data['fields']);
        $this->assertCount($collection ? 4 : 0, iterator_to_array($manager->getUpdates($this->publications($object, $operations))));
    }

    public function testBucketsSeparateItemsCollectionsAndPrivateScopes(): void
    {
        $registry = new CountingSubscriptionRegistry();
        $manager = $this->manager($registry);
        $item = new Subscription(name: 'watch', class: \stdClass::class, mercure: ['private' => true, 'private_fields' => ['tenant']]);
        $collection = new SubscriptionCollection(name: 'watch', class: \stdClass::class, mercure: ['private' => true, 'private_fields' => ['tenant']]);
        $first = (object) ['id' => '/messages/42', 'tenant' => '7'];
        $second = (object) ['id' => '/messages/43', 'tenant' => '7'];
        $otherTenant = (object) ['id' => '/messages/42', 'tenant' => '8'];
        $ids = [];
        foreach ([[$first, $item], [$second, $item], [$otherTenant, $item], [$first, $collection]] as [$object, $operation]) {
            $ids[] = $manager->retrieveSubscriptionId($this->context($object, ['id' => true]), [], $operation);
        }
        $this->assertCount(4, $registry->getValues());
        $this->assertCount(4, array_unique($ids));
        $this->assertSame($ids[3], $manager->retrieveSubscriptionId($this->context($second, ['id' => true]), [], $collection));
        $registry->reads = 0;
        $updates = iterator_to_array($manager->getUpdates($this->publications($first, [$item, $collection])));
        $this->assertSame(2, $registry->reads);
        $this->assertSame([$ids[0], $ids[3]], array_map(static fn (array $entry) => $entry[1]->getId(), $updates));
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testDeleteReadsSharedBucketOnceAndKeepsEachOperationsSnapshot(bool $collection): void
    {
        $registry = new CountingSubscriptionRegistry();
        $manager = $this->manager($registry);
        $object = (object) ['id' => '/messages/42', 'tenant' => '7'];
        $publications = [];
        $ids = [];
        foreach (['first', 'second'] as $name) {
            $operation = $collection ? new SubscriptionCollection(name: $name, class: \stdClass::class) : new Subscription(name: $name, class: \stdClass::class);
            $operation = $operation->withMercure(['private' => true, 'private_fields' => ['tenant']]);
            $ids[] = $manager->retrieveSubscriptionId($this->context($object, ['id' => true]), [], $operation);
            $snapshot = (object) ['id' => $object->id, 'iri' => 'https://example.com'.$object->id, 'type' => $name, 'private' => ['tenant' => '7']];
            $publications[] = ['object' => $snapshot, 'operation' => $operation];
        }
        $registry->reads = 0;
        $updates = iterator_to_array($manager->getUpdates($publications, 'delete'));
        $this->assertSame(1, $registry->reads);
        $this->assertSame($ids, array_map(static fn (array $entry) => $entry[1]->getId(), $updates));
        foreach ($updates as [$operation, $update]) {
            $this->assertSame(['type' => 'delete', 'payload' => ['id' => $object->id, 'iri' => 'https://example.com'.$object->id, 'type' => $operation->getName()]], $update->data);
            $manager->acknowledge($update);
        }
        $this->assertCount($collection ? 2 : 0, iterator_to_array($manager->getUpdates($publications, 'delete')));
        $this->assertEmpty(array_filter($this->fingerprints->getValues(), static fn ($value) => null !== $value));
    }

    public function testDisabledOperationInSharedBucketIsNotPublished(): void
    {
        $registry = new CountingSubscriptionRegistry();
        $manager = $this->manager($registry);
        $object = (object) ['id' => '/messages/42'];
        $first = new Subscription(name: 'first', class: \stdClass::class, mercure: true);
        $second = $first->withName('second');
        $id = $manager->retrieveSubscriptionId($this->context($object, ['id' => true]), [], $first);
        $manager->retrieveSubscriptionId($this->context($object, ['id' => true]), [], $second);
        $registry->reads = 0;
        $updates = iterator_to_array($manager->getUpdates($this->publications($object, [$first, $second->withMercure(false)])));
        $this->assertSame(1, $registry->reads);
        $this->assertCount(1, $updates);
        $this->assertSame($id, $updates[0][1]->getId());

        $snapshot = (object) ['id' => $object->id, 'iri' => 'https://example.com'.$object->id, 'type' => 'Message', 'private' => []];
        $deletions = iterator_to_array($manager->getUpdates($this->publications($snapshot, [$first, $second->withMercure(false)]), 'delete'));
        $this->assertCount(1, $deletions);
        $this->assertSame($id, $deletions[0][1]->getId());
        $this->assertEmpty(array_filter($registry->getValues(), static fn ($value) => null !== $value));
        $this->assertEmpty(array_filter($this->fingerprints->getValues(), static fn ($value) => null !== $value));
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testSameOperationNameOnDifferentGraphQlResourcesRemainsDistinct(bool $collection): void
    {
        $registry = new CountingSubscriptionRegistry();
        $manager = $this->manager($registry);
        $object = (object) ['id' => '/messages/42'];
        $operation = $collection ? new SubscriptionCollection(name: 'watch', class: \stdClass::class, mercure: true) : new Subscription(name: 'watch', class: \stdClass::class, mercure: true);
        $first = $operation->withShortName('PublicMessage');
        $second = $operation->withShortName('AdminMessage');
        $firstId = $manager->retrieveSubscriptionId($this->context($object, ['id' => true]), [], $first);
        $secondId = $manager->retrieveSubscriptionId($this->context($object, ['id' => true]), [], $second);
        $this->assertNotSame($firstId, $secondId);
        $this->assertCount(1, $registry->getValues());
        $registry->reads = 0;
        $updates = iterator_to_array($manager->getUpdates($this->publications($object, [$first, $second])));
        $this->assertSame(1, $registry->reads);
        $this->assertSame([$first, $second], array_column($updates, 0));
        $this->assertSame([$firstId, $secondId], array_map(static fn (array $entry) => $entry[1]->getId(), $updates));
        $this->assertSame(['PublicMessage', 'AdminMessage'], array_map(static fn (array $entry) => $entry[1]->data['shortName'], $updates));
    }

    public function testEquivalentPartitionsShareBucketRegardlessOfFieldOrder(): void
    {
        $registry = new CountingSubscriptionRegistry();
        $manager = $this->manager($registry);
        $object = (object) ['id' => '/messages/42', 'tenant' => '7', 'chat' => '8'];
        $first = new Subscription(name: 'first', class: \stdClass::class, mercure: ['private' => true, 'private_fields' => ['tenant', 'chat']]);
        $second = $first->withName('second')->withMercure(['private' => true, 'private_fields' => ['chat', 'tenant']]);
        $third = $first->withName('third')->withMercure(['private' => true, 'private_fields' => ['tenant']]);
        $ids = [];
        foreach ([$first, $second, $third] as $operation) {
            $ids[] = $manager->retrieveSubscriptionId($this->context($object, ['id' => true]), [], $operation);
        }
        $this->assertCount(2, $registry->getValues());
        $this->assertCount(3, array_unique($ids));
        $registry->reads = 0;
        $updates = iterator_to_array($manager->getUpdates($this->publications($object, [$first, $second, $third])));
        $this->assertSame(2, $registry->reads);
        $this->assertSame($ids, array_map(static fn (array $entry) => $entry[1]->getId(), $updates));
    }

    private function manager(CountingSubscriptionRegistry $registry): SubscriptionManager
    {
        $iri = $this->createStub(IriConverterInterface::class);
        $iri->method('getIriFromResource')->willReturnCallback(static fn (object $object) => $object->id);
        $normalizer = $this->createStub(ProcessorInterface::class);
        $normalizer->method('process')->willReturnCallback(static fn ($object, Subscription $operation, $variables, $context) => ['operation' => $operation->getName(), 'shortName' => $operation->getShortName(), 'fields' => $context['fields']]);

        $this->fingerprints = new ArrayAdapter();

        return new SubscriptionManager(new SubscriptionStore($registry, $this->fingerprints, new LockFactory(new InMemoryStore())), new SubscriptionIdentifierGenerator(), $normalizer, $iri);
    }

    private function context(object $object, array $fields): array
    {
        $info = $this->createStub(ResolveInfo::class);
        $info->method('getFieldSelection')->willReturn($fields);

        return ['args' => ['input' => ['id' => $object->id]], 'info' => $info, 'graphql_context' => ['previous_object' => $object]];
    }

    private function publications(object $object, array $operations): array
    {
        return array_map(static fn (Subscription $operation) => ['object' => $object, 'operation' => $operation], $operations);
    }
}

final class CountingSubscriptionRegistry extends ArrayAdapter
{
    public int $reads = 0;

    public function getItem(mixed $key): CacheItem
    {
        ++$this->reads;

        return parent::getItem($key);
    }
}
