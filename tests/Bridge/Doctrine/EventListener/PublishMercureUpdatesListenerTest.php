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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Bridge\Doctrine\EventListener\PublishMercureUpdatesListener;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\GraphQl\Subscription\MercureSubscriptionIriGeneratorInterface as GraphQlMercureSubscriptionIriGeneratorInterface;
use ApiPlatform\Core\GraphQl\Subscription\SubscriptionManagerInterface as GraphQlSubscriptionManagerInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\NotAResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyFriend;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyMercure;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyOffer;
use ApiPlatform\Core\Tests\ProphecyTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class PublishMercureUpdatesListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @group legacy
     */
    public function testLegacyPublishUpdate(): void
    {
        if (method_exists(Update::class, 'isPrivate')) {
            $this->markTestSkipped();
        }

        $toInsert = new Dummy();
        $toInsert->setId(1);
        $toInsertNotResource = new NotAResource('foo', 'bar');

        $toUpdate = new Dummy();
        $toUpdate->setId(2);
        $toUpdateNoMercureAttribute = new DummyCar();

        $toDelete = new Dummy();
        $toDelete->setId(3);
        $toDeleteExpressionLanguage = new DummyFriend();
        $toDeleteExpressionLanguage->setId(4);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(Dummy::class))->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(DummyCar::class))->willReturn(DummyCar::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(DummyFriend::class))->willReturn(DummyFriend::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(NotAResource::class)->willReturn(false);
        $resourceClassResolverProphecy->isResourceClass(DummyCar::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(DummyFriend::class)->willReturn(true);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($toInsert, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummies/1')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toUpdate, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummies/2')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDelete, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummies/3')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDelete)->willReturn('/dummies/3')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDeleteExpressionLanguage)->willReturn('/dummy_friends/4')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDeleteExpressionLanguage, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummy_friends/4')->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['mercure' => true, 'normalization_context' => ['groups' => ['foo', 'bar']]]));
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata());
        $resourceMetadataFactoryProphecy->create(DummyFriend::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['mercure' => "['foo', 'bar']"]));

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize($toInsert, 'jsonld', ['groups' => ['foo', 'bar']])->willReturn('1');
        $serializerProphecy->serialize($toUpdate, 'jsonld', ['groups' => ['foo', 'bar']])->willReturn('2');

        $formats = ['jsonld' => ['application/ld+json'], 'jsonhal' => ['application/hal+json']];

        $topics = [];
        $targets = [];
        $publisher = function (Update $update) use (&$topics, &$targets): string {
            $topics = array_merge($topics, $update->getTopics());
            $targets[] = $update->getTargets(); // @phpstan-ignore-line

            return 'id';
        };

        $listener = new PublishMercureUpdatesListener(
            $resourceClassResolverProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $serializerProphecy->reveal(),
            $formats,
            null,
            $publisher
        );

        $uowProphecy = $this->prophesize(UnitOfWork::class);
        $uowProphecy->getScheduledEntityInsertions()->willReturn([$toInsert, $toInsertNotResource])->shouldBeCalled();
        $uowProphecy->getScheduledEntityUpdates()->willReturn([$toUpdate, $toUpdateNoMercureAttribute])->shouldBeCalled();
        $uowProphecy->getScheduledEntityDeletions()->willReturn([$toDelete, $toDeleteExpressionLanguage])->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $emProphecy->getUnitOfWork()->willReturn($uowProphecy->reveal())->shouldBeCalled();
        $eventArgs = new OnFlushEventArgs($emProphecy->reveal());

        $listener->onFlush($eventArgs);
        $listener->postFlush();

        $this->assertSame(['http://example.com/dummies/1', 'http://example.com/dummies/2', 'http://example.com/dummies/3', 'http://example.com/dummy_friends/4'], $topics);
        $this->assertSame([['enable_async_update' => true], ['enable_async_update' => true], ['enable_async_update' => true], ['foo', 'bar', 'enable_async_update' => true]], $targets);
    }

    /**
     * @group legacy
     */
    public function testPublishUpdateWithLegacySignature(): void
    {
        if (!method_exists(Update::class, 'isPrivate')) {
            $this->markTestSkipped();
        }

        $toInsert = new Dummy();
        $toInsert->setId(1);
        $toInsertNotResource = new NotAResource('foo', 'bar');

        $toUpdate = new Dummy();
        $toUpdate->setId(2);
        $toUpdateNoMercureAttribute = new DummyCar();
        $toUpdateMercureOptions = new DummyOffer();
        $toUpdateMercureTopicOptions = new DummyMercure();

        $toDelete = new Dummy();
        $toDelete->setId(3);
        $toDeleteExpressionLanguage = new DummyFriend();
        $toDeleteExpressionLanguage->setId(4);
        $toDeleteMercureOptions = new DummyOffer();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(Dummy::class))->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(DummyCar::class))->willReturn(DummyCar::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(DummyFriend::class))->willReturn(DummyFriend::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(DummyOffer::class))->willReturn(DummyOffer::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(DummyMercure::class))->willReturn(DummyMercure::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(NotAResource::class)->willReturn(false);
        $resourceClassResolverProphecy->isResourceClass(DummyCar::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(DummyFriend::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(DummyOffer::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(DummyMercure::class)->willReturn(true);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($toInsert, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummies/1')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toUpdate, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummies/2')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDelete, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummies/3')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDelete)->willReturn('/dummies/3')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDeleteExpressionLanguage)->willReturn('/dummy_friends/4')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDeleteExpressionLanguage, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummy_friends/4')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDeleteMercureOptions)->willReturn('/dummy_offers/5')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDeleteMercureOptions, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummy_offers/5')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDeleteMercureOptions, UrlGeneratorInterface::ABS_PATH)->willReturn('/dummy_offers/5')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDeleteMercureOptions, UrlGeneratorInterface::REL_PATH)->willReturn('./dummy_offers/5')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDeleteMercureOptions, UrlGeneratorInterface::NET_PATH)->willReturn('//example.com/dummy_offers/5')->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['mercure' => true, 'normalization_context' => ['groups' => ['foo', 'bar']]]));
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata());
        $resourceMetadataFactoryProphecy->create(DummyFriend::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['mercure' => ['private' => true, 'retry' => 10]]));
        $resourceMetadataFactoryProphecy->create(DummyOffer::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['mercure' => ['topics' => ['http://example.com/custom_topics/1', '@=iri(object)', '@=iri(object, '.UrlGeneratorInterface::ABS_URL.')', '@=iri(object, '.UrlGeneratorInterface::ABS_PATH.')', '@=iri(object, '.UrlGeneratorInterface::REL_PATH.')', '@=iri(object, '.UrlGeneratorInterface::NET_PATH.')', '@=iri(object) ~ "/?topic=" ~ escape(iri(object))', '@=iri(object) ~ "/?topic=" ~ escape(iri(object, '.UrlGeneratorInterface::ABS_PATH.'))'], 'data' => 'mercure_custom_data', 'normalization_context' => ['groups' => ['baz']]]]));
        $resourceMetadataFactoryProphecy->create(DummyMercure::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['mercure' => ['topics' => ['/dummies/1', '/users/3'], 'normalization_context' => ['groups' => ['baz']]]]));

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize($toInsert, 'jsonld', ['groups' => ['foo', 'bar']])->willReturn('1');
        $serializerProphecy->serialize($toUpdate, 'jsonld', ['groups' => ['foo', 'bar']])->willReturn('2');
        $serializerProphecy->serialize($toUpdateMercureOptions, 'jsonld', ['groups' => ['baz']])->willReturn('mercure_options');
        $serializerProphecy->serialize($toUpdateMercureTopicOptions, 'jsonld', ['groups' => ['baz']])->willReturn('mercure_options');

        $formats = ['jsonld' => ['application/ld+json'], 'jsonhal' => ['application/hal+json']];

        $topics = [];
        $private = [];
        $retry = [];
        $data = [];
        $publisher = function (Update $update) use (&$topics, &$private, &$retry, &$data): string {
            $topics = array_merge($topics, $update->getTopics());
            $private[] = $update->isPrivate();
            $retry[] = $update->getRetry();
            $data[] = $update->getData();

            return 'id';
        };

        $listener = new PublishMercureUpdatesListener(
            $resourceClassResolverProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $serializerProphecy->reveal(),
            $formats,
            null,
            $publisher
        );

        $uowProphecy = $this->prophesize(UnitOfWork::class);
        $uowProphecy->getScheduledEntityInsertions()->willReturn([$toInsert, $toInsertNotResource])->shouldBeCalled();
        $uowProphecy->getScheduledEntityUpdates()->willReturn([$toUpdate, $toUpdateNoMercureAttribute, $toUpdateMercureOptions, $toUpdateMercureTopicOptions])->shouldBeCalled();
        $uowProphecy->getScheduledEntityDeletions()->willReturn([$toDelete, $toDeleteExpressionLanguage, $toDeleteMercureOptions])->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $emProphecy->getUnitOfWork()->willReturn($uowProphecy->reveal())->shouldBeCalled();
        $eventArgs = new OnFlushEventArgs($emProphecy->reveal());

        $listener->onFlush($eventArgs);
        $listener->postFlush();

        $this->assertSame(['1', '2', 'mercure_custom_data', 'mercure_options', '{"@id":"\/dummies\/3"}', '{"@id":"\/dummy_friends\/4"}', '{"@id":"\/dummy_offers\/5"}'], $data);
        $this->assertSame(['http://example.com/dummies/1', 'http://example.com/dummies/2', 'http://example.com/custom_topics/1', 'http://example.com/dummy_offers/5', 'http://example.com/dummy_offers/5', '/dummy_offers/5', './dummy_offers/5', '//example.com/dummy_offers/5', 'http://example.com/dummy_offers/5/?topic=http%3A%2F%2Fexample.com%2Fdummy_offers%2F5', 'http://example.com/dummy_offers/5/?topic=%2Fdummy_offers%2F5', '/dummies/1', '/users/3', 'http://example.com/dummies/3', 'http://example.com/dummy_friends/4', 'http://example.com/custom_topics/1', 'http://example.com/dummy_offers/5', 'http://example.com/dummy_offers/5', '/dummy_offers/5', './dummy_offers/5', '//example.com/dummy_offers/5', 'http://example.com/dummy_offers/5/?topic=http%3A%2F%2Fexample.com%2Fdummy_offers%2F5', 'http://example.com/dummy_offers/5/?topic=%2Fdummy_offers%2F5'], $topics);
        $this->assertSame([false, false, false, false, false, true, false], $private);
        $this->assertSame([null, null, null, null, null, 10, null], $retry);
    }

    public function testPublishUpdate(): void
    {
        if (!class_exists(HubRegistry::class)) {
            $this->markTestSkipped();
        }

        $toInsert = new Dummy();
        $toInsert->setId(1);
        $toInsertNotResource = new NotAResource('foo', 'bar');

        $toUpdate = new Dummy();
        $toUpdate->setId(2);
        $toUpdateNoMercureAttribute = new DummyCar();
        $toUpdateMercureOptions = new DummyOffer();
        $toUpdateMercureTopicOptions = new DummyMercure();

        $toDelete = new Dummy();
        $toDelete->setId(3);
        $toDeleteExpressionLanguage = new DummyFriend();
        $toDeleteExpressionLanguage->setId(4);
        $toDeleteMercureOptions = new DummyOffer();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(Dummy::class))->willReturn(Dummy::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(DummyCar::class))->willReturn(DummyCar::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(DummyFriend::class))->willReturn(DummyFriend::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(DummyOffer::class))->willReturn(DummyOffer::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(DummyMercure::class))->willReturn(DummyMercure::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(NotAResource::class)->willReturn(false);
        $resourceClassResolverProphecy->isResourceClass(DummyCar::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(DummyFriend::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(DummyOffer::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(DummyMercure::class)->willReturn(true);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($toInsert, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummies/1')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toUpdate, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummies/2')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDelete, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummies/3')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDelete)->willReturn('/dummies/3')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDeleteExpressionLanguage)->willReturn('/dummy_friends/4')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDeleteExpressionLanguage, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummy_friends/4')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDeleteMercureOptions)->willReturn('/dummy_offers/5')->shouldBeCalled();
        $iriConverterProphecy->getIriFromItem($toDeleteMercureOptions, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummy_offers/5')->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['mercure' => ['hub' => 'managed', 'enable_async_update' => false], 'normalization_context' => ['groups' => ['foo', 'bar']]]));
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata());
        $resourceMetadataFactoryProphecy->create(DummyFriend::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['mercure' => ['private' => true, 'retry' => 10, 'hub' => 'managed', 'enable_async_update' => false]]));
        $resourceMetadataFactoryProphecy->create(DummyOffer::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['mercure' => ['topics' => 'http://example.com/custom_topics/1', 'data' => 'mercure_custom_data', 'normalization_context' => ['groups' => ['baz']], 'hub' => 'managed', 'enable_async_update' => false]]));
        $resourceMetadataFactoryProphecy->create(DummyMercure::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['mercure' => ['topics' => ['/dummies/1', '/users/3'], 'normalization_context' => ['groups' => ['baz']], 'hub' => 'managed', 'enable_async_update' => false]]));

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize($toInsert, 'jsonld', ['groups' => ['foo', 'bar']])->willReturn('1');
        $serializerProphecy->serialize($toUpdate, 'jsonld', ['groups' => ['foo', 'bar']])->willReturn('2');
        $serializerProphecy->serialize($toUpdateMercureOptions, 'jsonld', ['groups' => ['baz']])->willReturn('mercure_options');
        $serializerProphecy->serialize($toUpdateMercureTopicOptions, 'jsonld', ['groups' => ['baz']])->willReturn('mercure_options');

        $formats = ['jsonld' => ['application/ld+json'], 'jsonhal' => ['application/hal+json']];

        $topics = [];
        $private = [];
        $retry = [];
        $data = [];

        $managedHub = $this->createMockHub(function (Update $update) use (&$topics, &$private, &$retry, &$data): string {
            $topics = array_merge($topics, $update->getTopics());
            $private[] = $update->isPrivate();
            $retry[] = $update->getRetry();
            $data[] = $update->getData();

            return 'id';
        });

        $listener = new PublishMercureUpdatesListener(
            $resourceClassResolverProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $serializerProphecy->reveal(),
            $formats,
            null,
            new HubRegistry($this->createMock(HubInterface::class), [
                'managed' => $managedHub,
            ])
        );

        $uowProphecy = $this->prophesize(UnitOfWork::class);
        $uowProphecy->getScheduledEntityInsertions()->willReturn([$toInsert, $toInsertNotResource])->shouldBeCalled();
        $uowProphecy->getScheduledEntityUpdates()->willReturn([$toUpdate, $toUpdateNoMercureAttribute, $toUpdateMercureOptions, $toUpdateMercureTopicOptions])->shouldBeCalled();
        $uowProphecy->getScheduledEntityDeletions()->willReturn([$toDelete, $toDeleteExpressionLanguage, $toDeleteMercureOptions])->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $emProphecy->getUnitOfWork()->willReturn($uowProphecy->reveal())->shouldBeCalled();
        $eventArgs = new OnFlushEventArgs($emProphecy->reveal());

        $listener->onFlush($eventArgs);
        $listener->postFlush();

        $this->assertSame(['1', '2', 'mercure_custom_data', 'mercure_options', '{"@id":"\/dummies\/3"}', '{"@id":"\/dummy_friends\/4"}', '{"@id":"\/dummy_offers\/5"}'], $data);
        $this->assertSame(['http://example.com/dummies/1', 'http://example.com/dummies/2', 'http://example.com/custom_topics/1', '/dummies/1', '/users/3', 'http://example.com/dummies/3', 'http://example.com/dummy_friends/4', 'http://example.com/custom_topics/1'], $topics);
        $this->assertSame([false, false, false, false, false, true, false], $private);
        $this->assertSame([null, null, null, null, null, 10, null], $retry);
    }

    public function testPublishGraphQlUpdates(): void
    {
        if (!class_exists(HubRegistry::class)) {
            $this->markTestSkipped();
        }

        $toUpdate = new Dummy();
        $toUpdate->setId(2);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(Dummy::class))->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($toUpdate, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummies/2');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['mercure' => ['enable_async_update' => false], 'normalization_context' => ['groups' => ['foo', 'bar']]]));

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize($toUpdate, 'jsonld', ['groups' => ['foo', 'bar']])->willReturn('2');

        $formats = ['jsonld' => ['application/ld+json'], 'jsonhal' => ['application/hal+json']];

        $topics = [];
        $private = [];
        $retry = [];
        $data = [];

        $defaultHub = $this->createMockHub(function (Update $update) use (&$topics, &$private, &$retry, &$data): string {
            $topics = array_merge($topics, $update->getTopics());
            $private[] = $update->isPrivate();
            $retry[] = $update->getRetry();
            $data[] = $update->getData();

            return 'id';
        });

        $graphQlSubscriptionManagerProphecy = $this->prophesize(GraphQlSubscriptionManagerInterface::class);
        $graphQlSubscriptionId = 'subscription-id';
        $graphQlSubscriptionData = ['data'];
        $graphQlSubscriptionManagerProphecy->getPushPayloads($toUpdate)->willReturn([[$graphQlSubscriptionId, $graphQlSubscriptionData]]);
        $graphQlMercureSubscriptionIriGenerator = $this->prophesize(GraphQlMercureSubscriptionIriGeneratorInterface::class);
        $topicIri = 'subscription-topic-iri';
        $graphQlMercureSubscriptionIriGenerator->generateTopicIri($graphQlSubscriptionId)->willReturn($topicIri);

        $listener = new PublishMercureUpdatesListener(
            $resourceClassResolverProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $serializerProphecy->reveal(),
            $formats,
            null,
            new HubRegistry($defaultHub, ['default' => $defaultHub]),
            $graphQlSubscriptionManagerProphecy->reveal(),
            $graphQlMercureSubscriptionIriGenerator->reveal()
        );

        $uowProphecy = $this->prophesize(UnitOfWork::class);
        $uowProphecy->getScheduledEntityInsertions()->willReturn([])->shouldBeCalled();
        $uowProphecy->getScheduledEntityUpdates()->willReturn([$toUpdate])->shouldBeCalled();
        $uowProphecy->getScheduledEntityDeletions()->willReturn([])->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $emProphecy->getUnitOfWork()->willReturn($uowProphecy->reveal())->shouldBeCalled();
        $eventArgs = new OnFlushEventArgs($emProphecy->reveal());

        $listener->onFlush($eventArgs);
        $listener->postFlush();

        $this->assertSame(['http://example.com/dummies/2', 'subscription-topic-iri'], $topics);
        $this->assertSame([false, false], $private);
        $this->assertSame([null, null], $retry);
        $this->assertSame(['2', '["data"]'], $data);
    }

    public function testInvalidMercureAttribute(): void
    {
        if (!class_exists(HubInterface::class)) {
            $this->markTestSkipped();
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value of the "mercure" attribute of the "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy" resource class must be a boolean, an array of options or an expression returning this array, "integer" given.');

        $toInsert = new Dummy();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(Dummy::class))->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['mercure' => 1]));

        $serializerProphecy = $this->prophesize(SerializerInterface::class);

        $listener = new PublishMercureUpdatesListener(
            $resourceClassResolverProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $serializerProphecy->reveal(),
            ['jsonld' => ['application/ld+json'], 'jsonhal' => ['application/hal+json']],
            null,
            new HubRegistry($this->createMock(HubInterface::class), [])
        );

        $uowProphecy = $this->prophesize(UnitOfWork::class);
        $uowProphecy->getScheduledEntityInsertions()->willReturn([$toInsert])->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $emProphecy->getUnitOfWork()->willReturn($uowProphecy->reveal())->shouldBeCalled();
        $eventArgs = new OnFlushEventArgs($emProphecy->reveal());

        $listener->onFlush($eventArgs);
    }

    /**
     * @group legacy
     */
    public function testInvalidMercureAttributeWithLegacySignature(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value of the "mercure" attribute of the "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy" resource class must be a boolean, an array of options or an expression returning this array, "integer" given.');

        $toInsert = new Dummy();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(Dummy::class))->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['mercure' => 1]));

        $serializerProphecy = $this->prophesize(SerializerInterface::class);

        $listener = new PublishMercureUpdatesListener(
            $resourceClassResolverProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $serializerProphecy->reveal(),
            ['jsonld' => ['application/ld+json'], 'jsonhal' => ['application/hal+json']],
            null,
            function (Update $update): string {
                return 'will never be called';
            }
        );

        $uowProphecy = $this->prophesize(UnitOfWork::class);
        $uowProphecy->getScheduledEntityInsertions()->willReturn([$toInsert])->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $emProphecy->getUnitOfWork()->willReturn($uowProphecy->reveal())->shouldBeCalled();
        $eventArgs = new OnFlushEventArgs($emProphecy->reveal());

        $listener->onFlush($eventArgs);
    }

    private function createMockHub(callable $callable): HubInterface
    {
        return new MockHub('https://mercure.demo/.well-known/mercure', new StaticTokenProvider('x'), $callable);
    }
}
