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
use ApiPlatform\GraphQl\Subscription\SubscriptionIdentifierGeneratorInterface;
use ApiPlatform\GraphQl\Subscription\SubscriptionManager;
use ApiPlatform\GraphQl\Subscription\SubscriptionStore;
use ApiPlatform\GraphQl\Tests\Fixtures\ApiResource\Dummy;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\GraphQl\SubscriptionCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\State\ProcessorInterface;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Argument\Token\TokenInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\PropertyAccess\Exception\AccessException;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SubscriptionManagerTest extends TestCase
{
    use ProphecyTrait;

    private static function publish(SubscriptionManager $manager, object $object, Subscription $operation, string $type = 'update'): array
    {
        $payloads = [];
        foreach ($manager->getUpdates([['object' => $object, 'operation' => $operation]], $type) as [, $update]) {
            $payloads[] = [$update->getId(), $update->data];
            $manager->acknowledge($update);
        }

        return $payloads;
    }

    /** @param Subscription[] $operations */
    private static function publishOperations(SubscriptionManager $manager, object $object, array $operations, string $type = 'update'): array
    {
        $publications = array_map(static fn (Subscription $operation): array => ['object' => $object, 'operation' => $operation], array_values($operations));
        $payloads = [];
        foreach ($manager->getUpdates($publications, $type) as [, $update]) {
            $payloads[] = [$update->getId(), $update->data];
            $manager->acknowledge($update);
        }

        return $payloads;
    }

    private ObjectProphecy $subscriptionsCacheProphecy;
    private ObjectProphecy $subscriptionIdentifierGeneratorProphecy;
    private ObjectProphecy $normalizeProcessor;
    private ObjectProphecy $iriConverterProphecy;
    private SubscriptionManager $subscriptionManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->subscriptionsCacheProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $this->subscriptionIdentifierGeneratorProphecy = $this->prophesize(SubscriptionIdentifierGeneratorInterface::class);
        $this->normalizeProcessor = $this->prophesize(ProcessorInterface::class);
        $this->iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $this->subscriptionManager = new SubscriptionManager(new SubscriptionStore($this->subscriptionsCacheProphecy->reveal(), new ArrayAdapter(), new LockFactory(new InMemoryStore())), $this->subscriptionIdentifierGeneratorProphecy->reveal(), $this->normalizeProcessor->reveal(), $this->iriConverterProphecy->reveal());
    }

    private function createCollectionSubscription(array|bool|null $mercure = null): SubscriptionCollection
    {
        return (new SubscriptionCollection())
            ->withName('update_collection')
            ->withClass(Dummy::class)
            ->withShortName('Dummy')
            ->withMercure($mercure);
    }

    private function createItemSubscription(array|bool|null $mercure = null): Subscription
    {
        return (new Subscription())
            ->withName('update')
            ->withClass(Dummy::class)
            ->withShortName('Dummy')
            ->withMercure($mercure);
    }

    private function cacheKey(string $iri, ?string $resource = null, bool $collection = false, array $privateFieldData = []): string
    {
        return 'graphql_subscription_'.hash('sha256', serialize([
            'resource' => $resource,
            'collection' => $collection,
            'iri' => $collection ? null : $iri,
            'private' => $privateFieldData,
        ]));
    }

    private function scopedFields(array $fields): TokenInterface
    {
        return Argument::that(static function (array $identity) use ($fields): bool {
            if (!isset($identity['__subscription_scope']) || !\is_string($identity['__subscription_scope'])) {
                return false;
            }
            unset($identity['__subscription_scope'], $identity['__subscription_operation']);

            return $fields === $identity;
        });
    }

    public function testRetrieveSubscriptionIdNoIdentifier(): void
    {
        $info = $this->prophesize(ResolveInfo::class);
        $info->getFieldSelection(\PHP_INT_MAX)->willReturn([]);

        $context = ['args' => [], 'info' => $info->reveal(), 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true];

        $this->assertNull($this->subscriptionManager->retrieveSubscriptionId($context, null, $this->createItemSubscription()));
    }

    public function testRetrieveSubscriptionIdNoHit(): void
    {
        $infoProphecy = $this->prophesize(ResolveInfo::class);
        $fields = ['fields'];
        $infoProphecy->getFieldSelection(\PHP_INT_MAX)->willReturn($fields);

        $context = ['args' => ['input' => ['id' => '/foos/34']], 'info' => $infoProphecy->reveal(), 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true];
        $result = ['result', 'clientSubscriptionId' => 'client-subscription-id'];

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false);
        $subscriptionId = 'subscriptionId';
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($this->scopedFields($fields))->willReturn($subscriptionId);
        $cacheItemProphecy->set(['Dummy:update' => [[$subscriptionId, $fields]]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/foos/34', Dummy::class))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled()->willReturn(true);

        $this->assertSame($subscriptionId, $this->subscriptionManager->retrieveSubscriptionId($context, $result, $this->createItemSubscription()));
    }

    public function testRetrieveSubscriptionIdHitNotCached(): void
    {
        $infoProphecy = $this->prophesize(ResolveInfo::class);
        $fields = ['fields'];
        $infoProphecy->getFieldSelection(\PHP_INT_MAX)->willReturn($fields);

        $context = ['args' => ['input' => ['id' => '/foos/34']], 'info' => $infoProphecy->reveal(), 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true];
        $result = ['result', 'clientSubscriptionId' => 'client-subscription-id'];

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(true);
        $cachedSubscriptions = [
            ['subscriptionIdFoo', ['fieldsFoo']],
            ['subscriptionIdBar', ['fieldsBar']],
        ];
        $cacheItemProphecy->get()->willReturn(['Dummy:update' => $cachedSubscriptions]);
        $subscriptionId = 'subscriptionId';
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($this->scopedFields($fields))->willReturn($subscriptionId);
        $cacheItemProphecy->set(['Dummy:update' => array_merge($cachedSubscriptions, [[$subscriptionId, $fields]])])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/foos/34', Dummy::class))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled()->willReturn(true);

        $this->assertSame($subscriptionId, $this->subscriptionManager->retrieveSubscriptionId($context, $result, $this->createItemSubscription()));
    }

    public function testRetrieveSubscriptionIdHitCached(): void
    {
        $infoProphecy = $this->prophesize(ResolveInfo::class);
        $fields = ['fieldsBar'];
        $infoProphecy->getFieldSelection(\PHP_INT_MAX)->willReturn($fields);

        $context = ['args' => ['input' => ['id' => '/foos/34']], 'info' => $infoProphecy->reveal(), 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true];
        $result = ['result'];

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(true);
        $cacheItemProphecy->get()->willReturn(['Dummy:update' => [
            ['subscriptionIdFoo', ['fieldsFoo']],
            ['subscriptionIdBar', ['fieldsBar']],
        ]]);
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($this->scopedFields($fields))->shouldNotBeCalled();
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/foos/34', Dummy::class))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());

        $this->assertSame('subscriptionIdBar', $this->subscriptionManager->retrieveSubscriptionId($context, $result, $this->createItemSubscription()));
    }

    public function testRetrieveSubscriptionIdHitCachedDifferentFieldsOrder(): void
    {
        $infoProphecy = $this->prophesize(ResolveInfo::class);
        $fields = [
            'third' => true,
            'second' => [
                'second' => true,
                'third' => true,
                'first' => true,
            ],
            'first' => true,
        ];
        $infoProphecy->getFieldSelection(\PHP_INT_MAX)->willReturn($fields);

        $context = ['args' => ['input' => ['id' => '/foos/34']], 'info' => $infoProphecy->reveal(), 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true];
        $result = ['result'];

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(true);
        $cacheItemProphecy->get()->willReturn(['Dummy:update' => [
            ['subscriptionIdFoo', [
                'first' => true,
                'second' => [
                    'first' => true,
                    'second' => true,
                    'third' => true,
                ],
                'third' => true,
            ]],
            ['subscriptionIdBar', ['fieldsBar']],
        ]]);
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($this->scopedFields($fields))->shouldNotBeCalled();
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/foos/34', Dummy::class))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());

        $this->assertSame('subscriptionIdFoo', $this->subscriptionManager->retrieveSubscriptionId($context, $result, $this->createItemSubscription()));
    }

    public function testRetrieveSubscriptionIdPartitionedPrivateItemUsesDedicatedCacheKey(): void
    {
        $infoProphecy = $this->prophesize(ResolveInfo::class);
        $fields = ['fields' => true];
        $infoProphecy->getFieldSelection(\PHP_INT_MAX)->willReturn($fields);

        $previousObject = new class {
            public function getTenant(): int
            {
                return 42;
            }
        };

        $context = [
            'args' => ['input' => ['id' => '/foos/34']],
            'info' => $infoProphecy->reveal(),
            'is_collection' => false,
            'is_mutation' => false,
            'is_subscription' => true,
            'graphql_context' => ['previous_object' => $previousObject],
        ];
        $result = ['result', 'clientSubscriptionId' => 'client-subscription-id'];
        $operation = new Subscription(mercure: ['private' => true, 'private_fields' => ['tenant']]);

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false);
        $subscriptionId = 'subscriptionId';
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($this->scopedFields($fields))->willReturn($subscriptionId);
        $cacheItemProphecy->set([':update_subscription' => [[$subscriptionId, $fields]]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/foos/34', privateFieldData: ['tenant' => '42']))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled()->willReturn(true);

        $this->assertSame($subscriptionId, $this->subscriptionManager->retrieveSubscriptionId($context, $result, $operation));
    }

    public function testRetrieveSubscriptionIdPartitionedPrivateItemUsesPropertyAccess(): void
    {
        $infoProphecy = $this->prophesize(ResolveInfo::class);
        $fields = ['fields' => true];
        $infoProphecy->getFieldSelection(\PHP_INT_MAX)->willReturn($fields);

        $previousObject = new class {
            public int $tenant = 42;
        };

        $context = [
            'args' => ['input' => ['id' => '/foos/34']],
            'info' => $infoProphecy->reveal(),
            'is_collection' => false,
            'is_mutation' => false,
            'is_subscription' => true,
            'graphql_context' => ['previous_object' => $previousObject],
        ];
        $result = ['result', 'clientSubscriptionId' => 'client-subscription-id'];
        $operation = new Subscription(mercure: ['private' => true, 'private_fields' => ['tenant']]);

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false);
        $subscriptionId = 'propertyAccessSubscriptionId';
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($this->scopedFields($fields))->willReturn($subscriptionId);
        $cacheItemProphecy->set([':update_subscription' => [[$subscriptionId, $fields]]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/foos/34', privateFieldData: ['tenant' => '42']))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled()->willReturn(true);

        $this->assertSame($subscriptionId, $this->subscriptionManager->retrieveSubscriptionId($context, $result, $operation));
    }

    public function testRetrieveSubscriptionIdSharedPrivateItemDoesNotPartitionCacheKey(): void
    {
        $infoProphecy = $this->prophesize(ResolveInfo::class);
        $fields = ['fields' => true];
        $infoProphecy->getFieldSelection(\PHP_INT_MAX)->willReturn($fields);

        $context = [
            'args' => ['input' => ['id' => '/foos/34']],
            'info' => $infoProphecy->reveal(),
            'is_collection' => false,
            'is_mutation' => false,
            'is_subscription' => true,
        ];
        $result = ['result', 'clientSubscriptionId' => 'client-subscription-id'];
        $operation = new Subscription(mercure: ['private' => true]);

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false);
        $subscriptionId = 'sharedPrivateItemSubscriptionId';
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($this->scopedFields($fields))->willReturn($subscriptionId);
        $cacheItemProphecy->set([':update_subscription' => [[$subscriptionId, $fields]]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/foos/34'))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled()->willReturn(true);

        $this->assertSame($subscriptionId, $this->subscriptionManager->retrieveSubscriptionId($context, $result, $operation));
    }

    public function testRetrieveSubscriptionIdPartitionKeyUsesDeclaredFieldOrderAndNames(): void
    {
        $infoProphecy = $this->prophesize(ResolveInfo::class);
        $fields = ['fields' => true];
        $infoProphecy->getFieldSelection(\PHP_INT_MAX)->willReturn($fields);

        $previousObject = new class {
            public function getRegion(): string
            {
                return 'eu';
            }

            public function getTenant(): int
            {
                return 42;
            }
        };

        $context = [
            'args' => ['input' => ['id' => '/foos/34']],
            'info' => $infoProphecy->reveal(),
            'is_collection' => false,
            'is_mutation' => false,
            'is_subscription' => true,
            'graphql_context' => ['previous_object' => $previousObject],
        ];
        $result = ['result', 'clientSubscriptionId' => 'client-subscription-id'];
        $operation = new Subscription(mercure: ['private' => true, 'private_fields' => ['region', 'tenant']]);

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false);
        $subscriptionId = 'orderedPartitionSubscriptionId';
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($this->scopedFields($fields))->willReturn($subscriptionId);
        $cacheItemProphecy->set([':update_subscription' => [[$subscriptionId, $fields]]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/foos/34', privateFieldData: ['region' => 'eu', 'tenant' => '42']))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled()->willReturn(true);

        $this->assertSame($subscriptionId, $this->subscriptionManager->retrieveSubscriptionId($context, $result, $operation));
    }

    #[DataProvider('invalidPrivateScopes')]
    public function testInvalidPrivateScopeCannotRegister(bool $collection, ?object $object, array $fields, string $exception): void
    {
        $operation = $collection
            ? $this->createCollectionSubscription(['private' => true, 'private_fields' => $fields])
            : $this->createItemSubscription(['private' => true, 'private_fields' => $fields]);
        $info = $this->createStub(ResolveInfo::class);
        $info->method('getFieldSelection')->willReturn(['dummy' => ['id' => true]]);
        $this->subscriptionsCacheProphecy->getItem(Argument::any())->shouldNotBeCalled();
        $this->subscriptionsCacheProphecy->save(Argument::any())->shouldNotBeCalled();
        $this->expectException($exception);

        $this->subscriptionManager->retrieveSubscriptionId([
            'args' => ['input' => ['id' => '/dummies/1']],
            'info' => $info,
            'graphql_context' => ['previous_object' => $object],
        ], [], $operation);
    }

    public static function invalidPrivateScopes(): iterable
    {
        foreach ([false, true] as $collection) {
            $prefix = $collection ? 'collection ' : 'item ';
            yield $prefix.'missing object' => [$collection, null, ['tenant'], RuntimeException::class];
            yield $prefix.'missing field' => [$collection, new \stdClass(), ['tenant'], AccessException::class];
            yield $prefix.'partial fields' => [$collection, (object) ['tenant' => 'tenant-a'], ['tenant', 'chat'], AccessException::class];
            yield $prefix.'inaccessible field' => [$collection, new class {
                private string $tenant = 'tenant-a';

                public function __toString(): string
                {
                    return $this->tenant;
                }
            }, ['tenant'], AccessException::class];
        }
    }

    #[TestWith(['create'])]
    #[TestWith(['update'])]
    public function testIncompletePrivateScopeCannotPublish(string $type): void
    {
        $object = (object) ['tenant' => 'tenant-a'];
        $operations = [
            $this->createCollectionSubscription(['private' => true, 'private_fields' => ['tenant', 'chat']]),
        ];
        $this->subscriptionsCacheProphecy->getItem(Argument::any())->shouldNotBeCalled();
        $this->expectException(AccessException::class);

        self::publishOperations($this->subscriptionManager, $object, $operations, $type);
    }

    #[DataProvider('missingDeleteFields')]
    public function testDeleteWithIncompletePrivateFieldsCannotReadOrRemoveSubscriptionBuckets(array $private): void
    {
        $operations = [
            $this->createItemSubscription(['private' => true, 'private_fields' => ['tenant', 'chat']]),
        ];
        $this->subscriptionsCacheProphecy->getItem(Argument::any())->shouldNotBeCalled();
        $this->subscriptionsCacheProphecy->deleteItem(Argument::any())->shouldNotBeCalled();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('missing from the delete snapshot');

        self::publishOperations($this->subscriptionManager, (object) [
            'resourceClass' => Dummy::class,
            'id' => '/dummies/1',
            'iri' => 'http://example.com/dummies/1',
            'type' => 'Dummy',
            'private' => $private,
        ], $operations, 'delete');
    }

    public static function missingDeleteFields(): iterable
    {
        yield 'all missing' => [[]];
        yield 'one missing' => [['tenant' => 'tenant-a']];
    }

    #[DataProvider('subscriptionKinds')]
    public function testPrivatePartitionsHaveDistinctStableSubscriptionIds(bool $collection): void
    {
        $cache = new ArrayAdapter();
        $manager = new SubscriptionManager(new SubscriptionStore($cache, new ArrayAdapter(), new LockFactory(new InMemoryStore())), new SubscriptionIdentifierGenerator(), $this->normalizeProcessor->reveal(), $this->iriConverterProphecy->reveal());
        $mercure = ['private' => true, 'private_fields' => ['tenant']];
        $operation = $collection ? $this->createCollectionSubscription($mercure) : $this->createItemSubscription($mercure);
        $fields = ['dummy' => ['id' => true, 'name' => true]];
        $info = $this->prophesize(ResolveInfo::class);
        $info->getFieldSelection(\PHP_INT_MAX)->willReturn($fields);
        $context = ['args' => ['input' => ['id' => '/dummies/1']], 'info' => $info->reveal()];
        $contextA = $context + ['graphql_context' => ['previous_object' => (object) ['tenant' => 'tenant-a']]];
        $contextB = $context + ['graphql_context' => ['previous_object' => (object) ['tenant' => 'tenant-b']]];

        $idA = $manager->retrieveSubscriptionId($contextA, [], $operation);
        $idB = $manager->retrieveSubscriptionId($contextB, [], $operation);
        $this->assertNotSame($idA, $idB);
        $this->assertSame($idA, $manager->retrieveSubscriptionId($contextA, [], $operation));
        $this->assertSame($idB, $manager->retrieveSubscriptionId($contextB, [], $operation));

        foreach (['tenant-a', 'tenant-b'] as $tenant) {
            $registrations = $cache->getItem($this->cacheKey('/dummies/1', Dummy::class, $collection, ['tenant' => $tenant]))->get();
            $this->assertCount(1, $registrations);
            $this->assertSame($fields, $registrations[$operation->getShortName().':'.$operation->getName()][0][1]);
            $this->assertCount(2, $registrations[$operation->getShortName().':'.$operation->getName()][0]);
        }

        $cache->clear();
        $this->assertSame($idA, $manager->retrieveSubscriptionId($contextA, [], $operation));
        $this->assertSame($idB, $manager->retrieveSubscriptionId($contextB, [], $operation));
    }

    public static function subscriptionKinds(): iterable
    {
        yield 'item' => [false];
        yield 'collection' => [true];
    }

    #[DataProvider('subscriptionKinds')]
    public function testPrivatePartitionValuesCannotCollideThroughSeparators(bool $collection): void
    {
        $manager = new SubscriptionManager(new SubscriptionStore(new ArrayAdapter(), new ArrayAdapter(), new LockFactory(new InMemoryStore())), new SubscriptionIdentifierGenerator(), $this->normalizeProcessor->reveal(), $this->iriConverterProphecy->reveal());
        $mercure = ['private' => true, 'private_fields' => ['region', 'tenant']];
        $operation = $collection ? $this->createCollectionSubscription($mercure) : $this->createItemSubscription($mercure);
        $info = $this->prophesize(ResolveInfo::class);
        $info->getFieldSelection(\PHP_INT_MAX)->willReturn(['dummy' => ['id' => true]]);
        $context = ['args' => ['input' => ['id' => '/dummies/1']], 'info' => $info->reveal()];

        $idA = $manager->retrieveSubscriptionId($context + ['graphql_context' => ['previous_object' => (object) ['region' => 'eu|tenant=42', 'tenant' => 'x']]], [], $operation);
        $idB = $manager->retrieveSubscriptionId($context + ['graphql_context' => ['previous_object' => (object) ['region' => 'eu', 'tenant' => '42|tenant=x']]], [], $operation);

        $this->assertNotSame($idA, $idB);
    }

    #[DataProvider('distinctSubscriptionScopes')]
    public function testSubscriptionIdsSeparateResourceOperationAndItem(bool $collection, array $changes): void
    {
        $cache = new ArrayAdapter();
        $manager = new SubscriptionManager(new SubscriptionStore($cache, new ArrayAdapter(), new LockFactory(new InMemoryStore())), new SubscriptionIdentifierGenerator(), $this->normalizeProcessor->reveal(), $this->iriConverterProphecy->reveal());
        $info = $this->prophesize(ResolveInfo::class);
        $info->getFieldSelection(\PHP_INT_MAX)->willReturn(['dummy' => ['id' => true]]);
        $base = ['class' => Dummy::class, 'name' => 'update', 'iri' => '/dummies/1', 'collection' => $collection];
        $ids = [];
        foreach ([$base, array_replace($base, $changes)] as $scope) {
            $operationClass = $scope['collection'] ? SubscriptionCollection::class : Subscription::class;
            $operation = new $operationClass(class: $scope['class'], shortName: 'Dummy', name: $scope['name'], mercure: true);
            $context = ['args' => ['input' => ['id' => $scope['iri']]], 'info' => $info->reveal()];
            $id = $manager->retrieveSubscriptionId($context, [], $operation);
            $this->assertSame($id, $manager->retrieveSubscriptionId($context, [], $operation));
            $ids[] = $id;
        }

        $this->assertNotSame($ids[0], $ids[1]);
    }

    public static function distinctSubscriptionScopes(): iterable
    {
        yield 'different items' => [false, ['iri' => '/dummies/2']];
        yield 'different item operations' => [false, ['name' => 'other_update']];
        yield 'different item resources with the same short name' => [false, ['class' => \stdClass::class]];
        yield 'different collection operations' => [true, ['name' => 'other_update']];
        yield 'different collection resources with the same short name' => [true, ['class' => \stdClass::class]];
        yield 'item and collection with the same operation name' => [false, ['collection' => true]];
    }

    public function testCollectionIdentityDoesNotDependOnTheEnrollmentItem(): void
    {
        $manager = new SubscriptionManager(new SubscriptionStore(new ArrayAdapter(), new ArrayAdapter(), new LockFactory(new InMemoryStore())), new SubscriptionIdentifierGenerator(), $this->normalizeProcessor->reveal(), $this->iriConverterProphecy->reveal());
        $operation = $this->createCollectionSubscription(true);
        $info = $this->prophesize(ResolveInfo::class);
        $info->getFieldSelection(\PHP_INT_MAX)->willReturn(['dummy' => ['id' => true]]);

        $this->assertSame(
            $manager->retrieveSubscriptionId(['args' => ['input' => ['id' => '/dummies/1']], 'info' => $info->reveal()], [], $operation),
            $manager->retrieveSubscriptionId(['args' => ['input' => ['id' => '/dummies/2']], 'info' => $info->reveal()], [], $operation)
        );
    }

    public function testRetrieveSubscriptionIdCollectionOperationUsesCollectionRegistrationPath(): void
    {
        $infoProphecy = $this->prophesize(ResolveInfo::class);
        $fields = ['fields' => true];
        $infoProphecy->getFieldSelection(\PHP_INT_MAX)->willReturn($fields);

        $context = ['args' => ['input' => ['id' => '/foos/34']], 'info' => $infoProphecy->reveal(), 'is_collection' => true, 'is_mutation' => false, 'is_subscription' => true];
        $operation = new SubscriptionCollection(name: 'update_collection', shortName: 'Dummy');

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false);
        $subscriptionId = 'collectionSubscriptionId';
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($this->scopedFields($fields + ['__collection' => true]))->willReturn($subscriptionId);
        $cacheItemProphecy->set(['Dummy:update_collection' => [[$subscriptionId, $fields]]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', null, true))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled()->willReturn(true);

        $this->assertSame($subscriptionId, $this->subscriptionManager->retrieveSubscriptionId($context, null, $operation));
    }

    public function testRetrieveSubscriptionIdSharedPrivateCollectionDoesNotPartitionCacheKey(): void
    {
        $infoProphecy = $this->prophesize(ResolveInfo::class);
        $fields = ['fields' => true];
        $infoProphecy->getFieldSelection(\PHP_INT_MAX)->willReturn($fields);

        $context = ['args' => ['input' => ['id' => '/foos/34']], 'info' => $infoProphecy->reveal(), 'is_collection' => true, 'is_mutation' => false, 'is_subscription' => true];
        $operation = $this->createCollectionSubscription(['private' => true]);

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false);
        $subscriptionId = 'sharedPrivateCollectionSubscriptionId';
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($this->scopedFields($fields + ['__collection' => true]))->willReturn($subscriptionId);
        $cacheItemProphecy->set(['Dummy:update_collection' => [[$subscriptionId, $fields]]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', Dummy::class, true))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled()->willReturn(true);

        $this->assertSame($subscriptionId, $this->subscriptionManager->retrieveSubscriptionId($context, null, $operation));
    }

    public function testRetrieveSubscriptionIdPartitionedPrivateCollectionUsesDedicatedCacheKey(): void
    {
        $infoProphecy = $this->prophesize(ResolveInfo::class);
        $fields = ['fields' => true];
        $infoProphecy->getFieldSelection(\PHP_INT_MAX)->willReturn($fields);

        $previousObject = new class {
            public function getTenant(): int
            {
                return 42;
            }
        };

        $context = [
            'args' => ['input' => ['id' => '/foos/34']],
            'info' => $infoProphecy->reveal(),
            'is_collection' => true,
            'is_mutation' => false,
            'is_subscription' => true,
            'graphql_context' => ['previous_object' => $previousObject],
        ];
        $operation = $this->createCollectionSubscription(['private' => true, 'private_fields' => ['tenant']]);

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false);
        $subscriptionId = 'partitionedCollectionSubscriptionId';
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($this->scopedFields($fields + ['__collection' => true]))->willReturn($subscriptionId);
        $cacheItemProphecy->set(['Dummy:update_collection' => [[$subscriptionId, $fields]]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', Dummy::class, true, ['tenant' => '42']))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled()->willReturn(true);

        $this->assertSame($subscriptionId, $this->subscriptionManager->retrieveSubscriptionId($context, null, $operation));
    }

    public function testRetrieveSubscriptionIdCollectionUsesGraphQlOperationKey(): void
    {
        $infoProphecy = $this->prophesize(ResolveInfo::class);
        $fields = ['fields' => true];
        $infoProphecy->getFieldSelection(\PHP_INT_MAX)->willReturn($fields);

        $context = ['args' => ['input' => ['id' => '/foos/34']], 'info' => $infoProphecy->reveal(), 'is_collection' => true, 'is_mutation' => false, 'is_subscription' => true];
        $operation = $this->createCollectionSubscription(true)->withClass(Dummy::class);

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false);
        $subscriptionId = 'subscriptionId';
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($this->scopedFields($fields + ['__collection' => true]))->willReturn($subscriptionId);
        $cacheItemProphecy->set(['Dummy:update_collection' => [[$subscriptionId, $fields]]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', Dummy::class, true))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled()->willReturn(true);

        $this->assertSame($subscriptionId, $this->subscriptionManager->retrieveSubscriptionId($context, null, $operation));
    }

    #[DataProvider('collectionChangeTypes')]
    public function testEachCollectionOperationReceivesItsRegisteredPayload(string $type): void
    {
        $object = new Dummy();
        $first = $this->createCollectionSubscription(true)->withClass(Dummy::class);
        $second = $first->withName('other_collection');
        $operations = [
            'update' => $this->createItemSubscription(true),
            'update_collection' => $first,
            'other_collection' => $second,
        ];
        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');
        $manager = new SubscriptionManager(new SubscriptionStore(new ArrayAdapter(), new ArrayAdapter(), new LockFactory(new InMemoryStore())), $this->subscriptionIdentifierGeneratorProphecy->reveal(), $this->normalizeProcessor->reveal(), $this->iriConverterProphecy->reveal());

        $expected = [];
        foreach (['first' => $first, 'second' => $second] as $name => $operation) {
            // Different selections keep this regression independent of subscription ID scoping.
            $fields = [$name => true];
            $info = $this->prophesize(ResolveInfo::class);
            $info->getFieldSelection(\PHP_INT_MAX)->willReturn($fields);
            $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($this->scopedFields($fields + ['__collection' => true]))->willReturn($name);
            $this->assertSame($name, $manager->retrieveSubscriptionId([
                'args' => ['input' => ['id' => '/dummies/1']],
                'info' => $info->reveal(),
            ], null, $operation));

            $data = [$name => 'changed'];
            if ('delete' === $type) {
                $data = ['type' => 'delete', 'payload' => ['id' => '/dummies/2', 'iri' => 'https://example.com/dummies/2', 'type' => 'Dummy']];
            } else {
                $this->normalizeProcessor->process(
                    $object,
                    Argument::type(Subscription::class),
                    [],
                    ['fields' => $fields]
                )->shouldBeCalledTimes(1)->willReturn($data);
            }
            $expected[] = [$name, $data];
        }

        if ('delete' === $type) {
            $object = (object) [
                'resourceClass' => Dummy::class,
                'id' => '/dummies/2',
                'iri' => 'https://example.com/dummies/2',
                'type' => 'Dummy',
                'private' => [],
            ];
        }

        $this->assertSame($expected, self::publishOperations($manager, $object, $operations, $type));
    }

    public static function collectionChangeTypes(): iterable
    {
        yield 'create' => ['create'];
        yield 'update' => ['update'];
        yield 'delete' => ['delete'];
    }

    public function testUpdatesNoHit(): void
    {
        $object = new Dummy();
        $itemSubscription = $this->createItemSubscription(true);

        $operations = ['update' => $itemSubscription];

        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false);
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/dummies/2', Dummy::class))->willReturn($cacheItemProphecy->reveal());

        $this->assertEquals([], self::publishOperations($this->subscriptionManager, $object, $operations, 'update'));
    }

    public function testUpdatesHit(): void
    {
        $object = new Dummy();
        $itemSubscription = $this->createItemSubscription(true);
        $collectionOperation = $this->createCollectionSubscription(true);

        $operations = [
            'update' => $itemSubscription,
            'update_collection' => $collectionOperation,
        ];

        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(true);
        $cacheItemProphecy->get()->willReturn(['Dummy:update' => [
            ['subscriptionIdFoo', ['fieldsFoo']],
            ['subscriptionIdBar', ['fieldsBar']],
        ]]);
        $cacheItemProphecyCollection = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecyCollection->isHit()->willReturn(true);
        $cacheItemProphecyCollection->get()->willReturn(['Dummy:update_collection' => [
            ['collectionIdFoo', ['fieldsFoo']],
            ['collectionIdBar', ['fieldsBar']],
        ]]);
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/dummies/2', Dummy::class))->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', Dummy::class, true))->shouldBeCalled()->willReturn($cacheItemProphecyCollection->reveal());

        $this->normalizeProcessor->process(
            $object,
            Argument::in([$itemSubscription, $collectionOperation]),
            [],
            ['fields' => ['fieldsFoo']]
        )->willReturn(
            ['newResultFoo', 'clientSubscriptionId' => 'client-subscription-id']
        );

        $this->normalizeProcessor->process(
            $object,
            Argument::in([$itemSubscription, $collectionOperation]),
            [],
            ['fields' => ['fieldsBar']]
        )->willReturn(
            ['resultBar', 'clientSubscriptionId' => 'client-subscription-id']
        );

        $this->assertEquals([['subscriptionIdFoo', ['newResultFoo']], ['subscriptionIdBar', ['resultBar']], ['collectionIdFoo', ['newResultFoo']], ['collectionIdBar', ['resultBar']]], self::publishOperations($this->subscriptionManager, $object, $operations, 'update'));
    }

    public function testAcknowledgementUpdatesCachedItemFingerprint(): void
    {
        $cache = new ArrayAdapter();
        $fingerprints = new ArrayAdapter();
        $manager = new SubscriptionManager(new SubscriptionStore($cache, $fingerprints, new LockFactory(new InMemoryStore())), new SubscriptionIdentifierGenerator(), $this->normalizeProcessor->reveal(), $this->iriConverterProphecy->reveal());
        $operation = $this->createItemSubscription(true);
        $info = $this->prophesize(ResolveInfo::class);
        $info->getFieldSelection(\PHP_INT_MAX)->willReturn(['fieldsFoo']);
        $id = $manager->retrieveSubscriptionId(['args' => ['input' => ['id' => '/dummies/2']], 'info' => $info->reveal()], ['staleResultFoo'], $operation);
        $registryBefore = $cache->getValues();
        $object = new Dummy();
        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');
        $this->normalizeProcessor->process($object, $operation, [], ['fields' => ['fieldsFoo']])->willReturn(['freshResultFoo']);

        $this->assertSame([[$id, ['freshResultFoo']]], self::publish($manager, $object, $operation));
        $this->assertSame([], self::publish($manager, $object, $operation));
        $this->assertSame($registryBefore, $cache->getValues());
        $this->assertCount(1, $fingerprints->getValues());
    }

    public function testUpdatesCreateTargetsCollectionSubscriptionsOnly(): void
    {
        $object = new Dummy();
        $itemSubscription = $this->createItemSubscription(true);
        $collectionOperation = $this->createCollectionSubscription(true);

        $operations = [
            'update' => $itemSubscription,
            'update_collection' => $collectionOperation,
        ];

        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');

        $cacheItemProphecyCollection = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecyCollection->isHit()->willReturn(true);
        $cacheItemProphecyCollection->get()->willReturn(['Dummy:update_collection' => [
            ['collectionSubscriptionId', ['collectionFields']],
        ]]);
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', Dummy::class, true))->shouldBeCalled()->willReturn($cacheItemProphecyCollection->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/dummies/2', Dummy::class))->shouldNotBeCalled();

        $this->normalizeProcessor->process(
            $object,
            $collectionOperation,
            [],
            ['fields' => ['collectionFields']]
        )->willReturn(
            ['createdResult', 'clientSubscriptionId' => 'client-subscription-id']
        );

        $this->assertEquals([['collectionSubscriptionId', ['createdResult']]], self::publishOperations($this->subscriptionManager, $object, $operations, 'create'));
    }

    public function testUpdatesCreateUsesSharedPrivateCollectionCacheKey(): void
    {
        $object = new Dummy();
        $collectionOperation = $this->createCollectionSubscription(['private' => true]);

        $operations = [
            'update_collection' => $collectionOperation,
        ];

        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn(['Dummy:update_collection' => [
            ['sharedPrivateCollectionSubscriptionId', ['collectionFields']],
        ]]);

        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', Dummy::class, true))->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', Dummy::class, true, ['tenant' => '42']))->shouldNotBeCalled();

        $this->normalizeProcessor->process(
            $object,
            $collectionOperation,
            [],
            ['fields' => ['collectionFields']]
        )->willReturn(
            ['sharedPrivateCreatedResult', 'clientSubscriptionId' => 'client-subscription-id']
        );

        $this->assertEquals([['sharedPrivateCollectionSubscriptionId', ['sharedPrivateCreatedResult']]], self::publishOperations($this->subscriptionManager, $object, $operations, 'create'));
    }

    public function testUpdatesCreateUsesPartitionedPrivateCollectionCacheKey(): void
    {
        $object = new class extends Dummy {
            public function getTenant(): int
            {
                return 42;
            }
        };
        $collectionOperation = $this->createCollectionSubscription(['private' => true, 'private_fields' => ['tenant']]);

        $operations = [
            'update_collection' => $collectionOperation,
        ];

        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn(['Dummy:update_collection' => [
            ['partitionedCollectionSubscriptionId', ['collectionFields']],
        ]]);

        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', Dummy::class, true, ['tenant' => '42']))->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());

        $this->normalizeProcessor->process(
            $object,
            $collectionOperation,
            [],
            ['fields' => ['collectionFields']]
        )->willReturn(
            ['partitionedCreatedResult', 'clientSubscriptionId' => 'client-subscription-id']
        );

        $this->assertEquals([['partitionedCollectionSubscriptionId', ['partitionedCreatedResult']]], self::publishOperations($this->subscriptionManager, $object, $operations, 'create'));
    }

    public function testUpdatesUpdatePublishesCollectionSubscriptionWithoutItemSubscription(): void
    {
        $object = new Dummy();
        $itemSubscription = $this->createItemSubscription(true);
        $collectionOperation = $this->createCollectionSubscription(true);

        $operations = [
            'update' => $itemSubscription,
            'update_collection' => $collectionOperation,
        ];

        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');

        $itemCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $itemCacheItemProphecy->isHit()->willReturn(false);

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn(['Dummy:update_collection' => [
            ['collectionSubscriptionId', ['collectionFields']],
        ]]);

        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/dummies/2', Dummy::class))->shouldBeCalled()->willReturn($itemCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', Dummy::class, true))->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());

        $this->normalizeProcessor->process(
            $object,
            $collectionOperation,
            [],
            ['fields' => ['collectionFields']]
        )->willReturn(
            ['updatedCollectionResult', 'clientSubscriptionId' => 'client-subscription-id']
        );

        $this->assertEquals([['collectionSubscriptionId', ['updatedCollectionResult']]], self::publishOperations($this->subscriptionManager, $object, $operations, 'update'));
    }

    public function testUpdatesUpdateUsesSharedPrivateCollectionAndItemCacheKeys(): void
    {
        $object = new Dummy();
        $itemSubscription = $this->createItemSubscription(['private' => true]);
        $collectionOperation = $this->createCollectionSubscription(['private' => true]);

        $operations = [
            'update' => $itemSubscription,
            'update_collection' => $collectionOperation,
        ];

        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');

        $itemCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $itemCacheItemProphecy->isHit()->willReturn(false);

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn(['Dummy:update_collection' => [
            ['sharedPrivateCollectionSubscriptionId', ['collectionFields']],
        ]]);

        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/dummies/2', Dummy::class))->shouldBeCalled()->willReturn($itemCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', Dummy::class, true))->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());

        $this->normalizeProcessor->process(
            $object,
            $collectionOperation,
            [],
            ['fields' => ['collectionFields']]
        )->willReturn(
            ['sharedPrivateUpdatedResult', 'clientSubscriptionId' => 'client-subscription-id']
        );

        $this->assertEquals([['sharedPrivateCollectionSubscriptionId', ['sharedPrivateUpdatedResult']]], self::publishOperations($this->subscriptionManager, $object, $operations, 'update'));
    }

    public function testUpdatesUpdateUsesPartitionedPrivateCollectionAndItemCacheKeys(): void
    {
        $object = new class extends Dummy {
            public function getTenant(): int
            {
                return 42;
            }
        };
        $itemSubscription = $this->createItemSubscription(['private' => true, 'private_fields' => ['tenant']]);
        $collectionOperation = $this->createCollectionSubscription(['private' => true, 'private_fields' => ['tenant']]);

        $operations = [
            'update' => $itemSubscription,
            'update_collection' => $collectionOperation,
        ];

        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');

        $itemCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $itemCacheItemProphecy->isHit()->willReturn(false);

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn(['Dummy:update_collection' => [
            ['partitionedCollectionSubscriptionId', ['collectionFields']],
        ]]);

        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/dummies/2', Dummy::class, privateFieldData: ['tenant' => '42']))->shouldBeCalled()->willReturn($itemCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', Dummy::class, true, ['tenant' => '42']))->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());

        $this->normalizeProcessor->process(
            $object,
            $collectionOperation,
            [],
            ['fields' => ['collectionFields']]
        )->willReturn(
            ['partitionedUpdatedResult', 'clientSubscriptionId' => 'client-subscription-id']
        );

        $this->assertEquals([['partitionedCollectionSubscriptionId', ['partitionedUpdatedResult']]], self::publishOperations($this->subscriptionManager, $object, $operations, 'update'));
    }

    public function testUpdatesDeleteReturnsLightweightPayloadAndRemovesItemCache(): void
    {
        $object = new class {
            public string $resourceClass = Dummy::class;
            public string $id = '/dummies/2';
            public string $iri = '/dummies/2';
            public string $type = 'Dummy';
            public array $private = [];
        };

        $operations = [
            'update' => $this->createItemSubscription(true),
            'update_collection' => $this->createCollectionSubscription(true),
        ];

        $itemCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $itemCacheItemProphecy->isHit()->willReturn(true);
        $itemCacheItemProphecy->get()->willReturn(['Dummy:update' => [
            ['itemSubscriptionId', ['itemFields']],
        ]]);

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn(['Dummy:update_collection' => [
            ['collectionSubscriptionId', ['collectionFields']],
        ]]);

        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/dummies/2', Dummy::class))->shouldBeCalled()->willReturn($itemCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', Dummy::class, true))->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->deleteItem($this->cacheKey('/dummies/2', Dummy::class))->shouldBeCalled()->willReturn(true);

        $payload = ['type' => 'delete', 'payload' => ['id' => '/dummies/2', 'iri' => '/dummies/2', 'type' => 'Dummy']];

        $this->assertEquals([
            ['itemSubscriptionId', $payload],
            ['collectionSubscriptionId', $payload],
        ], self::publishOperations($this->subscriptionManager, $object, $operations, 'delete'));
    }

    public function testUpdatesDeleteReturnsPartitionedPrivatePayloadsAndRemovesPartitionedItemCache(): void
    {
        $object = new class {
            public string $resourceClass = Dummy::class;
            public string $id = '/dummies/2';
            public string $iri = '/dummies/2';
            public string $type = 'Dummy';
            public array $private = ['tenant' => '42'];
        };

        $operations = [
            'update' => $this->createItemSubscription(['private' => true, 'private_fields' => ['tenant']]),
            'update_collection' => $this->createCollectionSubscription(['private' => true, 'private_fields' => ['tenant']]),
        ];

        $itemCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $itemCacheItemProphecy->isHit()->willReturn(true);
        $itemCacheItemProphecy->get()->willReturn(['Dummy:update' => [
            ['partitionedItemSubscriptionId', ['itemFields']],
        ]]);

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn(['Dummy:update_collection' => [
            ['partitionedCollectionSubscriptionId', ['collectionFields']],
        ]]);

        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/dummies/2', Dummy::class, privateFieldData: ['tenant' => '42']))->shouldBeCalled()->willReturn($itemCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', Dummy::class, true, ['tenant' => '42']))->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->deleteItem($this->cacheKey('/dummies/2', Dummy::class, privateFieldData: ['tenant' => '42']))->shouldBeCalled()->willReturn(true);

        $payload = ['type' => 'delete', 'payload' => ['id' => '/dummies/2', 'iri' => '/dummies/2', 'type' => 'Dummy']];

        $this->assertEquals([
            ['partitionedItemSubscriptionId', $payload],
            ['partitionedCollectionSubscriptionId', $payload],
        ], self::publishOperations($this->subscriptionManager, $object, $operations, 'delete'));
    }

    public function testUpdatesDeleteUsesMetadataBasedCollectionSubscriptionKey(): void
    {
        $object = new class {
            public string $resourceClass = Dummy::class;
            public string $id = '/dummies/2';
            public string $iri = '/dummies/2';
            public string $type = 'Dummy';
            public array $private = [];
        };
        $collectionOperation = $this->createCollectionSubscription(true);

        $operations = [
            'update' => $this->createItemSubscription(true),
            'update_collection' => $collectionOperation,
        ];

        $itemCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $itemCacheItemProphecy->isHit()->willReturn(true);
        $itemCacheItemProphecy->get()->willReturn(['Dummy:update' => [
            ['itemSubscriptionId', ['itemFields']],
        ]]);

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn(['Dummy:update_collection' => [
            ['collectionSubscriptionId', ['collectionFields']],
        ]]);

        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/dummies/2', Dummy::class))->shouldBeCalled()->willReturn($itemCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', Dummy::class, true))->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->deleteItem($this->cacheKey('/dummies/2', Dummy::class))->shouldBeCalled()->willReturn(true);

        $payload = ['type' => 'delete', 'payload' => ['id' => '/dummies/2', 'iri' => '/dummies/2', 'type' => 'Dummy']];

        $this->assertEquals([
            ['itemSubscriptionId', $payload],
            ['collectionSubscriptionId', $payload],
        ], self::publishOperations($this->subscriptionManager, $object, $operations, 'delete'));
    }
}
