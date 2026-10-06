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
use ApiPlatform\GraphQl\Tests\Fixtures\ApiResource\Dummy;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\GraphQl\SubscriptionCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
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
use Symfony\Component\PropertyAccess\Exception\AccessException;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SubscriptionManagerTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $subscriptionsCacheProphecy;
    private ObjectProphecy $subscriptionIdentifierGeneratorProphecy;
    private ObjectProphecy $normalizeProcessor;
    private ObjectProphecy $iriConverterProphecy;
    private SubscriptionManager $subscriptionManager;
    private ObjectProphecy $resourceMetadataCollectionFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->subscriptionsCacheProphecy = $this->prophesize(CacheItemPoolInterface::class);
        $this->subscriptionIdentifierGeneratorProphecy = $this->prophesize(SubscriptionIdentifierGeneratorInterface::class);
        $this->normalizeProcessor = $this->prophesize(ProcessorInterface::class);
        $this->iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $this->resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $this->subscriptionManager = new SubscriptionManager($this->subscriptionsCacheProphecy->reveal(), $this->subscriptionIdentifierGeneratorProphecy->reveal(), $this->normalizeProcessor->reveal(), $this->iriConverterProphecy->reveal(), $this->resourceMetadataCollectionFactory->reveal());
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

    private function cacheKey(string $iri, ?string $operation = null, ?string $resource = null, bool $collection = false): string
    {
        return 'graphql_subscription_'.hash('sha256', serialize([
            'resource' => $resource,
            'operation' => $operation,
            'collection' => $collection,
            'iri' => $collection ? null : $iri,
        ]));
    }

    private function scopedFields(array $fields): TokenInterface
    {
        return Argument::that(static function (array $identity) use ($fields): bool {
            if (!isset($identity['__subscription_scope']) || !\is_string($identity['__subscription_scope'])) {
                return false;
            }
            unset($identity['__subscription_scope']);

            return $fields === $identity;
        });
    }

    public function testRetrieveSubscriptionIdNoIdentifier(): void
    {
        $info = $this->prophesize(ResolveInfo::class);
        $info->getFieldSelection(\PHP_INT_MAX)->willReturn([]);

        $context = ['args' => [], 'info' => $info->reveal(), 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true];

        $this->assertNull($this->subscriptionManager->retrieveSubscriptionId($context, null));
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
        $cacheItemProphecy->set([[$subscriptionId, $fields, ['result']]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/foos/34'))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled();

        $this->assertSame($subscriptionId, $this->subscriptionManager->retrieveSubscriptionId($context, $result));
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
            ['subscriptionIdFoo', ['fieldsFoo'], ['resultFoo']],
            ['subscriptionIdBar', ['fieldsBar'], ['resultBar']],
        ];
        $cacheItemProphecy->get()->willReturn($cachedSubscriptions);
        $subscriptionId = 'subscriptionId';
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($this->scopedFields($fields))->willReturn($subscriptionId);
        $cacheItemProphecy->set(array_merge($cachedSubscriptions, [[$subscriptionId, $fields, ['result']]]))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/foos/34'))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled();

        $this->assertSame($subscriptionId, $this->subscriptionManager->retrieveSubscriptionId($context, $result));
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
        $cacheItemProphecy->get()->willReturn([
            ['subscriptionIdFoo', ['fieldsFoo'], ['resultFoo']],
            ['subscriptionIdBar', ['fieldsBar'], ['resultBar']],
        ]);
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($this->scopedFields($fields))->shouldNotBeCalled();
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/foos/34'))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());

        $this->assertSame('subscriptionIdBar', $this->subscriptionManager->retrieveSubscriptionId($context, $result));
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
        $cacheItemProphecy->get()->willReturn([
            ['subscriptionIdFoo', [
                'first' => true,
                'second' => [
                    'first' => true,
                    'second' => true,
                    'third' => true,
                ],
                'third' => true,
            ], ['resultFoo']],
            ['subscriptionIdBar', ['fieldsBar'], ['resultBar']],
        ]);
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($this->scopedFields($fields))->shouldNotBeCalled();
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/foos/34'))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());

        $this->assertSame('subscriptionIdFoo', $this->subscriptionManager->retrieveSubscriptionId($context, $result));
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
        $cacheItemProphecy->set([[$subscriptionId, $fields, ['result']]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/foos/34', 'update_subscription').'_'.hash('sha256', serialize(['tenant' => '42'])))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled();

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
        $cacheItemProphecy->set([[$subscriptionId, $fields, ['result']]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/foos/34', 'update_subscription').'_'.hash('sha256', serialize(['tenant' => '42'])))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled();

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
        $cacheItemProphecy->set([[$subscriptionId, $fields, ['result']]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/foos/34', 'update_subscription'))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled();

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
        $cacheItemProphecy->set([[$subscriptionId, $fields, ['result']]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/foos/34', 'update_subscription').'_'.hash('sha256', serialize(['region' => 'eu', 'tenant' => '42'])))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled();

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
        $this->resourceMetadataCollectionFactory->create(\stdClass::class)->willReturn(new ResourceMetadataCollection(\stdClass::class, [
            (new ApiResource())->withOperations(new Operations([new Get(shortName: 'Dummy')]))->withGraphQlOperations([
                $this->createCollectionSubscription(['private' => true, 'private_fields' => ['tenant', 'chat']]),
            ]),
        ]));
        $this->subscriptionsCacheProphecy->getItem(Argument::any())->shouldNotBeCalled();
        $this->expectException(AccessException::class);

        $this->subscriptionManager->getPushPayloads($object, $type);
    }

    #[DataProvider('missingDeleteFields')]
    public function testDeleteWithIncompletePrivateFieldsCannotReadOrRemoveSubscriptionBuckets(array $private): void
    {
        $this->resourceMetadataCollectionFactory->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withGraphQlOperations([
                $this->createItemSubscription(['private' => true, 'private_fields' => ['tenant', 'chat']]),
            ]),
        ]));
        $this->subscriptionsCacheProphecy->getItem(Argument::any())->shouldNotBeCalled();
        $this->subscriptionsCacheProphecy->deleteItem(Argument::any())->shouldNotBeCalled();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('missing from the delete snapshot');

        $this->subscriptionManager->getPushPayloads((object) [
            'resourceClass' => Dummy::class,
            'id' => '/dummies/1',
            'iri' => 'http://example.com/dummies/1',
            'type' => 'Dummy',
            'private' => $private,
        ], 'delete');
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
        $manager = new SubscriptionManager($cache, new SubscriptionIdentifierGenerator(), $this->normalizeProcessor->reveal(), $this->iriConverterProphecy->reveal(), $this->resourceMetadataCollectionFactory->reveal());
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

        $cacheKey = $collection ? $this->cacheKey('', 'update_collection', Dummy::class, true) : $this->cacheKey('/dummies/1', 'update', Dummy::class);
        foreach (['tenant-a', 'tenant-b'] as $tenant) {
            $registrations = $cache->getItem($cacheKey.'_'.hash('sha256', serialize(['tenant' => $tenant])))->get();
            $this->assertCount(1, $registrations);
            $this->assertSame($fields, $registrations[0][1]);
            $this->assertSame([], $registrations[0][2]);
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
        $manager = new SubscriptionManager(new ArrayAdapter(), new SubscriptionIdentifierGenerator(), $this->normalizeProcessor->reveal(), $this->iriConverterProphecy->reveal(), $this->resourceMetadataCollectionFactory->reveal());
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
        $manager = new SubscriptionManager($cache, new SubscriptionIdentifierGenerator(), $this->normalizeProcessor->reveal(), $this->iriConverterProphecy->reveal(), $this->resourceMetadataCollectionFactory->reveal());
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
        $manager = new SubscriptionManager(new ArrayAdapter(), new SubscriptionIdentifierGenerator(), $this->normalizeProcessor->reveal(), $this->iriConverterProphecy->reveal(), $this->resourceMetadataCollectionFactory->reveal());
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
        $cacheItemProphecy->set([[$subscriptionId, $fields, []]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', 'update_collection', null, true))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled();

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
        $cacheItemProphecy->set([[$subscriptionId, $fields, []]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', 'update_collection', Dummy::class, true))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled();

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
        $cacheItemProphecy->set([[$subscriptionId, $fields, []]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', 'update_collection', Dummy::class, true).'_'.hash('sha256', serialize(['tenant' => '42'])))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled();

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
        $cacheItemProphecy->set([[$subscriptionId, $fields, []]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', 'update_collection', Dummy::class, true))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled();

        $this->assertSame($subscriptionId, $this->subscriptionManager->retrieveSubscriptionId($context, null, $operation));
    }

    #[DataProvider('collectionChangeTypes')]
    public function testEachCollectionOperationReceivesItsRegisteredPayload(string $type): void
    {
        $object = new Dummy();
        $first = $this->createCollectionSubscription(true)->withClass(Dummy::class);
        $second = $first->withName('other_collection');
        $this->resourceMetadataCollectionFactory->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())
                ->withOperations(new Operations([(new Get())->withShortName('Dummy')]))
                ->withGraphQlOperations([
                    'collection_query' => new QueryCollection(name: 'collection_query', shortName: 'Dummy', class: Dummy::class),
                    'update' => $this->createItemSubscription(true),
                    'update_collection' => $first,
                ]),
            (new ApiResource())->withGraphQlOperations(['other_collection' => $second]),
        ]));
        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');
        $manager = new SubscriptionManager(new ArrayAdapter(), $this->subscriptionIdentifierGeneratorProphecy->reveal(), $this->normalizeProcessor->reveal(), $this->iriConverterProphecy->reveal(), $this->resourceMetadataCollectionFactory->reveal());

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

        $this->assertSame($expected, $manager->getPushPayloads($object, $type));
    }

    public static function collectionChangeTypes(): iterable
    {
        yield 'create' => ['create'];
        yield 'update' => ['update'];
        yield 'delete' => ['delete'];
    }

    public function testGetPushPayloadsNoHit(): void
    {
        $object = new Dummy();
        $itemSubscription = $this->createItemSubscription(true);

        $this->resourceMetadataCollectionFactory->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())
                ->withOperations(new Operations([(new Get())->withShortName('Dummy')]))
                ->withGraphQlOperations(['update' => $itemSubscription]),
        ]));

        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false);
        $cacheItemProphecy->isHit()->willReturn(false);
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/dummies/2', 'update', Dummy::class))->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem('_dummies')->willReturn($cacheItemProphecy->reveal());

        $this->assertEquals([], $this->subscriptionManager->getPushPayloads($object, 'update'));
    }

    public function testGetPushPayloadsHit(): void
    {
        $object = new Dummy();
        $itemSubscription = $this->createItemSubscription(true);
        $collectionOperation = $this->createCollectionSubscription(true);

        $this->resourceMetadataCollectionFactory->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())
                ->withOperations(new Operations([(new Get())->withShortName('Dummy')]))
                ->withGraphQlOperations([
                    'update' => $itemSubscription,
                    'update_collection' => $collectionOperation,
                ]),
        ]));

        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(true);
        $cacheItemProphecy->get()->willReturn([
            ['subscriptionIdFoo', ['fieldsFoo'], ['resultFoo']],
            ['subscriptionIdBar', ['fieldsBar'], ['resultBar']],
        ]);
        $cacheItemProphecy->set([
            ['subscriptionIdFoo', ['fieldsFoo'], ['newResultFoo']],
            ['subscriptionIdBar', ['fieldsBar'], ['resultBar']],
        ])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $cacheItemProphecyCollection = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecyCollection->isHit()->willReturn(true);
        $cacheItemProphecyCollection->get()->willReturn([
            ['subscriptionIdFoo', ['fieldsFoo'], []],
            ['subscriptionIdBar', ['fieldsBar'], []],
        ]);
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/dummies/2', 'update', Dummy::class))->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', 'update_collection', Dummy::class, true))->shouldBeCalled()->willReturn($cacheItemProphecyCollection->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled();

        $this->normalizeProcessor->process(
            $object,
            (new Subscription())->withName('mercure_subscription')->withShortName('Dummy'),
            [],
            ['fields' => ['fieldsFoo']]
        )->willReturn(
            ['newResultFoo', 'clientSubscriptionId' => 'client-subscription-id']
        );

        $this->normalizeProcessor->process(
            $object,
            (new Subscription())->withName('mercure_subscription')->withShortName('Dummy'),
            [],
            ['fields' => ['fieldsBar']]
        )->willReturn(
            ['resultBar', 'clientSubscriptionId' => 'client-subscription-id']
        );

        $this->assertEquals([['subscriptionIdFoo', ['newResultFoo']], ['subscriptionIdBar', ['resultBar']]], $this->subscriptionManager->getPushPayloads($object, 'update'));
    }

    public function testGetPushPayloadsUpdatesCachedItemSnapshotAfterPublishing(): void
    {
        $object = new Dummy();
        $itemSubscription = $this->createItemSubscription(true);
        $collectionOperation = $this->createCollectionSubscription(true);

        $this->resourceMetadataCollectionFactory->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())
                ->withOperations(new Operations([(new Get())->withShortName('Dummy')]))
                ->withGraphQlOperations([
                    'update' => $itemSubscription,
                    'update_collection' => $collectionOperation,
                ]),
        ]));

        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');

        $itemCacheItemFirstCallProphecy = $this->prophesize(CacheItemInterface::class);
        $itemCacheItemFirstCallProphecy->isHit()->willReturn(true);
        $itemCacheItemFirstCallProphecy->get()->willReturn([
            ['subscriptionIdFoo', ['fieldsFoo'], ['staleResultFoo']],
        ]);
        $itemCacheItemFirstCallProphecy->set([
            ['subscriptionIdFoo', ['fieldsFoo'], ['freshResultFoo']],
        ])->shouldBeCalled()->willReturn($itemCacheItemFirstCallProphecy->reveal());

        $itemCacheItemSecondCallProphecy = $this->prophesize(CacheItemInterface::class);
        $itemCacheItemSecondCallProphecy->isHit()->willReturn(true);
        $itemCacheItemSecondCallProphecy->get()->willReturn([
            ['subscriptionIdFoo', ['fieldsFoo'], ['freshResultFoo']],
        ]);
        $itemCacheItemSecondCallProphecy->set(Argument::any())->shouldNotBeCalled();

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(false);

        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', 'update_collection', Dummy::class, true))->willReturn($collectionCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/dummies/2', 'update', Dummy::class))->willReturn(
            $itemCacheItemFirstCallProphecy->reveal(),
            $itemCacheItemSecondCallProphecy->reveal()
        );
        $this->subscriptionsCacheProphecy->save($itemCacheItemFirstCallProphecy->reveal())->shouldBeCalledTimes(1);

        $this->normalizeProcessor->process(
            $object,
            (new Subscription())->withName('mercure_subscription')->withShortName('Dummy'),
            [],
            ['fields' => ['fieldsFoo']]
        )->willReturn(
            ['freshResultFoo', 'clientSubscriptionId' => 'client-subscription-id'],
            ['freshResultFoo', 'clientSubscriptionId' => 'client-subscription-id']
        );

        $this->assertEquals([['subscriptionIdFoo', ['freshResultFoo']]], $this->subscriptionManager->getPushPayloads($object, 'update'));
        $this->assertEquals([], $this->subscriptionManager->getPushPayloads($object, 'update'));
    }

    public function testGetPushPayloadsCreateTargetsCollectionSubscriptionsOnly(): void
    {
        $object = new Dummy();
        $itemSubscription = $this->createItemSubscription(true);
        $collectionOperation = $this->createCollectionSubscription(true);

        $this->resourceMetadataCollectionFactory->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())
                ->withOperations(new Operations([(new Get())->withShortName('Dummy')]))
                ->withGraphQlOperations([
                    'update' => $itemSubscription,
                    'update_collection' => $collectionOperation,
                ]),
        ]));

        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');

        $cacheItemProphecyCollection = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecyCollection->isHit()->willReturn(true);
        $cacheItemProphecyCollection->get()->willReturn([
            ['collectionSubscriptionId', ['collectionFields'], []],
        ]);
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', 'update_collection', Dummy::class, true))->shouldBeCalled()->willReturn($cacheItemProphecyCollection->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/dummies/2', 'update', Dummy::class))->shouldNotBeCalled();

        $this->normalizeProcessor->process(
            $object,
            (new Subscription())->withName('mercure_subscription')->withShortName('Dummy'),
            [],
            ['fields' => ['collectionFields']]
        )->willReturn(
            ['createdResult', 'clientSubscriptionId' => 'client-subscription-id']
        );

        $this->assertEquals([['collectionSubscriptionId', ['createdResult']]], $this->subscriptionManager->getPushPayloads($object, 'create'));
    }

    public function testGetPushPayloadsCreateUsesSharedPrivateCollectionCacheKey(): void
    {
        $object = new Dummy();
        $collectionOperation = $this->createCollectionSubscription(['private' => true]);

        $this->resourceMetadataCollectionFactory->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())
                ->withOperations(new Operations([
                    (new Get())->withShortName('Dummy')->withMercure(['private' => true]),
                ]))
                ->withGraphQlOperations([
                    'update_collection' => $collectionOperation,
                ]),
        ]));

        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn([
            ['sharedPrivateCollectionSubscriptionId', ['collectionFields'], []],
        ]);

        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', 'update_collection', Dummy::class, true))->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem(Argument::containingString(hash('sha256', 'tenant=')))->shouldNotBeCalled();

        $this->normalizeProcessor->process(
            $object,
            (new Subscription())->withName('mercure_subscription')->withShortName('Dummy'),
            [],
            ['fields' => ['collectionFields']]
        )->willReturn(
            ['sharedPrivateCreatedResult', 'clientSubscriptionId' => 'client-subscription-id']
        );

        $this->assertEquals([['sharedPrivateCollectionSubscriptionId', ['sharedPrivateCreatedResult']]], $this->subscriptionManager->getPushPayloads($object, 'create'));
    }

    public function testGetPushPayloadsCreateUsesPartitionedPrivateCollectionCacheKey(): void
    {
        $object = new class extends Dummy {
            public function getTenant(): int
            {
                return 42;
            }
        };
        $collectionOperation = $this->createCollectionSubscription(['private' => true, 'private_fields' => ['tenant']]);
        $partitionKey = hash('sha256', serialize(['tenant' => '42']));

        $this->resourceMetadataCollectionFactory->create($object::class)->willReturn(new ResourceMetadataCollection($object::class, [
            (new ApiResource())
                ->withOperations(new Operations([
                    (new Get())->withShortName('Dummy')->withMercure(['private' => true, 'private_fields' => ['tenant']]),
                ]))
                ->withGraphQlOperations([
                    'update_collection' => $collectionOperation,
                ]),
        ]));

        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn([
            ['partitionedCollectionSubscriptionId', ['collectionFields'], []],
        ]);

        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', 'update_collection', Dummy::class, true).'_'.$partitionKey)->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());

        $this->normalizeProcessor->process(
            $object,
            (new Subscription())->withName('mercure_subscription')->withShortName('Dummy'),
            [],
            ['fields' => ['collectionFields']]
        )->willReturn(
            ['partitionedCreatedResult', 'clientSubscriptionId' => 'client-subscription-id']
        );

        $this->assertEquals([['partitionedCollectionSubscriptionId', ['partitionedCreatedResult']]], $this->subscriptionManager->getPushPayloads($object, 'create'));
    }

    public function testGetPushPayloadsUpdatePublishesCollectionSubscriptionWithoutItemSubscription(): void
    {
        $object = new Dummy();
        $itemSubscription = $this->createItemSubscription(true);
        $collectionOperation = $this->createCollectionSubscription(true);

        $this->resourceMetadataCollectionFactory->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())
                ->withOperations(new Operations([(new Get())->withShortName('Dummy')]))
                ->withGraphQlOperations([
                    'update' => $itemSubscription,
                    'update_collection' => $collectionOperation,
                ]),
        ]));

        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');

        $itemCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $itemCacheItemProphecy->isHit()->willReturn(false);

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn([
            ['collectionSubscriptionId', ['collectionFields'], []],
        ]);

        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/dummies/2', 'update', Dummy::class))->shouldBeCalled()->willReturn($itemCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', 'update_collection', Dummy::class, true))->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());

        $this->normalizeProcessor->process(
            $object,
            (new Subscription())->withName('mercure_subscription')->withShortName('Dummy'),
            [],
            ['fields' => ['collectionFields']]
        )->willReturn(
            ['updatedCollectionResult', 'clientSubscriptionId' => 'client-subscription-id']
        );

        $this->assertEquals([['collectionSubscriptionId', ['updatedCollectionResult']]], $this->subscriptionManager->getPushPayloads($object, 'update'));
    }

    public function testGetPushPayloadsUpdateUsesSharedPrivateCollectionAndItemCacheKeys(): void
    {
        $object = new Dummy();
        $itemSubscription = $this->createItemSubscription(['private' => true]);
        $collectionOperation = $this->createCollectionSubscription(['private' => true]);

        $this->resourceMetadataCollectionFactory->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())
                ->withOperations(new Operations([
                    (new Get())->withShortName('Dummy')->withMercure(['private' => true]),
                ]))
                ->withGraphQlOperations([
                    'update' => $itemSubscription,
                    'update_collection' => $collectionOperation,
                ]),
        ]));

        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');

        $itemCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $itemCacheItemProphecy->isHit()->willReturn(false);

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn([
            ['sharedPrivateCollectionSubscriptionId', ['collectionFields'], []],
        ]);

        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/dummies/2', 'update', Dummy::class))->shouldBeCalled()->willReturn($itemCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', 'update_collection', Dummy::class, true))->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());

        $this->normalizeProcessor->process(
            $object,
            (new Subscription())->withName('mercure_subscription')->withShortName('Dummy'),
            [],
            ['fields' => ['collectionFields']]
        )->willReturn(
            ['sharedPrivateUpdatedResult', 'clientSubscriptionId' => 'client-subscription-id']
        );

        $this->assertEquals([['sharedPrivateCollectionSubscriptionId', ['sharedPrivateUpdatedResult']]], $this->subscriptionManager->getPushPayloads($object, 'update'));
    }

    public function testGetPushPayloadsUpdateUsesPartitionedPrivateCollectionAndItemCacheKeys(): void
    {
        $object = new class extends Dummy {
            public function getTenant(): int
            {
                return 42;
            }
        };
        $itemSubscription = $this->createItemSubscription(['private' => true, 'private_fields' => ['tenant']]);
        $collectionOperation = $this->createCollectionSubscription(['private' => true, 'private_fields' => ['tenant']]);
        $partitionKey = hash('sha256', serialize(['tenant' => '42']));

        $this->resourceMetadataCollectionFactory->create($object::class)->willReturn(new ResourceMetadataCollection($object::class, [
            (new ApiResource())
                ->withOperations(new Operations([
                    (new Get())->withShortName('Dummy')->withMercure(['private' => true, 'private_fields' => ['tenant']]),
                ]))
                ->withGraphQlOperations([
                    'update' => $itemSubscription,
                    'update_collection' => $collectionOperation,
                ]),
        ]));

        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');

        $itemCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $itemCacheItemProphecy->isHit()->willReturn(false);

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn([
            ['partitionedCollectionSubscriptionId', ['collectionFields'], []],
        ]);

        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/dummies/2', 'update', Dummy::class).'_'.$partitionKey)->shouldBeCalled()->willReturn($itemCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', 'update_collection', Dummy::class, true).'_'.$partitionKey)->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());

        $this->normalizeProcessor->process(
            $object,
            (new Subscription())->withName('mercure_subscription')->withShortName('Dummy'),
            [],
            ['fields' => ['collectionFields']]
        )->willReturn(
            ['partitionedUpdatedResult', 'clientSubscriptionId' => 'client-subscription-id']
        );

        $this->assertEquals([['partitionedCollectionSubscriptionId', ['partitionedUpdatedResult']]], $this->subscriptionManager->getPushPayloads($object, 'update'));
    }

    public function testGetPushPayloadsDeleteReturnsLightweightPayloadAndRemovesItemCache(): void
    {
        $object = new class {
            public string $resourceClass = Dummy::class;
            public string $id = '/dummies/2';
            public string $iri = '/dummies/2';
            public string $type = 'Dummy';
            public array $private = [];
        };

        $this->resourceMetadataCollectionFactory->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withGraphQlOperations([
                'update' => $this->createItemSubscription(true),
                'update_collection' => $this->createCollectionSubscription(true),
            ]),
        ]));

        $itemCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $itemCacheItemProphecy->isHit()->willReturn(true);
        $itemCacheItemProphecy->get()->willReturn([
            ['itemSubscriptionId', ['itemFields'], ['result']],
        ]);

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn([
            ['collectionSubscriptionId', ['collectionFields'], []],
        ]);

        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/dummies/2', 'update', Dummy::class))->shouldBeCalled()->willReturn($itemCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', 'update_collection', Dummy::class, true))->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->hasItem($this->cacheKey('/dummies/2', 'update', Dummy::class))->shouldBeCalled()->willReturn(true);
        $this->subscriptionsCacheProphecy->deleteItem($this->cacheKey('/dummies/2', 'update', Dummy::class))->shouldBeCalled();

        $payload = ['type' => 'delete', 'payload' => ['id' => '/dummies/2', 'iri' => '/dummies/2', 'type' => 'Dummy']];

        $this->assertEquals([
            ['itemSubscriptionId', $payload],
            ['collectionSubscriptionId', $payload],
        ], $this->subscriptionManager->getPushPayloads($object, 'delete'));
    }

    public function testGetPushPayloadsDeleteReturnsPartitionedPrivatePayloadsAndRemovesPartitionedItemCache(): void
    {
        $object = new class {
            public string $resourceClass = Dummy::class;
            public string $id = '/dummies/2';
            public string $iri = '/dummies/2';
            public string $type = 'Dummy';
            public array $private = ['tenant' => '42'];
        };

        $this->resourceMetadataCollectionFactory->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withGraphQlOperations([
                'update' => $this->createItemSubscription(['private' => true, 'private_fields' => ['tenant']]),
                'update_collection' => $this->createCollectionSubscription(['private' => true, 'private_fields' => ['tenant']]),
            ]),
        ]));

        $partitionKey = hash('sha256', serialize(['tenant' => '42']));

        $itemCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $itemCacheItemProphecy->isHit()->willReturn(true);
        $itemCacheItemProphecy->get()->willReturn([
            ['partitionedItemSubscriptionId', ['itemFields'], ['result']],
        ]);

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn([
            ['partitionedCollectionSubscriptionId', ['collectionFields'], []],
        ]);

        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/dummies/2', 'update', Dummy::class).'_'.$partitionKey)->shouldBeCalled()->willReturn($itemCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', 'update_collection', Dummy::class, true).'_'.$partitionKey)->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->hasItem($this->cacheKey('/dummies/2', 'update', Dummy::class).'_'.$partitionKey)->shouldBeCalled()->willReturn(true);
        $this->subscriptionsCacheProphecy->deleteItem($this->cacheKey('/dummies/2', 'update', Dummy::class).'_'.$partitionKey)->shouldBeCalled();

        $payload = ['type' => 'delete', 'payload' => ['id' => '/dummies/2', 'iri' => '/dummies/2', 'type' => 'Dummy']];

        $this->assertEquals([
            ['partitionedItemSubscriptionId', $payload],
            ['partitionedCollectionSubscriptionId', $payload],
        ], $this->subscriptionManager->getPushPayloads($object, 'delete'));
    }

    public function testGetPushPayloadsDeleteUsesMetadataBasedCollectionSubscriptionKey(): void
    {
        $object = new class {
            public string $resourceClass = Dummy::class;
            public string $id = '/dummies/2';
            public string $iri = '/dummies/2';
            public string $type = 'Dummy';
            public array $private = [];
        };
        $collectionOperation = $this->createCollectionSubscription(true);

        $this->resourceMetadataCollectionFactory->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withGraphQlOperations([
                'update' => $this->createItemSubscription(true),
                'update_collection' => $collectionOperation,
            ]),
        ]));

        $itemCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $itemCacheItemProphecy->isHit()->willReturn(true);
        $itemCacheItemProphecy->get()->willReturn([
            ['itemSubscriptionId', ['itemFields'], ['result']],
        ]);

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn([
            ['collectionSubscriptionId', ['collectionFields'], []],
        ]);

        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('/dummies/2', 'update', Dummy::class))->shouldBeCalled()->willReturn($itemCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem($this->cacheKey('', 'update_collection', Dummy::class, true))->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->hasItem($this->cacheKey('/dummies/2', 'update', Dummy::class))->shouldBeCalled()->willReturn(true);
        $this->subscriptionsCacheProphecy->deleteItem($this->cacheKey('/dummies/2', 'update', Dummy::class))->shouldBeCalled();

        $payload = ['type' => 'delete', 'payload' => ['id' => '/dummies/2', 'iri' => '/dummies/2', 'type' => 'Dummy']];

        $this->assertEquals([
            ['itemSubscriptionId', $payload],
            ['collectionSubscriptionId', $payload],
        ], $this->subscriptionManager->getPushPayloads($object, 'delete'));
    }
}
