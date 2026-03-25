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

use ApiPlatform\GraphQl\Subscription\SubscriptionIdentifierGeneratorInterface;
use ApiPlatform\GraphQl\Subscription\SubscriptionManager;
use ApiPlatform\GraphQl\Tests\Fixtures\ApiResource\Dummy;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\GraphQl\SubscriptionCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\State\ProcessorInterface;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

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
            ->withShortName('Dummy')
            ->withMercure($mercure);
    }

    private function createItemSubscription(array|bool|null $mercure = null): Subscription
    {
        return (new Subscription())
            ->withName('update')
            ->withShortName('Dummy')
            ->withMercure($mercure);
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
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($fields)->willReturn($subscriptionId);
        $cacheItemProphecy->set([[$subscriptionId, $fields, ['result']]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem('_foos_34')->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
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
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($fields)->willReturn($subscriptionId);
        $cacheItemProphecy->set(array_merge($cachedSubscriptions, [[$subscriptionId, $fields, ['result']]]))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem('_foos_34')->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
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
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($fields)->shouldNotBeCalled();
        $this->subscriptionsCacheProphecy->getItem('_foos_34')->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());

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
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($fields)->shouldNotBeCalled();
        $this->subscriptionsCacheProphecy->getItem('_foos_34')->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());

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
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($fields)->willReturn($subscriptionId);
        $cacheItemProphecy->set([[$subscriptionId, $fields, ['result']]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem('_foos_34_'.hash('sha256', 'tenant=42'))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
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
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($fields)->willReturn($subscriptionId);
        $cacheItemProphecy->set([[$subscriptionId, $fields, ['result']]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem('_foos_34_'.hash('sha256', 'tenant=42'))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
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
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($fields)->willReturn($subscriptionId);
        $cacheItemProphecy->set([[$subscriptionId, $fields, ['result']]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem('_foos_34')->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
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
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($fields)->willReturn($subscriptionId);
        $cacheItemProphecy->set([[$subscriptionId, $fields, ['result']]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem('_foos_34_'.hash('sha256', 'region=eu|tenant=42'))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled();

        $this->assertSame($subscriptionId, $this->subscriptionManager->retrieveSubscriptionId($context, $result, $operation));
    }

    public function testRetrieveSubscriptionIdRejectsPrivateFieldsWithoutPrivateMercure(): void
    {
        $infoProphecy = $this->prophesize(ResolveInfo::class);
        $infoProphecy->getFieldSelection(\PHP_INT_MAX)->willReturn(['fields' => true]);

        $context = [
            'args' => ['input' => ['id' => '/foos/34']],
            'info' => $infoProphecy->reveal(),
            'is_collection' => false,
            'is_mutation' => false,
            'is_subscription' => true,
        ];
        $operation = new Subscription(mercure: ['private_fields' => ['tenant']]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"private_fields" requires "mercure.private" to be true.');

        $this->subscriptionManager->retrieveSubscriptionId($context, ['result'], $operation);
    }

    public function testRetrieveSubscriptionIdCollectionOperationUsesCollectionRegistrationPath(): void
    {
        $infoProphecy = $this->prophesize(ResolveInfo::class);
        $fields = ['fields' => true];
        $infoProphecy->getFieldSelection(\PHP_INT_MAX)->willReturn($fields);

        $context = ['args' => ['input' => ['id' => '/foos/34']], 'info' => $infoProphecy->reveal(), 'is_collection' => true, 'is_mutation' => false, 'is_subscription' => true];
        $operation = $this->createCollectionSubscription();

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false);
        $subscriptionId = 'collectionSubscriptionId';
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($fields + ['__collection' => true])->willReturn($subscriptionId);
        $cacheItemProphecy->set([[$subscriptionId, $fields, []]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem('_foos')->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
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
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($fields + ['__collection' => true])->willReturn($subscriptionId);
        $cacheItemProphecy->set([[$subscriptionId, $fields, []]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem('_foos')->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
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
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($fields + ['__collection' => true])->willReturn($subscriptionId);
        $cacheItemProphecy->set([[$subscriptionId, $fields, []]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem('_foos_'.hash('sha256', 'tenant=42'))->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled();

        $this->assertSame($subscriptionId, $this->subscriptionManager->retrieveSubscriptionId($context, null, $operation));
    }

    public function testRetrieveSubscriptionIdCollectionUsesOperationBasedCollectionSubscriptionIri(): void
    {
        $infoProphecy = $this->prophesize(ResolveInfo::class);
        $fields = ['fields' => true];
        $infoProphecy->getFieldSelection(\PHP_INT_MAX)->willReturn($fields);

        $context = ['args' => ['input' => ['id' => '/foos/34']], 'info' => $infoProphecy->reveal(), 'is_collection' => true, 'is_mutation' => false, 'is_subscription' => true];
        $operation = $this->createCollectionSubscription(true)->withClass(Dummy::class);

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false);
        $subscriptionId = 'subscriptionId';
        $this->iriConverterProphecy->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, $operation)->shouldBeCalled()->willReturn('/graphql/dummies');
        $this->subscriptionIdentifierGeneratorProphecy->generateSubscriptionIdentifier($fields + ['__collection' => true])->willReturn($subscriptionId);
        $cacheItemProphecy->set([[$subscriptionId, $fields, []]])->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem('_graphql_dummies')->shouldBeCalled()->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled();

        $this->assertSame($subscriptionId, $this->subscriptionManager->retrieveSubscriptionId($context, null, $operation));
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
        $this->subscriptionsCacheProphecy->getItem('_dummies_2')->willReturn($cacheItemProphecy->reveal());
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
        $this->iriConverterProphecy->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, $collectionOperation)->shouldBeCalled()->willReturn('/graphql/dummies');

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
        $this->subscriptionsCacheProphecy->getItem('_dummies_2')->willReturn($cacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem('_graphql_dummies')->shouldBeCalled()->willReturn($cacheItemProphecyCollection->reveal());
        $this->subscriptionsCacheProphecy->save($cacheItemProphecy->reveal())->shouldBeCalled();

        $this->normalizeProcessor->process(
            $object,
            (new Subscription())->withName('mercure_subscription')->withShortName('Dummy'),
            [],
            ['fields' => ['fieldsFoo'], 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true]
        )->willReturn(
            ['newResultFoo', 'clientSubscriptionId' => 'client-subscription-id']
        );

        $this->normalizeProcessor->process(
            $object,
            (new Subscription())->withName('mercure_subscription')->withShortName('Dummy'),
            [],
            ['fields' => ['fieldsBar'], 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true]
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
        $this->iriConverterProphecy->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, $collectionOperation)->willReturn('/graphql/dummies');

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

        $this->subscriptionsCacheProphecy->getItem('_graphql_dummies')->willReturn($collectionCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem('_dummies_2')->willReturn(
            $itemCacheItemFirstCallProphecy->reveal(),
            $itemCacheItemSecondCallProphecy->reveal()
        );
        $this->subscriptionsCacheProphecy->save($itemCacheItemFirstCallProphecy->reveal())->shouldBeCalledTimes(1);

        $this->normalizeProcessor->process(
            $object,
            (new Subscription())->withName('mercure_subscription')->withShortName('Dummy'),
            [],
            ['fields' => ['fieldsFoo'], 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true]
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
        $this->iriConverterProphecy->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, $collectionOperation)->shouldBeCalled()->willReturn('/graphql/dummies');

        $cacheItemProphecyCollection = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecyCollection->isHit()->willReturn(true);
        $cacheItemProphecyCollection->get()->willReturn([
            ['collectionSubscriptionId', ['collectionFields'], []],
        ]);
        $this->subscriptionsCacheProphecy->getItem('_graphql_dummies')->shouldBeCalled()->willReturn($cacheItemProphecyCollection->reveal());
        $this->subscriptionsCacheProphecy->getItem('_dummies_2')->shouldNotBeCalled();

        $this->normalizeProcessor->process(
            $object,
            (new Subscription())->withName('mercure_subscription')->withShortName('Dummy'),
            [],
            ['fields' => ['collectionFields'], 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true]
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
        $this->iriConverterProphecy->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, $collectionOperation)->shouldBeCalled()->willReturn('/graphql/dummies');

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn([
            ['sharedPrivateCollectionSubscriptionId', ['collectionFields'], []],
        ]);

        $this->subscriptionsCacheProphecy->getItem('_graphql_dummies')->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem(Argument::containingString(hash('sha256', 'tenant=')))->shouldNotBeCalled();

        $this->normalizeProcessor->process(
            $object,
            (new Subscription())->withName('mercure_subscription')->withShortName('Dummy'),
            [],
            ['fields' => ['collectionFields'], 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true]
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
        $partitionKey = hash('sha256', 'tenant=42');

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
        $this->iriConverterProphecy->getIriFromResource($object::class, UrlGeneratorInterface::ABS_PATH, $collectionOperation)->shouldBeCalled()->willReturn('/graphql/dummies');

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn([
            ['partitionedCollectionSubscriptionId', ['collectionFields'], []],
        ]);

        $this->subscriptionsCacheProphecy->getItem('_graphql_dummies_'.$partitionKey)->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());

        $this->normalizeProcessor->process(
            $object,
            (new Subscription())->withName('mercure_subscription')->withShortName('Dummy'),
            [],
            ['fields' => ['collectionFields'], 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true]
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
        $this->iriConverterProphecy->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, $collectionOperation)->shouldBeCalled()->willReturn('/graphql/dummies');

        $itemCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $itemCacheItemProphecy->isHit()->willReturn(false);

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn([
            ['collectionSubscriptionId', ['collectionFields'], []],
        ]);

        $this->subscriptionsCacheProphecy->getItem('_dummies_2')->shouldBeCalled()->willReturn($itemCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem('_graphql_dummies')->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());

        $this->normalizeProcessor->process(
            $object,
            (new Subscription())->withName('mercure_subscription')->withShortName('Dummy'),
            [],
            ['fields' => ['collectionFields'], 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true]
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
        $this->iriConverterProphecy->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, $collectionOperation)->shouldBeCalled()->willReturn('/graphql/dummies');

        $itemCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $itemCacheItemProphecy->isHit()->willReturn(false);

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn([
            ['sharedPrivateCollectionSubscriptionId', ['collectionFields'], []],
        ]);

        $this->subscriptionsCacheProphecy->getItem('_dummies_2')->shouldBeCalled()->willReturn($itemCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem('_graphql_dummies')->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());

        $this->normalizeProcessor->process(
            $object,
            (new Subscription())->withName('mercure_subscription')->withShortName('Dummy'),
            [],
            ['fields' => ['collectionFields'], 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true]
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
        $partitionKey = hash('sha256', 'tenant=42');

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
        $this->iriConverterProphecy->getIriFromResource($object::class, UrlGeneratorInterface::ABS_PATH, $collectionOperation)->shouldBeCalled()->willReturn('/graphql/dummies');

        $itemCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $itemCacheItemProphecy->isHit()->willReturn(false);

        $collectionCacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $collectionCacheItemProphecy->isHit()->willReturn(true);
        $collectionCacheItemProphecy->get()->willReturn([
            ['partitionedCollectionSubscriptionId', ['collectionFields'], []],
        ]);

        $this->subscriptionsCacheProphecy->getItem('_dummies_2_'.$partitionKey)->shouldBeCalled()->willReturn($itemCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem('_graphql_dummies_'.$partitionKey)->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());

        $this->normalizeProcessor->process(
            $object,
            (new Subscription())->withName('mercure_subscription')->withShortName('Dummy'),
            [],
            ['fields' => ['collectionFields'], 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true]
        )->willReturn(
            ['partitionedUpdatedResult', 'clientSubscriptionId' => 'client-subscription-id']
        );

        $this->assertEquals([['partitionedCollectionSubscriptionId', ['partitionedUpdatedResult']]], $this->subscriptionManager->getPushPayloads($object, 'update'));
    }

    public function testGetPushPayloadsDeleteReturnsLightweightPayloadAndRemovesItemCache(): void
    {
        $object = new class {
            public string $id = '/dummies/2';
            public string $iri = '/dummies/2';
            public string $type = 'Dummy';
            public array $private = [];
        };

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

        $this->subscriptionsCacheProphecy->getItem('_dummies_2')->shouldBeCalled()->willReturn($itemCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem('_dummies')->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->hasItem('_dummies_2')->shouldBeCalled()->willReturn(true);
        $this->subscriptionsCacheProphecy->deleteItem('_dummies_2')->shouldBeCalled();

        $payload = ['type' => 'delete', 'payload' => ['id' => '/dummies/2', 'iri' => '/dummies/2', 'type' => 'Dummy']];

        $this->assertEquals([
            ['itemSubscriptionId', $payload],
            ['collectionSubscriptionId', $payload],
        ], $this->subscriptionManager->getPushPayloads($object, 'delete'));
    }

    public function testGetPushPayloadsDeleteReturnsPartitionedPrivatePayloadsAndRemovesPartitionedItemCache(): void
    {
        $object = new class {
            public string $id = '/dummies/2';
            public string $iri = '/dummies/2';
            public string $type = 'Dummy';
            public array $private = ['tenant' => '42'];
        };

        $partitionKey = hash('sha256', 'tenant=42');

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

        $this->subscriptionsCacheProphecy->getItem('_dummies_2_'.$partitionKey)->shouldBeCalled()->willReturn($itemCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem('_dummies_'.$partitionKey)->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->hasItem('_dummies_2_'.$partitionKey)->shouldBeCalled()->willReturn(true);
        $this->subscriptionsCacheProphecy->deleteItem('_dummies_2_'.$partitionKey)->shouldBeCalled();

        $payload = ['type' => 'delete', 'payload' => ['id' => '/dummies/2', 'iri' => '/dummies/2', 'type' => 'Dummy']];

        $this->assertEquals([
            ['partitionedItemSubscriptionId', $payload],
            ['partitionedCollectionSubscriptionId', $payload],
        ], $this->subscriptionManager->getPushPayloads($object, 'delete'));
    }

    public function testGetPushPayloadsDeleteUsesMetadataBasedCollectionSubscriptionIri(): void
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
                'update_collection' => $collectionOperation,
            ]),
        ]));

        $this->iriConverterProphecy->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, $collectionOperation)->shouldBeCalled()->willReturn('/graphql/dummies');

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

        $this->subscriptionsCacheProphecy->getItem('_dummies_2')->shouldBeCalled()->willReturn($itemCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->getItem('_graphql_dummies')->shouldBeCalled()->willReturn($collectionCacheItemProphecy->reveal());
        $this->subscriptionsCacheProphecy->hasItem('_dummies_2')->shouldBeCalled()->willReturn(true);
        $this->subscriptionsCacheProphecy->deleteItem('_dummies_2')->shouldBeCalled();

        $payload = ['type' => 'delete', 'payload' => ['id' => '/dummies/2', 'iri' => '/dummies/2', 'type' => 'Dummy']];

        $this->assertEquals([
            ['itemSubscriptionId', $payload],
            ['collectionSubscriptionId', $payload],
        ], $this->subscriptionManager->getPushPayloads($object, 'delete'));
    }
}
