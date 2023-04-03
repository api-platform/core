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

use ApiPlatform\GraphQl\Resolver\Stage\SerializeStageInterface;
use ApiPlatform\GraphQl\Subscription\SubscriptionIdentifierGeneratorInterface;
use ApiPlatform\GraphQl\Subscription\SubscriptionManager;
use ApiPlatform\GraphQl\Tests\Fixtures\ApiResource\Dummy;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;
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
    private ObjectProphecy $serializeStageProphecy;
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
        $this->serializeStageProphecy = $this->prophesize(SerializeStageInterface::class);
        $this->iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $this->resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $this->subscriptionManager = new SubscriptionManager($this->subscriptionsCacheProphecy->reveal(), $this->subscriptionIdentifierGeneratorProphecy->reveal(), $this->serializeStageProphecy->reveal(), $this->iriConverterProphecy->reveal(), $this->resourceMetadataCollectionFactory->reveal());
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

    public function testGetPushPayloadsNoHit(): void
    {
        $object = new Dummy();

        $this->resourceMetadataCollectionFactory->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([(new Get())->withShortName('Dummy')])),
        ]));

        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(false);
        $this->subscriptionsCacheProphecy->getItem('_dummies_2')->willReturn($cacheItemProphecy->reveal());

        $this->assertEquals([], $this->subscriptionManager->getPushPayloads($object));
    }

    public function testGetPushPayloadsHit(): void
    {
        $object = new Dummy();

        $this->resourceMetadataCollectionFactory->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([(new Get())->withShortName('Dummy')])),
        ]));

        $this->iriConverterProphecy->getIriFromResource($object)->willReturn('/dummies/2');

        $cacheItemProphecy = $this->prophesize(CacheItemInterface::class);
        $cacheItemProphecy->isHit()->willReturn(true);
        $cacheItemProphecy->get()->willReturn([
            ['subscriptionIdFoo', ['fieldsFoo'], ['resultFoo']],
            ['subscriptionIdBar', ['fieldsBar'], ['resultBar']],
        ]);
        $this->subscriptionsCacheProphecy->getItem('_dummies_2')->willReturn($cacheItemProphecy->reveal());

        $this->serializeStageProphecy->__invoke($object, Dummy::class, (new Subscription())->withName('update_subscription')->withShortName('Dummy'), ['fields' => ['fieldsFoo'], 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true])->willReturn(['newResultFoo', 'clientSubscriptionId' => 'client-subscription-id']);
        $this->serializeStageProphecy->__invoke($object, Dummy::class, (new Subscription())->withName('update_subscription')->withShortName('Dummy'), ['fields' => ['fieldsBar'], 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true])->willReturn(['resultBar', 'clientSubscriptionId' => 'client-subscription-id']);

        $this->assertEquals([['subscriptionIdFoo', ['newResultFoo']]], $this->subscriptionManager->getPushPayloads($object));
    }
}
