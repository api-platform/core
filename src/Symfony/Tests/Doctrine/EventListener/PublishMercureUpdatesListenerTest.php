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
use ApiPlatform\GraphQl\Subscription\SubscriptionManagerInterface as GraphQlSubscriptionManagerInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Symfony\Doctrine\EventListener\PublishMercureUpdatesListener;
use ApiPlatform\Symfony\Tests\Fixtures\NotAResource;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity\DummyFriend;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity\DummyMercure;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity\DummyOffer;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity\MercureWithTopicsAndGetOperation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
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
        $iriConverterProphecy->getIriFromResource($toInsert, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummies/1')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toUpdate, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummies/2')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDelete, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummies/3')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDelete)->willReturn('/dummies/3')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDeleteExpressionLanguage)->willReturn('/dummy_friends/4')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDeleteExpressionLanguage, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummy_friends/4')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDeleteMercureOptions)->willReturn('/dummy_offers/5')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDeleteMercureOptions, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummy_offers/5')->shouldBeCalled();

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

        $this->assertEquals(['1', '2', 'mercure_custom_data', 'mercure_options', '{"@id":"\/dummies\/3","@type":"Dummy"}', '{"@id":"\/dummy_friends\/4","@type":"https:\/\/schema.org\/Person"}', '{"@id":"\/dummy_offers\/5","@type":"DummyOffer"}'], $data);
        $this->assertEquals(['http://example.com/dummies/1', 'http://example.com/dummies/2', 'http://example.com/custom_topics/1', '/dummies/1', '/users/3', 'http://example.com/dummies/3', 'http://example.com/dummy_friends/4', 'http://example.com/custom_topics/1'], $topics);
        $this->assertEquals([false, false, false, false, false, true, false], $private);
        $this->assertEquals([null, null, null, null, null, 10, null], $retry);
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

        $iriConverterProphecy->getIriFromResource($toDelete)->willReturn('/mercure_with_topics_and_get_operations/3')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDelete, UrlGeneratorInterface::ABS_URL, null)->willReturn('http://example.com/mercure_with_topics_and_get_operations/3')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDelete, UrlGeneratorInterface::ABS_PATH, null)->willReturn('/mercure_with_topics_and_get_operations/3')->shouldBeCalled();
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
            '{"@type":"MercureWithTopicsAndGetOperation","@id":"/mercure_with_topics_and_get_operations/1","id":1,"name":"Hello World!"}',
            '{"@type":"MercureWithTopicsAndGetOperation","@id":"/mercure_with_topics_and_get_operations/2","id":2,"name":"Hello World!"}',
            '{"@id":"\/mercure_with_topics_and_get_operations\/3","@type":"MercureWithTopicsAndGetOperation"}',
        ], $data);
        $this->assertEquals([
            'http://example.com/mercure_with_topics_and_get_operations/1', '/mercure_with_topics_and_get_operations/1', 'http://example.com/custom_resource/mercure_with_topics_and_get_operations/1',
            'http://example.com/mercure_with_topics_and_get_operations/2', '/mercure_with_topics_and_get_operations/2', 'http://example.com/custom_resource/mercure_with_topics_and_get_operations/2',
            'http://example.com/mercure_with_topics_and_get_operations/3', '/mercure_with_topics_and_get_operations/3', 'http://example.com/custom_resource/mercure_with_topics_and_get_operations/3',
        ], $topics);
    }

    public function testPublishGraphQlUpdates(): void
    {
        $toUpdate = new Dummy();
        $toUpdate->setId(2);

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(Dummy::class))->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($toUpdate, UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/dummies/2');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [(new ApiResource())->withOperations(new Operations([
            'get' => (new Get())->withMercure(['enable_async_update' => false])->withNormalizationContext(['groups' => ['foo', 'bar']]),
        ]))]));

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
        $this->assertEquals([false, false], $private);
        $this->assertEquals([null, null], $retry);
        $this->assertEquals(['2', '["data"]'], $data);
    }

    private function createMockHub(callable $callable): HubInterface
    {
        return new MockHub('https://mercure.demo/.well-known/mercure', new StaticTokenProvider('x'), $callable);
    }
}
