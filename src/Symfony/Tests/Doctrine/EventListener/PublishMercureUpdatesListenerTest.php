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

namespace ApiPlatform\Symfony\Tests\Doctrine\EventListener;

use ApiPlatform\GraphQl\Subscription\MercureSubscriptionIriGeneratorInterface as GraphQlMercureSubscriptionIriGeneratorInterface;
use ApiPlatform\GraphQl\Subscription\RegisteredSubscription;
use ApiPlatform\GraphQl\Subscription\SubscriptionManagerInterface as GraphQlSubscriptionManagerInterface;
use ApiPlatform\GraphQl\Subscription\SubscriptionUpdate;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\GraphQl\SubscriptionCollection;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Symfony\Doctrine\EventListener\PublishMercureUpdatesListener;
use ApiPlatform\Symfony\Messenger\MercureHubStamp;
use ApiPlatform\Symfony\Tests\Fixtures\NotAResource;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity\DummyFriend;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity\DummyMercure;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity\DummyMercureMultiResource;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity\DummyOffer;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity\MercureWithTopicsAndGetOperation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Argument\Token\TokenInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class PublishMercureUpdatesListenerTest extends TestCase
{
    use ProphecyTrait;

    private static function publication(object $object, string $operationClass): TokenInterface
    {
        return Argument::that(static fn (array $publications): bool => 1 === \count($publications)
            && $publications[0]['operation'] instanceof $operationClass
            && ($object instanceof TokenInterface ? (bool) $object->scoreArgument($publications[0]['object']) : $object === $publications[0]['object']));
    }

    /** @return list<array{Subscription, SubscriptionUpdate}> */
    private static function preparedUpdates(array $payloads, Subscription $operation): array
    {
        $updates = [];
        foreach ($payloads as [$id, $data]) {
            $updates[] = [$operation, new SubscriptionUpdate(new RegisteredSubscription($id, [], false, null), $data, null)];
        }

        return $updates;
    }

    public function testSubscriptionBatchesContainOnlyOneChangedResource(): void
    {
        $first = new Dummy();
        $second = new Dummy();
        $operations = [
            new Subscription(name: 'watch', class: Dummy::class, mercure: true),
            new Subscription(name: 'details', class: Dummy::class, mercure: true),
        ];
        $resolver = $this->createStub(ResourceClassResolverInterface::class);
        $resolver->method('getResourceClass')->willReturn(Dummy::class);
        $resolver->method('isResourceClass')->willReturn(true);
        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadata->method('create')->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([]))->withGraphQlOperations($operations),
        ]));
        $batches = [];
        $subscriptions = $this->createMock(GraphQlSubscriptionManagerInterface::class);
        $subscriptions->expects($this->exactly(2))->method('getUpdates')->willReturnCallback(static function (array $publications, string $type) use (&$batches): iterable {
            self::assertSame('update', $type);
            self::assertCount(2, $publications);
            self::assertSame($publications[0]['object'], $publications[1]['object']);
            self::assertSame(['watch', 'details'], array_map(static fn (array $publication) => $publication['operation']->getName(), $publications));
            $batches[] = $publications[0]['object'];

            return [];
        });
        $hub = $this->createStub(HubInterface::class);
        $listener = new PublishMercureUpdatesListener($resolver, $this->createStub(IriConverterInterface::class), $metadata, $this->createStub(SerializerInterface::class), ['json' => ['application/json']], null, new HubRegistry($hub), $subscriptions, $this->createStub(GraphQlMercureSubscriptionIriGeneratorInterface::class));
        $uow = $this->createStub(UnitOfWork::class);
        $uow->method('getScheduledEntityInsertions')->willReturn([]);
        $uow->method('getScheduledEntityUpdates')->willReturn([$first, $second]);
        $uow->method('getScheduledEntityDeletions')->willReturn([]);
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getUnitOfWork')->willReturn($uow);
        $listener->onFlush(new OnFlushEventArgs($manager));
        $listener->postFlush();
        $this->assertSame([$first, $second], $batches);
    }

    public function testPublishUpdate(): void
    {
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
        $iriConverterProphecy->getIriFromResource($toInsert, UrlGeneratorInterface::ABS_URL, Argument::any())->willReturn('http://example.com/dummies/1')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toUpdate, UrlGeneratorInterface::ABS_URL, Argument::any())->willReturn('http://example.com/dummies/2')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDelete, UrlGeneratorInterface::ABS_URL, Argument::any())->willReturn('http://example.com/dummies/3')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDelete, UrlGeneratorInterface::ABS_PATH, Argument::any())->willReturn('/dummies/3')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDeleteExpressionLanguage, UrlGeneratorInterface::ABS_PATH, Argument::any())->willReturn('/dummy_friends/4')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDeleteExpressionLanguage, UrlGeneratorInterface::ABS_URL, Argument::any())->willReturn('http://example.com/dummy_friends/4')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDeleteMercureOptions, UrlGeneratorInterface::ABS_PATH, Argument::any())->willReturn('/dummy_offers/5')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDeleteMercureOptions, UrlGeneratorInterface::ABS_URL, Argument::any())->willReturn('http://example.com/dummy_offers/5')->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $resourceMetadataFactoryProphecy->create(DummyMercure::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [(new ApiResource())->withOperations(new Operations([
            'get' => (new Get())->withMercure([]),
        ]))]));

        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [(new ApiResource())->withOperations(new Operations([
            'get' => (new Get())->withShortName('Dummy')->withMercure(['hub' => 'managed', 'enable_async_update' => false])->withNormalizationContext(['groups' => ['foo', 'bar']]),
        ]))]));
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadataCollection(DummyCar::class, [(new ApiResource())->withOperations(new Operations([
            'get' => new Get(),
        ]))]));
        $resourceMetadataFactoryProphecy->create(DummyFriend::class)->willReturn(new ResourceMetadataCollection(DummyFriend::class, [(new ApiResource())->withOperations(new Operations([
            'get' => (new Get())->withTypes('https://schema.org/Person')->withShortName('DummyFriend')->withMercure(['private' => true, 'retry' => 10, 'hub' => 'managed', 'enable_async_update' => false]),
        ]))]));
        $resourceMetadataFactoryProphecy->create(DummyOffer::class)->willReturn(new ResourceMetadataCollection(DummyOffer::class, [(new ApiResource())->withOperations(new Operations([
            'get' => (new Get())->withShortName('DummyOffer')->withMercure(['topics' => 'http://example.com/custom_topics/1', 'data' => 'mercure_custom_data', 'hub' => 'managed', 'enable_async_update' => false])->withNormalizationContext(['groups' => ['baz']]),
        ]))]));
        $resourceMetadataFactoryProphecy->create(DummyMercure::class)->willReturn(new ResourceMetadataCollection(DummyMercure::class, [(new ApiResource())->withOperations(new Operations([
            'get' => (new Get())->withMercure(['topics' => ['/dummies/1', '/users/3'], 'hub' => 'managed', 'enable_async_update' => false])->withNormalizationContext(['groups' => ['baz']]),
        ]))]));

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

        $managedHub = $this->createMockHub(static function (Update $update) use (&$topics, &$private, &$retry, &$data): string {
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
            ]),
            null,
            null,
            null,
            true,
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

        $this->assertEquals(['{"@id":"\/dummies\/3","@type":"Dummy"}', '{"@id":"\/dummy_friends\/4","@type":"https:\/\/schema.org\/Person"}', '{"@id":"\/dummy_offers\/5","@type":"DummyOffer"}', '1', '2', 'mercure_custom_data', 'mercure_options'], $data);
        $this->assertEquals(['http://example.com/dummies/3', 'http://example.com/dummy_friends/4', 'http://example.com/custom_topics/1', 'http://example.com/dummies/1', 'http://example.com/dummies/2', 'http://example.com/custom_topics/1', '/dummies/1', '/users/3'], $topics);
        $this->assertEquals([false, true, false, false, false, false, false], $private);
        $this->assertEquals([null, 10, null, null, null, null, null], $retry);
    }

    public function testPublishUpdateMultipleTopicsUsingExpressionLanguage(): void
    {
        $mercure = [
            'topics' => [
                '@=iri(object)',
                '@=iri(object, '.UrlGeneratorInterface::ABS_PATH.')',
                '@=iri(object, '.UrlGeneratorInterface::ABS_URL.', get_operation(object, "/custom_resource/mercure_with_topics_and_get_operations/{id}{._format}"))',
            ],
        ];

        $toInsert = new MercureWithTopicsAndGetOperation();
        $toInsert->id = 1;
        $toInsert->name = 'Hello World!';

        $toUpdate = new MercureWithTopicsAndGetOperation();
        $toUpdate->id = 2;
        $toUpdate->name = 'Hello World!';

        $toDelete = new MercureWithTopicsAndGetOperation();
        $toDelete->id = 3;
        $toDelete->name = 'Hello World!';

        // Even if it's the Post operation which sends Updates to Mercure,
        // the `mercure` configuration is retrieved from the first operation
        // of the resource because the Doctrine Listener doesn't have a
        // reference to the operation.
        $getOperation = (new Get())->withMercure($mercure)->withShortName('MercureWithTopicsAndGetOperation');
        $customGetOperation = (new Get(uriTemplate: '/custom_resource/mercure_with_topics_and_get_operations/{id}{._format}'));
        $postOperation = (new Post());

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(MercureWithTopicsAndGetOperation::class))->willReturn(MercureWithTopicsAndGetOperation::class);
        $resourceClassResolverProphecy->isResourceClass(MercureWithTopicsAndGetOperation::class)->willReturn(true);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $iriConverterProphecy->getIriFromResource($toInsert, UrlGeneratorInterface::ABS_URL, null)->willReturn('http://example.com/mercure_with_topics_and_get_operations/1')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toInsert, UrlGeneratorInterface::ABS_PATH, null)->willReturn('/mercure_with_topics_and_get_operations/1')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toInsert, UrlGeneratorInterface::ABS_URL, Argument::exact($customGetOperation))->willReturn('http://example.com/custom_resource/mercure_with_topics_and_get_operations/1')->shouldBeCalled();

        $iriConverterProphecy->getIriFromResource($toUpdate, UrlGeneratorInterface::ABS_URL, null)->willReturn('http://example.com/mercure_with_topics_and_get_operations/2')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toUpdate, UrlGeneratorInterface::ABS_PATH, null)->willReturn('/mercure_with_topics_and_get_operations/2')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toUpdate, UrlGeneratorInterface::ABS_URL, Argument::exact($customGetOperation))->willReturn('http://example.com/custom_resource/mercure_with_topics_and_get_operations/2')->shouldBeCalled();

        $iriConverterProphecy->getIriFromResource($toDelete, UrlGeneratorInterface::ABS_PATH, Argument::any())->willReturn('/mercure_with_topics_and_get_operations/3')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDelete, UrlGeneratorInterface::ABS_URL, Argument::any())->willReturn('http://example.com/mercure_with_topics_and_get_operations/3')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDelete, UrlGeneratorInterface::ABS_URL, Argument::exact($customGetOperation))->willReturn('http://example.com/custom_resource/mercure_with_topics_and_get_operations/3')->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $resourceMetadataFactoryProphecy->create(MercureWithTopicsAndGetOperation::class)->willReturn(new ResourceMetadataCollection(MercureWithTopicsAndGetOperation::class, [
            (new ApiResource())->withOperations(new Operations([
                'get' => $getOperation,
                'custom_get' => $customGetOperation,
                'post' => $postOperation,
            ])),
        ]))->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize($toInsert, 'jsonld', [])->willReturn('{"@type":"MercureWithTopicsAndGetOperation","@id":"/mercure_with_topics_and_get_operations/1","id":1,"name":"Hello World!"}')->shouldBeCalled();
        $serializerProphecy->serialize($toUpdate, 'jsonld', [])->willReturn('{"@type":"MercureWithTopicsAndGetOperation","@id":"/mercure_with_topics_and_get_operations/2","id":2,"name":"Hello World!"}')->shouldBeCalled();

        $formats = ['jsonld' => ['application/ld+json'], 'jsonhal' => ['application/hal+json']];

        $topics = [];
        $private = [];
        $retry = [];
        $data = [];

        $defaultHub = $this->createMockHub(static function (Update $update) use (&$topics, &$private, &$retry, &$data): string {
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
            new HubRegistry($defaultHub, ['default' => $defaultHub]),
            null,
            null,
            null,
            true,
        );

        $uowProphecy = $this->prophesize(UnitOfWork::class);
        $uowProphecy->getScheduledEntityInsertions()->willReturn([$toInsert])->shouldBeCalled();
        $uowProphecy->getScheduledEntityUpdates()->willReturn([$toUpdate])->shouldBeCalled();
        $uowProphecy->getScheduledEntityDeletions()->willReturn([$toDelete])->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $emProphecy->getUnitOfWork()->willReturn($uowProphecy->reveal())->shouldBeCalled();
        $eventArgs = new OnFlushEventArgs($emProphecy->reveal());

        $listener->onFlush($eventArgs);
        $listener->postFlush();

        $this->assertEquals([
            '{"@id":"\/mercure_with_topics_and_get_operations\/3","@type":"MercureWithTopicsAndGetOperation"}',
            '{"@type":"MercureWithTopicsAndGetOperation","@id":"/mercure_with_topics_and_get_operations/1","id":1,"name":"Hello World!"}',
            '{"@type":"MercureWithTopicsAndGetOperation","@id":"/mercure_with_topics_and_get_operations/2","id":2,"name":"Hello World!"}',
        ], $data);
        $this->assertEquals([
            'http://example.com/mercure_with_topics_and_get_operations/3', '/mercure_with_topics_and_get_operations/3', 'http://example.com/custom_resource/mercure_with_topics_and_get_operations/3',
            'http://example.com/mercure_with_topics_and_get_operations/1', '/mercure_with_topics_and_get_operations/1', 'http://example.com/custom_resource/mercure_with_topics_and_get_operations/1',
            'http://example.com/mercure_with_topics_and_get_operations/2', '/mercure_with_topics_and_get_operations/2', 'http://example.com/custom_resource/mercure_with_topics_and_get_operations/2',
        ], $topics);
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testPublishGraphQlUpdates(bool $privateUpdates): void
    {
        $toUpdate = new Dummy();
        $toUpdate->setId(2);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(Dummy::class))->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($toUpdate, UrlGeneratorInterface::ABS_URL, Argument::any())->willReturn('http://example.com/dummies/2');

        $mercure = ($privateUpdates ? ['private' => true] : []) + ['enable_async_update' => false];
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [(new ApiResource(shortName: 'Dummy'))->withOperations(new Operations([
            'get' => (new Get())->withMercure($mercure)->withNormalizationContext(['groups' => ['foo', 'bar']]),
        ]))->withGraphQlOperations([(new Subscription(name: 'watch', mercure: $mercure))->withNormalizationContext(['groups' => ['foo', 'bar']])])]));

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize($toUpdate, 'jsonld', ['groups' => ['foo', 'bar']])->willReturn('2');

        $formats = ['jsonld' => ['application/ld+json'], 'jsonhal' => ['application/hal+json']];

        $topics = [];
        $private = [];
        $retry = [];
        $data = [];

        $defaultHub = $this->createMockHub(static function (Update $update) use (&$topics, &$private, &$retry, &$data): string {
            $topics = array_merge($topics, $update->getTopics());
            $private[] = $update->isPrivate();
            $retry[] = $update->getRetry();
            $data[] = $update->getData();

            return 'id';
        });

        $graphQlSubscriptionManagerProphecy = $this->prophesize(GraphQlSubscriptionManagerInterface::class);
        $graphQlSubscriptionId = 'subscription-id';
        $graphQlSubscriptionData = ['data'];
        $graphQlSubscriptionManagerProphecy->getUpdates(self::publication($toUpdate, Subscription::class), 'update')->will(static fn (array $args) => self::preparedUpdates([[$graphQlSubscriptionId, $graphQlSubscriptionData]], $args[0][0]['operation']));
        $graphQlSubscriptionManagerProphecy->acknowledge(Argument::type(SubscriptionUpdate::class))->shouldBeCalled();
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
            $graphQlMercureSubscriptionIriGenerator->reveal(),
            null,
            true,
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

        $this->assertEquals(['http://example.com/dummies/2', 'subscription-topic-iri'], $topics);
        $this->assertEquals([$privateUpdates, $privateUpdates], $private);
        $this->assertEquals([null, null], $retry);
        $this->assertEquals(['2', '["data"]'], $data);
    }

    public function testPublishGraphQlCreateUpdates(): void
    {
        $toInsert = new Dummy();
        $toInsert->setId(1);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(Dummy::class))->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($toInsert, UrlGeneratorInterface::ABS_URL, Argument::any())->willReturn('http://example.com/dummies/1');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [(new ApiResource(shortName: 'Dummy'))->withOperations(new Operations([
            'get' => (new Get())->withMercure(['enable_async_update' => false])->withNormalizationContext(['groups' => ['foo', 'bar']]),
        ]))->withGraphQlOperations([(new SubscriptionCollection(name: 'watch', mercure: ['enable_async_update' => false]))->withNormalizationContext(['groups' => ['foo', 'bar']])])]));

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize($toInsert, 'jsonld', ['groups' => ['foo', 'bar']])->willReturn('1');

        $formats = ['jsonld' => ['application/ld+json'], 'jsonhal' => ['application/hal+json']];

        $topics = [];
        $private = [];
        $retry = [];
        $data = [];

        $defaultHub = $this->createMockHub(static function (Update $update) use (&$topics, &$private, &$retry, &$data): string {
            $topics = array_merge($topics, $update->getTopics());
            $private[] = $update->isPrivate();
            $retry[] = $update->getRetry();
            $data[] = $update->getData();

            return 'id';
        });

        $graphQlSubscriptionManagerProphecy = $this->prophesize(GraphQlSubscriptionManagerInterface::class);
        $graphQlSubscriptionId = 'subscription-id';
        $graphQlSubscriptionData = ['data'];
        $graphQlSubscriptionManagerProphecy->getUpdates(self::publication($toInsert, Subscription::class), 'create')->will(static fn (array $args) => self::preparedUpdates([[$graphQlSubscriptionId, $graphQlSubscriptionData]], $args[0][0]['operation']));
        $graphQlSubscriptionManagerProphecy->acknowledge(Argument::type(SubscriptionUpdate::class))->shouldBeCalled();
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
            $graphQlMercureSubscriptionIriGenerator->reveal(),
            null,
            true,
        );

        $uowProphecy = $this->prophesize(UnitOfWork::class);
        $uowProphecy->getScheduledEntityInsertions()->willReturn([$toInsert])->shouldBeCalled();
        $uowProphecy->getScheduledEntityUpdates()->willReturn([])->shouldBeCalled();
        $uowProphecy->getScheduledEntityDeletions()->willReturn([])->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $emProphecy->getUnitOfWork()->willReturn($uowProphecy->reveal())->shouldBeCalled();
        $eventArgs = new OnFlushEventArgs($emProphecy->reveal());

        $listener->onFlush($eventArgs);
        $listener->postFlush();

        $this->assertEquals(['http://example.com/dummies/1', 'subscription-topic-iri'], $topics);
        $this->assertEquals([false, false], $private);
        $this->assertEquals([null, null], $retry);
        $this->assertEquals(['1', '["data"]'], $data);
    }

    public function testPublishGraphQlCreateUpdatesForCollectionSubscriptions(): void
    {
        $toInsert = new Dummy();
        $toInsert->setId(1);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(Dummy::class))->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($toInsert, UrlGeneratorInterface::ABS_URL, Argument::any())->willReturn('http://example.com/dummies/1');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [(new ApiResource(shortName: 'Dummy'))->withOperations(new Operations([
            'get' => (new Get())->withMercure(['enable_async_update' => false])->withNormalizationContext(['groups' => ['foo', 'bar']]),
        ]))->withGraphQlOperations([(new SubscriptionCollection(name: 'watch', mercure: ['enable_async_update' => false]))->withNormalizationContext(['groups' => ['foo', 'bar']])])]));

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize($toInsert, 'jsonld', ['groups' => ['foo', 'bar']])->willReturn('1');

        $formats = ['jsonld' => ['application/ld+json'], 'jsonhal' => ['application/hal+json']];

        $topics = [];
        $private = [];
        $retry = [];
        $data = [];

        $defaultHub = $this->createMockHub(static function (Update $update) use (&$topics, &$private, &$retry, &$data): string {
            $topics = array_merge($topics, $update->getTopics());
            $private[] = $update->isPrivate();
            $retry[] = $update->getRetry();
            $data[] = $update->getData();

            return 'id';
        });

        $graphQlSubscriptionManagerProphecy = $this->prophesize(GraphQlSubscriptionManagerInterface::class);
        $graphQlCollectionSubscriptionPayloads = [
            ['collection-subscription-id-1', ['data' => ['collection' => 'first']]],
            ['collection-subscription-id-2', ['data' => ['collection' => 'second']]],
        ];
        $graphQlSubscriptionManagerProphecy->getUpdates(self::publication($toInsert, Subscription::class), 'create')->will(static fn (array $args) => self::preparedUpdates($graphQlCollectionSubscriptionPayloads, $args[0][0]['operation']));
        $graphQlSubscriptionManagerProphecy->acknowledge(Argument::type(SubscriptionUpdate::class))->shouldBeCalled();
        $graphQlMercureSubscriptionIriGenerator = $this->prophesize(GraphQlMercureSubscriptionIriGeneratorInterface::class);
        $graphQlMercureSubscriptionIriGenerator->generateTopicIri('collection-subscription-id-1')->willReturn('collection-subscription-topic-iri-1');
        $graphQlMercureSubscriptionIriGenerator->generateTopicIri('collection-subscription-id-2')->willReturn('collection-subscription-topic-iri-2');

        $listener = new PublishMercureUpdatesListener(
            $resourceClassResolverProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $serializerProphecy->reveal(),
            $formats,
            null,
            new HubRegistry($defaultHub, ['default' => $defaultHub]),
            $graphQlSubscriptionManagerProphecy->reveal(),
            $graphQlMercureSubscriptionIriGenerator->reveal(),
            null,
            true,
        );

        $uowProphecy = $this->prophesize(UnitOfWork::class);
        $uowProphecy->getScheduledEntityInsertions()->willReturn([$toInsert])->shouldBeCalled();
        $uowProphecy->getScheduledEntityUpdates()->willReturn([])->shouldBeCalled();
        $uowProphecy->getScheduledEntityDeletions()->willReturn([])->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $emProphecy->getUnitOfWork()->willReturn($uowProphecy->reveal())->shouldBeCalled();
        $eventArgs = new OnFlushEventArgs($emProphecy->reveal());

        $listener->onFlush($eventArgs);
        $listener->postFlush();

        $this->assertEquals(['http://example.com/dummies/1', 'collection-subscription-topic-iri-1', 'collection-subscription-topic-iri-2'], $topics);
        $this->assertEquals([false, false, false], $private);
        $this->assertEquals([null, null, null], $retry);
        $this->assertEquals(['1', '{"data":{"collection":"first"}}', '{"data":{"collection":"second"}}'], $data);
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testPublishGraphQlDeleteUpdates(bool $privateUpdates): void
    {
        $toDelete = new Dummy();
        $toDelete->setId(2);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(Dummy::class))->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($toDelete, UrlGeneratorInterface::ABS_PATH, Argument::any())->willReturn('/dummies/2')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDelete, UrlGeneratorInterface::ABS_URL, Argument::any())->willReturn('http://example.com/dummies/2')->shouldBeCalled();

        $mercure = ($privateUpdates ? ['private' => true] : []) + ['enable_async_update' => false];
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [(new ApiResource(shortName: 'Dummy'))->withOperations(new Operations([
            'get' => (new Get())->withMercure($mercure)->withShortName('Dummy')->withNormalizationContext(['groups' => ['foo', 'bar']]),
        ]))->withGraphQlOperations([(new Subscription(name: 'watch', mercure: $mercure))->withShortName('Dummy')->withNormalizationContext(['groups' => ['foo', 'bar']])])]));

        $serializerProphecy = $this->prophesize(SerializerInterface::class);

        $formats = ['jsonld' => ['application/ld+json'], 'jsonhal' => ['application/hal+json']];

        $topics = [];
        $private = [];
        $retry = [];
        $data = [];

        $defaultHub = $this->createMockHub(static function (Update $update) use (&$topics, &$private, &$retry, &$data): string {
            $topics = array_merge($topics, $update->getTopics());
            $private[] = $update->isPrivate();
            $retry[] = $update->getRetry();
            $data[] = $update->getData();

            return 'id';
        });

        $graphQlSubscriptionManagerProphecy = $this->prophesize(GraphQlSubscriptionManagerInterface::class);
        $graphQlSubscriptionId = 'subscription-id';
        $graphQlSubscriptionData = ['data'];
        $graphQlSubscriptionManagerProphecy->getUpdates(self::publication(Argument::that(static fn ($object): bool => $object instanceof \stdClass && Dummy::class === $object->resourceClass && '/dummies/2' === $object->id && 'http://example.com/dummies/2' === $object->iri && [] === $object->private), Subscription::class), 'delete')->will(static fn (array $args) => self::preparedUpdates([[$graphQlSubscriptionId, $graphQlSubscriptionData]], $args[0][0]['operation']));
        $graphQlSubscriptionManagerProphecy->acknowledge(Argument::type(SubscriptionUpdate::class))->shouldBeCalled();
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
            $graphQlMercureSubscriptionIriGenerator->reveal(),
            null,
            true,
        );

        $uowProphecy = $this->prophesize(UnitOfWork::class);
        $uowProphecy->getScheduledEntityInsertions()->willReturn([])->shouldBeCalled();
        $uowProphecy->getScheduledEntityUpdates()->willReturn([])->shouldBeCalled();
        $uowProphecy->getScheduledEntityDeletions()->willReturn([$toDelete])->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $emProphecy->getUnitOfWork()->willReturn($uowProphecy->reveal())->shouldBeCalled();
        $eventArgs = new OnFlushEventArgs($emProphecy->reveal());

        $listener->onFlush($eventArgs);
        $listener->postFlush();

        $this->assertEquals(['http://example.com/dummies/2', 'subscription-topic-iri'], $topics);
        $this->assertEquals([$privateUpdates, $privateUpdates], $private);
        $this->assertEquals([null, null], $retry);
        $this->assertEquals(['{"@id":"\/dummies\/2","@type":"Dummy"}', '["data"]'], $data);
    }

    public function testPublishGraphQlUpdatesForCollectionSubscriptions(): void
    {
        $toUpdate = new Dummy();
        $toUpdate->setId(2);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(Dummy::class))->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($toUpdate, UrlGeneratorInterface::ABS_URL, Argument::any())->willReturn('http://example.com/dummies/2');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [(new ApiResource(shortName: 'Dummy'))->withOperations(new Operations([
            'get' => (new Get())->withMercure(['enable_async_update' => false])->withNormalizationContext(['groups' => ['foo', 'bar']]),
        ]))->withGraphQlOperations([(new SubscriptionCollection(name: 'watch', mercure: ['enable_async_update' => false]))->withNormalizationContext(['groups' => ['foo', 'bar']])])]));

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize($toUpdate, 'jsonld', ['groups' => ['foo', 'bar']])->willReturn('2');

        $formats = ['jsonld' => ['application/ld+json'], 'jsonhal' => ['application/hal+json']];

        $topics = [];
        $private = [];
        $retry = [];
        $data = [];

        $defaultHub = $this->createMockHub(static function (Update $update) use (&$topics, &$private, &$retry, &$data): string {
            $topics = array_merge($topics, $update->getTopics());
            $private[] = $update->isPrivate();
            $retry[] = $update->getRetry();
            $data[] = $update->getData();

            return 'id';
        });

        $graphQlSubscriptionManagerProphecy = $this->prophesize(GraphQlSubscriptionManagerInterface::class);
        $graphQlCollectionSubscriptionPayloads = [
            ['collection-subscription-id-1', ['data' => ['collection' => 'first']]],
            ['collection-subscription-id-2', ['data' => ['collection' => 'second']]],
        ];
        $graphQlSubscriptionManagerProphecy->getUpdates(self::publication($toUpdate, SubscriptionCollection::class), 'update')->will(static fn (array $args) => self::preparedUpdates($graphQlCollectionSubscriptionPayloads, $args[0][0]['operation']));
        $graphQlSubscriptionManagerProphecy->acknowledge(Argument::type(SubscriptionUpdate::class))->shouldBeCalled();
        $graphQlMercureSubscriptionIriGenerator = $this->prophesize(GraphQlMercureSubscriptionIriGeneratorInterface::class);
        $graphQlMercureSubscriptionIriGenerator->generateTopicIri('collection-subscription-id-1')->willReturn('collection-subscription-topic-iri-1');
        $graphQlMercureSubscriptionIriGenerator->generateTopicIri('collection-subscription-id-2')->willReturn('collection-subscription-topic-iri-2');

        $listener = new PublishMercureUpdatesListener(
            $resourceClassResolverProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $serializerProphecy->reveal(),
            $formats,
            null,
            new HubRegistry($defaultHub, ['default' => $defaultHub]),
            $graphQlSubscriptionManagerProphecy->reveal(),
            $graphQlMercureSubscriptionIriGenerator->reveal(),
            null,
            true,
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

        $this->assertEquals(['http://example.com/dummies/2', 'collection-subscription-topic-iri-1', 'collection-subscription-topic-iri-2'], $topics);
        $this->assertEquals([false, false, false], $private);
        $this->assertEquals([null, null, null], $retry);
        $this->assertEquals(['2', '{"data":{"collection":"first"}}', '{"data":{"collection":"second"}}'], $data);
    }

    public function testPublishGraphQlCreateUpdatesKeepsPrivatePartitionContext(): void
    {
        $toInsert = new class {
            private int $id = 1;
            private int $tenant = 42;

            public function getId(): int
            {
                return $this->id;
            }

            public function getTenant(): int
            {
                return $this->tenant;
            }
        };
        $resourceClass = $toInsert::class;

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type($resourceClass))->willReturn($resourceClass);
        $resourceClassResolverProphecy->isResourceClass($resourceClass)->willReturn(true);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($toInsert, UrlGeneratorInterface::ABS_URL, Argument::any())->willReturn('http://example.com/partitioned_dummies/1');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadataCollection($resourceClass, [(new ApiResource(shortName: 'Dummy'))->withOperations(new Operations([
            'get' => (new Get())->withMercure(['private' => true, 'private_fields' => ['tenant'], 'enable_async_update' => false])->withNormalizationContext(['groups' => ['foo', 'bar']]),
        ]))->withGraphQlOperations([(new SubscriptionCollection(name: 'watch', mercure: ['private' => true, 'private_fields' => ['tenant'], 'enable_async_update' => false]))->withNormalizationContext(['groups' => ['foo', 'bar']])])]));

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize($toInsert, 'jsonld', ['groups' => ['foo', 'bar']])->willReturn('1');

        $formats = ['jsonld' => ['application/ld+json'], 'jsonhal' => ['application/hal+json']];

        $topics = [];
        $private = [];
        $retry = [];
        $data = [];

        $defaultHub = $this->createMockHub(static function (Update $update) use (&$topics, &$private, &$retry, &$data): string {
            $topics = array_merge($topics, $update->getTopics());
            $private[] = $update->isPrivate();
            $retry[] = $update->getRetry();
            $data[] = $update->getData();

            return 'id';
        });

        $graphQlSubscriptionManagerProphecy = $this->prophesize(GraphQlSubscriptionManagerInterface::class);
        $graphQlSubscriptionId = 'subscription-id';
        $graphQlSubscriptionData = ['data'];
        $graphQlSubscriptionManagerProphecy->getUpdates(self::publication($toInsert, Subscription::class), 'create')->will(static fn (array $args) => self::preparedUpdates([[$graphQlSubscriptionId, $graphQlSubscriptionData]], $args[0][0]['operation']));
        $graphQlSubscriptionManagerProphecy->acknowledge(Argument::type(SubscriptionUpdate::class))->shouldBeCalled();
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
            $graphQlMercureSubscriptionIriGenerator->reveal(),
            null,
            true,
        );

        $uowProphecy = $this->prophesize(UnitOfWork::class);
        $uowProphecy->getScheduledEntityInsertions()->willReturn([$toInsert])->shouldBeCalled();
        $uowProphecy->getScheduledEntityUpdates()->willReturn([])->shouldBeCalled();
        $uowProphecy->getScheduledEntityDeletions()->willReturn([])->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $emProphecy->getUnitOfWork()->willReturn($uowProphecy->reveal())->shouldBeCalled();
        $eventArgs = new OnFlushEventArgs($emProphecy->reveal());

        $listener->onFlush($eventArgs);
        $listener->postFlush();

        $this->assertEquals(['http://example.com/partitioned_dummies/1', 'subscription-topic-iri'], $topics);
        $this->assertEquals([true, true], $private);
        $this->assertEquals([null, null], $retry);
        $this->assertEquals(['1', '["data"]'], $data);
    }

    public function testPublishGraphQlUpdatesKeepsPrivatePartitionContext(): void
    {
        $toUpdate = new class {
            private int $id = 2;
            private int $tenant = 42;

            public function getId(): int
            {
                return $this->id;
            }

            public function getTenant(): int
            {
                return $this->tenant;
            }
        };
        $resourceClass = $toUpdate::class;

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type($resourceClass))->willReturn($resourceClass);
        $resourceClassResolverProphecy->isResourceClass($resourceClass)->willReturn(true);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($toUpdate, UrlGeneratorInterface::ABS_URL, Argument::any())->willReturn('http://example.com/partitioned_dummies/2');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadataCollection($resourceClass, [(new ApiResource(shortName: 'Dummy'))->withOperations(new Operations([
            'get' => (new Get())->withMercure(['private' => true, 'private_fields' => ['tenant'], 'enable_async_update' => false])->withNormalizationContext(['groups' => ['foo', 'bar']]),
        ]))->withGraphQlOperations([(new Subscription(name: 'watch', mercure: ['private' => true, 'private_fields' => ['tenant'], 'enable_async_update' => false]))->withNormalizationContext(['groups' => ['foo', 'bar']])])]));

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize($toUpdate, 'jsonld', ['groups' => ['foo', 'bar']])->willReturn('2');

        $formats = ['jsonld' => ['application/ld+json'], 'jsonhal' => ['application/hal+json']];

        $topics = [];
        $private = [];
        $retry = [];
        $data = [];

        $defaultHub = $this->createMockHub(static function (Update $update) use (&$topics, &$private, &$retry, &$data): string {
            $topics = array_merge($topics, $update->getTopics());
            $private[] = $update->isPrivate();
            $retry[] = $update->getRetry();
            $data[] = $update->getData();

            return 'id';
        });

        $graphQlSubscriptionManagerProphecy = $this->prophesize(GraphQlSubscriptionManagerInterface::class);
        $graphQlSubscriptionId = 'subscription-id';
        $graphQlSubscriptionData = ['data'];
        $graphQlSubscriptionManagerProphecy->getUpdates(self::publication($toUpdate, Subscription::class), 'update')->will(static fn (array $args) => self::preparedUpdates([[$graphQlSubscriptionId, $graphQlSubscriptionData]], $args[0][0]['operation']));
        $graphQlSubscriptionManagerProphecy->acknowledge(Argument::type(SubscriptionUpdate::class))->shouldBeCalled();
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
            $graphQlMercureSubscriptionIriGenerator->reveal(),
            null,
            true,
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

        $this->assertEquals(['http://example.com/partitioned_dummies/2', 'subscription-topic-iri'], $topics);
        $this->assertEquals([true, true], $private);
        $this->assertEquals([null, null], $retry);
        $this->assertEquals(['2', '["data"]'], $data);
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testFailedGraphQlPublicationDoesNotAdvanceFingerprint(bool $async): void
    {
        $object = new Dummy();
        $operation = new Subscription(name: 'watch', class: Dummy::class, mercure: ['enable_async_update' => $async]);
        $resolver = $this->createStub(ResourceClassResolverInterface::class);
        $resolver->method('getResourceClass')->willReturn(Dummy::class);
        $resolver->method('isResourceClass')->willReturn(true);
        $iri = $this->createStub(IriConverterInterface::class);
        $iri->method('getIriFromResource')->willReturn('/dummies/1');
        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadata->method('create')->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([new Get(mercure: false)]))->withGraphQlOperations([$operation]),
        ]));
        $normalizer = $this->createStub(\ApiPlatform\State\ProcessorInterface::class);
        $normalizer->method('process')->willReturn(['name' => 'Changed']);
        $subscriptions = new \ApiPlatform\GraphQl\Subscription\SubscriptionManager(new \ApiPlatform\GraphQl\Subscription\SubscriptionStore(new \Symfony\Component\Cache\Adapter\ArrayAdapter(), new \Symfony\Component\Cache\Adapter\ArrayAdapter(), new \Symfony\Component\Lock\LockFactory(new \Symfony\Component\Lock\Store\InMemoryStore())), new \ApiPlatform\GraphQl\Subscription\SubscriptionIdentifierGenerator(), $normalizer, $iri);
        $info = $this->createStub(\GraphQL\Type\Definition\ResolveInfo::class);
        $info->method('getFieldSelection')->willReturn(['name' => true]);
        $subscriptions->retrieveSubscriptionId(['args' => ['input' => ['id' => '/dummies/1']], 'info' => $info], ['name' => 'Old'], $operation);
        $topics = $this->createStub(GraphQlMercureSubscriptionIriGeneratorInterface::class);
        $topics->method('generateTopicIri')->willReturn('https://example.com/subscription');
        $attempts = 0;
        $publication = static function (Update $update) use (&$attempts): string {
            self::assertSame('{"name":"Changed"}', $update->getData());
            if (1 === ++$attempts) {
                throw new \RuntimeException('Publication failed');
            }

            return 'published';
        };
        $hub = $this->createMock(HubInterface::class);
        $hub->expects($async ? $this->never() : $this->exactly(2))->method('publish')->willReturnCallback($publication);
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($async ? $this->exactly(2) : $this->never())->method('dispatch')->willReturnCallback(static function (Envelope $envelope) use ($publication): Envelope {
            $publication($envelope->getMessage());

            return $envelope;
        });
        $listener = new PublishMercureUpdatesListener($resolver, $iri, $metadata, $this->createStub(SerializerInterface::class), ['json' => ['application/json']], $bus, new HubRegistry($hub), $subscriptions, $topics);
        $uow = $this->createStub(UnitOfWork::class);
        $uow->method('getScheduledEntityInsertions')->willReturn([]);
        $uow->method('getScheduledEntityUpdates')->willReturn([$object]);
        $uow->method('getScheduledEntityDeletions')->willReturn([]);
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getUnitOfWork')->willReturn($uow);
        $listener->onFlush(new OnFlushEventArgs($manager));
        try {
            $listener->postFlush();
            $this->fail('Publication must fail.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Publication failed', $e->getMessage());
        }
        // The same payload remains eligible after a failed hub call or dispatch.
        $listener->onFlush(new OnFlushEventArgs($manager));
        $listener->postFlush();
        $this->assertSame(2, $attempts);
        // Once accepted, the same payload is suppressed on subsequent updates.
        $listener->onFlush(new OnFlushEventArgs($manager));
        $listener->postFlush();
        $this->assertSame(2, $attempts);
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testEachOperationChoosesItsDeliveryMode(bool $restAsync): void
    {
        $object = new Dummy();
        $resolver = $this->createStub(ResourceClassResolverInterface::class);
        $resolver->method('getResourceClass')->willReturn(Dummy::class);
        $resolver->method('isResourceClass')->willReturn(true);
        $iri = $this->createStub(IriConverterInterface::class);
        $iri->method('getIriFromResource')->willReturn('https://example.com/dummies/1');
        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadata->method('create')->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([new Get(mercure: ['enable_async_update' => $restAsync])]))->withGraphQlOperations([
                new Subscription(name: 'sync', mercure: ['private' => true, 'hub' => 'scoped', 'enable_async_update' => false]),
                new Subscription(name: 'async', mercure: ['private' => false, 'hub' => 'async', 'enable_async_update' => true]),
                new Subscription(name: 'disabled', mercure: false),
                new QueryCollection(name: 'query'),
            ]),
        ]));
        $serializer = $this->createStub(SerializerInterface::class);
        $serializer->method('serialize')->willReturn('{}');
        $payloads = $this->createMock(GraphQlSubscriptionManagerInterface::class);
        $payloads->expects($this->exactly(2))->method('acknowledge')->with($this->isInstanceOf(SubscriptionUpdate::class));
        $payloads->expects($this->once())->method('getUpdates')->willReturnCallback(static function (array $publications, string $type) use ($object): iterable {
            self::assertCount(2, $publications);
            self::assertSame('update', $type);
            foreach ($publications as ['object' => $data, 'operation' => $operation]) {
                self::assertSame($object, $data);
                self::assertContains($operation->getName(), ['sync', 'async']);
                yield [$operation, new SubscriptionUpdate(new RegisteredSubscription($operation->getName(), [], true, null), ['name' => $operation->getName()], null)];
            }
        });
        $topics = $this->createStub(GraphQlMercureSubscriptionIriGeneratorInterface::class);
        $topics->method('generateTopicIri')->willReturnCallback(static fn (string $id): string => 'https://example.com/subscriptions/'.$id);
        $dispatched = [];
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->exactly($restAsync ? 2 : 1))->method('dispatch')->willReturnCallback(static function (Envelope $envelope) use (&$dispatched): Envelope {
            $dispatched[] = $envelope;

            return $envelope;
        });
        $defaultHub = $this->createMock(HubInterface::class);
        $defaultHub->expects($restAsync ? $this->never() : $this->once())->method('publish')->with($this->callback(static fn (Update $update): bool => ['https://example.com/dummies/1'] === $update->getTopics()))->willReturn('rest-id');
        $scopedHub = $this->createMock(HubInterface::class);
        $scopedHub->expects($this->once())->method('publish')->with($this->callback(static fn (Update $update): bool => $update->isPrivate() && ['https://example.com/subscriptions/sync'] === $update->getTopics() && '{"name":"sync"}' === $update->getData()))->willReturn('graphql-id');
        $listener = new PublishMercureUpdatesListener($resolver, $iri, $metadata, $serializer, ['json' => ['application/json']], $bus, new HubRegistry($defaultHub, ['scoped' => $scopedHub]), $payloads, $topics);
        $uow = $this->createStub(UnitOfWork::class);
        $uow->method('getScheduledEntityInsertions')->willReturn([]);
        $uow->method('getScheduledEntityUpdates')->willReturn([$object]);
        $uow->method('getScheduledEntityDeletions')->willReturn([]);
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getUnitOfWork')->willReturn($uow);
        $listener->onFlush(new OnFlushEventArgs($manager));
        $listener->postFlush();

        $envelope = $dispatched[array_key_last($dispatched)];
        $this->assertSame('async', $envelope->last(MercureHubStamp::class)->getHub());
        if ($restAsync) {
            $this->assertNull($dispatched[0]->last(MercureHubStamp::class)->getHub());
        }
        $update = $envelope->getMessage();
        $this->assertSame(['https://example.com/subscriptions/async'], $update->getTopics());
        $this->assertFalse($update->isPrivate());
        $this->assertSame('{"name":"async"}', $update->getData());
        // A second flush cannot replay publications already delivered.
        $listener->postFlush();
    }

    public function testDeleteSnapshotRejectsMissingPrivateFields(): void
    {
        $object = new \stdClass();
        $resolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resolver->getResourceClass($object)->willReturn(\stdClass::class);
        $resolver->isResourceClass(\stdClass::class)->willReturn(true);
        $metadata = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $metadata->create(\stdClass::class)->willReturn(new ResourceMetadataCollection(\stdClass::class, [
            (new ApiResource())->withOperations(new Operations([new Get(shortName: 'Dummy', mercure: true)]))->withGraphQlOperations([
                new Subscription(name: 'watch', mercure: ['private' => true, 'private_fields' => ['tenant']]),
            ]),
        ]));
        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->never())->method('publish');
        $listener = new PublishMercureUpdatesListener(
            $resolver->reveal(),
            $this->createStub(IriConverterInterface::class),
            $metadata->reveal(),
            $this->createStub(SerializerInterface::class),
            ['jsonld' => ['application/ld+json']],
            hubRegistry: new HubRegistry($hub),
            graphQlSubscriptionManager: $this->createStub(GraphQlSubscriptionManagerInterface::class),
            graphQlMercureSubscriptionIriGenerator: $this->createStub(GraphQlMercureSubscriptionIriGeneratorInterface::class),
        );
        $uow = $this->createStub(UnitOfWork::class);
        $uow->method('getScheduledEntityInsertions')->willReturn([]);
        $uow->method('getScheduledEntityUpdates')->willReturn([]);
        $uow->method('getScheduledEntityDeletions')->willReturn([$object]);
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getUnitOfWork')->willReturn($uow);

        $this->expectException(AccessException::class);
        $listener->onFlush(new OnFlushEventArgs($manager));
    }

    public function testPublishRestDeleteDoesNotReadGraphQlPrivateFields(): void
    {
        $toDelete = new class {
            public function getTenant(): string
            {
                throw new \LogicException('GraphQL private fields must not be read without a subscription manager.');
            }
        };
        $resourceClass = $toDelete::class;

        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->getResourceClass($toDelete)->willReturn($resourceClass);
        $resourceClassResolver->isResourceClass($resourceClass)->willReturn(true);

        $iriConverter = $this->prophesize(IriConverterInterface::class);
        $iriConverter->getIriFromResource($toDelete, UrlGeneratorInterface::ABS_PATH, Argument::any())->willReturn('/partitioned_dummies/2');
        $iriConverter->getIriFromResource($toDelete, UrlGeneratorInterface::ABS_URL, Argument::any())->willReturn('http://example.com/partitioned_dummies/2');

        $metadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $metadataFactory->create($resourceClass)->willReturn(new ResourceMetadataCollection($resourceClass, [
            new ApiResource(operations: [
                new Get(shortName: 'PartitionedDummy', mercure: ['private' => true, 'private_fields' => ['tenant'], 'enable_async_update' => false]),
            ]),
        ]));

        $updates = [];
        $hub = $this->createMockHub(static function (Update $update) use (&$updates): string {
            $updates[] = $update;

            return 'id';
        });
        $listener = new PublishMercureUpdatesListener(
            $resourceClassResolver->reveal(),
            $iriConverter->reveal(),
            $metadataFactory->reveal(),
            $this->createStub(SerializerInterface::class),
            ['jsonld' => ['application/ld+json']],
            hubRegistry: new HubRegistry($hub),
            includeType: true,
        );

        $uow = $this->prophesize(UnitOfWork::class);
        $uow->getScheduledEntityInsertions()->willReturn([]);
        $uow->getScheduledEntityUpdates()->willReturn([]);
        $uow->getScheduledEntityDeletions()->willReturn([$toDelete]);
        $em = $this->prophesize(EntityManagerInterface::class);
        $em->getUnitOfWork()->willReturn($uow->reveal());

        $listener->onFlush(new OnFlushEventArgs($em->reveal()));
        $listener->postFlush();

        $this->assertCount(1, $updates);
        $this->assertSame(['http://example.com/partitioned_dummies/2'], $updates[0]->getTopics());
        $this->assertTrue($updates[0]->isPrivate());
        $this->assertSame(['@id' => '/partitioned_dummies/2', '@type' => 'PartitionedDummy'], json_decode($updates[0]->getData(), true, flags: \JSON_THROW_ON_ERROR));
    }

    public function testPublishGraphQlDeleteUpdatesKeepsPrivatePartitionData(): void
    {
        $toDelete = new class {
            private int $id = 2;
            private int $tenant = 42;

            public function getId(): int
            {
                return $this->id;
            }

            public function getTenant(): int
            {
                return $this->tenant;
            }
        };
        $resourceClass = $toDelete::class;

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type($resourceClass))->willReturn($resourceClass);
        $resourceClassResolverProphecy->isResourceClass($resourceClass)->willReturn(true);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($toDelete, UrlGeneratorInterface::ABS_PATH, Argument::any())->willReturn('/partitioned_dummies/2')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDelete, UrlGeneratorInterface::ABS_URL, Argument::any())->willReturn('http://example.com/partitioned_dummies/2')->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadataCollection($resourceClass, [(new ApiResource(shortName: 'Dummy'))->withOperations(new Operations([
            'get' => (new Get())->withMercure(['private' => true, 'private_fields' => ['tenant'], 'enable_async_update' => false])->withShortName('PartitionedDummy')->withNormalizationContext(['groups' => ['foo', 'bar']]),
        ]))->withGraphQlOperations([
            new Subscription(name: 'watch', mercure: ['private' => true, 'private_fields' => ['tenant']]),
        ])]));

        $serializerProphecy = $this->prophesize(SerializerInterface::class);

        $formats = ['jsonld' => ['application/ld+json'], 'jsonhal' => ['application/hal+json']];

        $topics = [];
        $private = [];
        $retry = [];
        $data = [];

        $defaultHub = $this->createMockHub(static function (Update $update) use (&$topics, &$private, &$retry, &$data): string {
            $topics = array_merge($topics, $update->getTopics());
            $private[] = $update->isPrivate();
            $retry[] = $update->getRetry();
            $data[] = $update->getData();

            return 'id';
        });

        $graphQlSubscriptionManagerProphecy = $this->prophesize(GraphQlSubscriptionManagerInterface::class);
        $graphQlSubscriptionId = 'subscription-id';
        $graphQlSubscriptionData = ['data'];
        $graphQlSubscriptionManagerProphecy->getUpdates(self::publication(Argument::that(static fn ($object) => $object instanceof \stdClass && $resourceClass === $object->resourceClass && '/partitioned_dummies/2' === $object->id && 'http://example.com/partitioned_dummies/2' === $object->iri && ['tenant' => '42'] === $object->private), Subscription::class), 'delete')->will(static fn (array $args) => self::preparedUpdates([[$graphQlSubscriptionId, $graphQlSubscriptionData]], $args[0][0]['operation']));
        $graphQlSubscriptionManagerProphecy->acknowledge(Argument::type(SubscriptionUpdate::class))->shouldBeCalled();
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
            $graphQlMercureSubscriptionIriGenerator->reveal(),
            null,
            true,
        );

        $uowProphecy = $this->prophesize(UnitOfWork::class);
        $uowProphecy->getScheduledEntityInsertions()->willReturn([])->shouldBeCalled();
        $uowProphecy->getScheduledEntityUpdates()->willReturn([])->shouldBeCalled();
        $uowProphecy->getScheduledEntityDeletions()->willReturn([$toDelete])->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $emProphecy->getUnitOfWork()->willReturn($uowProphecy->reveal())->shouldBeCalled();
        $eventArgs = new OnFlushEventArgs($emProphecy->reveal());

        $listener->onFlush($eventArgs);
        $listener->postFlush();

        $this->assertEquals(['http://example.com/partitioned_dummies/2', 'subscription-topic-iri'], $topics);
        $this->assertEquals([true, true], $private);
        $this->assertEquals([null, null], $retry);
        $this->assertEquals(['{"@id":"\/partitioned_dummies\/2","@type":"PartitionedDummy"}', '["data"]'], $data);
    }

    public static function privateFieldValues(): iterable
    {
        yield 'scalar field' => [false];
        yield 'related resource' => [true];
    }

    #[DataProvider('privateFieldValues')]
    public function testPublishGraphQlDeleteUpdatesKeepsPrivatePartitionDataUsingPropertyAccess(bool $related): void
    {
        $toDelete = new class {
            public int $id = 2;
            public int|object $tenant = 42;
        };
        $identifiersExtractor = $this->createMock(IdentifiersExtractorInterface::class);
        if ($related) {
            $toDelete->tenant = (object) ['code' => 42];
            $identifiersExtractor->expects($this->once())->method('getIdentifiersFromItem')->with($toDelete->tenant)->willReturn(['code' => 42]);
        } else {
            $identifiersExtractor->expects($this->never())->method('getIdentifiersFromItem');
        }
        $resourceClass = $toDelete::class;

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type($resourceClass))->willReturn($resourceClass);
        $resourceClassResolverProphecy->isResourceClass($resourceClass)->willReturn(true);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($toDelete, UrlGeneratorInterface::ABS_PATH, Argument::any())->willReturn('/partitioned_dummies/2')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDelete, UrlGeneratorInterface::ABS_URL, Argument::any())->willReturn('http://example.com/partitioned_dummies/2')->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadataCollection($resourceClass, [(new ApiResource(shortName: 'PartitionedDummy'))->withOperations(new Operations([
            'get' => (new Get())->withMercure(['private' => true, 'private_fields' => ['tenant'], 'enable_async_update' => false])->withShortName('PartitionedDummy')->withNormalizationContext(['groups' => ['foo', 'bar']]),
        ]))->withGraphQlOperations([
            new Subscription(name: 'watch', mercure: ['private' => true, 'private_fields' => ['tenant']]),
        ])]));

        $serializerProphecy = $this->prophesize(SerializerInterface::class);

        $formats = ['jsonld' => ['application/ld+json'], 'jsonhal' => ['application/hal+json']];

        $topics = [];
        $private = [];
        $retry = [];
        $data = [];

        $defaultHub = $this->createMockHub(static function (Update $update) use (&$topics, &$private, &$retry, &$data): string {
            $topics = array_merge($topics, $update->getTopics());
            $private[] = $update->isPrivate();
            $retry[] = $update->getRetry();
            $data[] = $update->getData();

            return 'id';
        });

        $graphQlSubscriptionManagerProphecy = $this->prophesize(GraphQlSubscriptionManagerInterface::class);
        $graphQlSubscriptionId = 'subscription-id';
        $graphQlSubscriptionData = ['data'];
        $graphQlSubscriptionManagerProphecy->getUpdates(self::publication(Argument::that(static fn ($object) => $object instanceof \stdClass && $resourceClass === $object->resourceClass && '/partitioned_dummies/2' === $object->id && 'http://example.com/partitioned_dummies/2' === $object->iri && 'PartitionedDummy' === $object->type && ['tenant' => '42'] === $object->private), Subscription::class), 'delete')->will(static fn (array $args) => self::preparedUpdates([[$graphQlSubscriptionId, $graphQlSubscriptionData]], $args[0][0]['operation']));
        $graphQlSubscriptionManagerProphecy->acknowledge(Argument::type(SubscriptionUpdate::class))->shouldBeCalled();
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
            $graphQlMercureSubscriptionIriGenerator->reveal(),
            null,
            true,
            $identifiersExtractor,
        );

        $uowProphecy = $this->prophesize(UnitOfWork::class);
        $uowProphecy->getScheduledEntityInsertions()->willReturn([])->shouldBeCalled();
        $uowProphecy->getScheduledEntityUpdates()->willReturn([])->shouldBeCalled();
        $uowProphecy->getScheduledEntityDeletions()->willReturn([$toDelete])->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $emProphecy->getUnitOfWork()->willReturn($uowProphecy->reveal())->shouldBeCalled();
        $eventArgs = new OnFlushEventArgs($emProphecy->reveal());

        $listener->onFlush($eventArgs);
        $listener->postFlush();

        $this->assertEquals(['http://example.com/partitioned_dummies/2', 'subscription-topic-iri'], $topics);
        $this->assertEquals([true, true], $private);
        $this->assertEquals([null, null], $retry);
        $this->assertEquals(['{"@id":"\/partitioned_dummies\/2","@type":"PartitionedDummy"}', '["data"]'], $data);
    }

    public function testPublishUpdateWithMultipleResources(): void
    {
        $toInsert = new DummyMercureMultiResource();
        $toInsert->id = 1;
        $toInsert->name = 'test';

        $toDelete = new DummyMercureMultiResource();
        $toDelete->id = 2;
        $toDelete->name = 'deleted';

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(DummyMercureMultiResource::class))->willReturn(DummyMercureMultiResource::class);
        $resourceClassResolverProphecy->isResourceClass(DummyMercureMultiResource::class)->willReturn(true);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($toInsert, UrlGeneratorInterface::ABS_URL, Argument::any())->willReturn('http://example.com/admin/dummy_mercures/1', 'http://example.com/dummy_mercures/1');
        $iriConverterProphecy->getIriFromResource($toDelete, UrlGeneratorInterface::ABS_PATH, Argument::any())->willReturn('/admin/dummy_mercures/2', '/dummy_mercures/2');
        $iriConverterProphecy->getIriFromResource($toDelete, UrlGeneratorInterface::ABS_URL, Argument::any())->willReturn('http://example.com/admin/dummy_mercures/2', 'http://example.com/dummy_mercures/2');

        $adminGetOp = (new Get(uriTemplate: '/admin/dummy_mercures/{id}{._format}'))->withShortName('AdminDummyMercure')->withMercure(['enable_async_update' => false, 'hub' => 'managed'])->withNormalizationContext(['groups' => ['admin:read']]);
        $publicGetOp = (new Get(uriTemplate: '/dummy_mercures/{id}{._format}'))->withShortName('DummyMercure')->withMercure(['enable_async_update' => false, 'hub' => 'managed'])->withNormalizationContext(['groups' => ['read']]);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyMercureMultiResource::class)->willReturn(new ResourceMetadataCollection(DummyMercureMultiResource::class, [
            (new ApiResource())->withShortName('AdminDummyMercure')->withOperations(new Operations([
                'get' => $adminGetOp,
            ])),
            (new ApiResource())->withShortName('DummyMercure')->withOperations(new Operations([
                'get' => $publicGetOp,
            ])),
        ]));

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize($toInsert, 'jsonld', ['groups' => ['admin:read']])->willReturn('{"admin":1}');
        $serializerProphecy->serialize($toInsert, 'jsonld', ['groups' => ['read']])->willReturn('{"public":1}');

        $formats = ['jsonld' => ['application/ld+json']];

        $topics = [];
        $data = [];

        $managedHub = $this->createMockHub(static function (Update $update) use (&$topics, &$data): string {
            $topics = array_merge($topics, $update->getTopics());
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
            new HubRegistry($this->createMock(HubInterface::class), ['managed' => $managedHub]),
            null,
            null,
            null,
            true,
        );

        $uowProphecy = $this->prophesize(UnitOfWork::class);
        $uowProphecy->getScheduledEntityInsertions()->willReturn([$toInsert])->shouldBeCalled();
        $uowProphecy->getScheduledEntityUpdates()->willReturn([])->shouldBeCalled();
        $uowProphecy->getScheduledEntityDeletions()->willReturn([$toDelete])->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $emProphecy->getUnitOfWork()->willReturn($uowProphecy->reveal())->shouldBeCalled();
        $eventArgs = new OnFlushEventArgs($emProphecy->reveal());

        $listener->onFlush($eventArgs);
        $listener->postFlush();

        // Both resources should have published updates
        $this->assertCount(4, $data, 'Expected 4 updates: 2 inserts (admin + public) + 2 deletes (admin + public)');
        $this->assertSame(['@id' => '/admin/dummy_mercures/2', '@type' => 'AdminDummyMercure'], json_decode($data[0], true, flags: \JSON_THROW_ON_ERROR));
        $this->assertSame(['@id' => '/dummy_mercures/2', '@type' => 'DummyMercure'], json_decode($data[1], true, flags: \JSON_THROW_ON_ERROR));
        $this->assertEquals('{"admin":1}', $data[2]);
        $this->assertEquals('{"public":1}', $data[3]);
    }

    private function createMockHub(callable $callable): HubInterface
    {
        return new MockHub('https://mercure.demo/.well-known/mercure', new StaticTokenProvider('x'), $callable);
    }
}
