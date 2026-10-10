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

use ApiPlatform\GraphQl\Subscription\MercureSubscriptionIriGeneratorInterface;
use ApiPlatform\GraphQl\Subscription\RegisteredSubscription;
use ApiPlatform\GraphQl\Subscription\SubscriptionIdentifierGenerator;
use ApiPlatform\GraphQl\Subscription\SubscriptionManager;
use ApiPlatform\GraphQl\Subscription\SubscriptionManagerInterface;
use ApiPlatform\GraphQl\Subscription\SubscriptionStore;
use ApiPlatform\GraphQl\Subscription\SubscriptionUpdate;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\GraphQl\SubscriptionCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Symfony\Doctrine\EventListener\PublishMercureUpdatesListener;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class DeletePublicationCleanupTest extends TestCase
{
    #[TestWith(['create', false])]
    #[TestWith(['create', true])]
    #[TestWith(['update', false])]
    #[TestWith(['update', true])]
    #[TestWith(['delete_rest', false])]
    #[TestWith(['delete_rest', true])]
    #[TestWith(['delete_graphql', false])]
    #[TestWith(['delete_graphql', true])]
    public function testDeliveryFailureDoesNotLeaveDeletedItemSubscriptions(string $failureStage, bool $async): void
    {
        $deleted = [new Dummy(), new Dummy()];
        $deleted[0]->setId(1);
        $deleted[1]->setId(2);
        $other = new Dummy();
        $other->setId(3);
        $later = new Dummy();
        $later->setId(4);
        $options = ['enable_async_update' => $async];
        $item = new Subscription(name: 'watch', class: Dummy::class, mercure: $options);
        $partitionedItem = $item->withName('privateWatch')->withMercure($options + ['private' => true, 'private_fields' => ['id']]);
        $collection = new SubscriptionCollection(name: 'watchCollection', class: Dummy::class, mercure: $options);
        $resolver = $this->createStub(ResourceClassResolverInterface::class);
        $resolver->method('getResourceClass')->willReturn(Dummy::class);
        $resolver->method('isResourceClass')->willReturn(true);
        $iri = $this->createStub(IriConverterInterface::class);
        $iri->method('getIriFromResource')->willReturnCallback(static fn (Dummy $object): string => '/dummies/'.$object->getId());
        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadata->method('create')->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource(shortName: 'Dummy'))->withOperations(new Operations([new Get(mercure: 'delete_rest' === $failureStage ? $options : false)]))->withGraphQlOperations([$item, $partitionedItem, $collection]),
        ]));
        $registry = new ArrayAdapter();
        $fingerprints = new ArrayAdapter();
        $normalizer = $this->createStub(ProcessorInterface::class);
        $normalizer->method('process')->willReturn(['dummy' => ['name' => 'Changed']]);
        $subscriptions = new SubscriptionManager(new SubscriptionStore($registry, $fingerprints, new LockFactory(new InMemoryStore())), new SubscriptionIdentifierGenerator(), $normalizer, $iri);
        $info = $this->createStub(ResolveInfo::class);
        $info->method('getFieldSelection')->willReturn(['dummy' => ['id' => true]]);
        foreach ($deleted as $object) {
            foreach ([$item, $partitionedItem] as $operation) {
                $subscriptions->retrieveSubscriptionId(['args' => ['input' => ['id' => '/dummies/'.$object->getId()]], 'info' => $info, 'graphql_context' => ['previous_object' => $object]], [], $operation);
            }
        }
        $subscriptions->retrieveSubscriptionId(['args' => ['input' => ['id' => '/dummies/1']], 'info' => $info], [], $collection);
        $this->assertCount(5, array_filter($registry->getValues()));
        $this->assertCount(4, array_filter($fingerprints->getValues()));
        $topics = $this->createStub(MercureSubscriptionIriGeneratorInterface::class);
        $topics->method('generateTopicIri')->willReturnCallback(static fn (string $id): string => 'https://example.com/subscriptions/'.$id);
        $failure = new \RuntimeException('Delivery failed');
        $attempted = [];
        $failed = false;
        $publication = static function (Update $update) use ($failureStage, $failure, &$attempted, &$failed): string {
            $data = json_decode($update->getData(), true, flags: \JSON_THROW_ON_ERROR);
            $delete = isset($data['@id']) || 'delete' === ($data['type'] ?? null);
            $attempted[] = $delete ? 'delete' : $failureStage;
            $shouldFail = match ($failureStage) {
                'delete_rest' => isset($data['@id']),
                'delete_graphql' => 'delete' === ($data['type'] ?? null),
                default => !$delete,
            };
            if ($shouldFail) {
                if ($failed) {
                    throw new \RuntimeException('A later delivery also failed');
                }
                $failed = true;
                throw $failure;
            }

            return 'published';
        };
        $count = match ($failureStage) {
            'delete_rest' => 8,
            'delete_graphql' => 6,
            default => 7,
        };
        $hub = $this->createMock(HubInterface::class);
        $hub->expects($async ? $this->never() : $this->exactly($count))->method('publish')->willReturnCallback($publication);
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($async ? $this->exactly($count) : $this->never())->method('dispatch')->willReturnCallback(static function (Envelope $envelope) use ($publication): Envelope {
            $publication($envelope->getMessage());

            return $envelope;
        });
        $listener = new PublishMercureUpdatesListener($resolver, $iri, $metadata, $this->createStub(SerializerInterface::class), ['json' => ['application/json']], $bus, new HubRegistry($hub), $subscriptions, $topics);
        $uow = $this->createStub(UnitOfWork::class);
        $uow->method('getScheduledEntityInsertions')->willReturn('create' === $failureStage ? [$other, $later] : []);
        $uow->method('getScheduledEntityUpdates')->willReturn('update' === $failureStage ? [$other, $later] : []);
        $uow->method('getScheduledEntityDeletions')->willReturn($deleted);
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getUnitOfWork')->willReturn($uow);
        $listener->onFlush(new OnFlushEventArgs($manager));
        $this->assertCount(5, array_filter($registry->getValues()), 'Collecting before a successful flush must not remove registrations.');
        try {
            $listener->postFlush();
            $this->fail('The delivery exception must propagate.');
        } catch (\RuntimeException $e) {
            $this->assertSame($failure, $e);
        }
        $expectedOrder = array_fill(0, str_starts_with($failureStage, 'delete') ? $count : 6, 'delete');
        if (!str_starts_with($failureStage, 'delete')) {
            $expectedOrder[] = $failureStage;
        }
        $this->assertSame($expectedOrder, $attempted, 'Finish deletions before attempting creates or updates.');
        $entries = array_filter($registry->getValues());
        $this->assertCount(1, $entries, 'Only the collection subscription must remain.');
        $this->assertSame([':watchCollection'], array_keys($registry->getItem(array_key_first($entries))->get()));
        $this->assertEmpty(array_filter($fingerprints->getValues()), 'All deleted items must lose their fingerprints.');
        $listener->postFlush(); // Failed publication must also reset the listener's buffers.
    }

    #[TestWith(['delete', false])]
    #[TestWith(['delete', true])]
    #[TestWith(['create', false])]
    #[TestWith(['create', true])]
    #[TestWith(['update', false])]
    #[TestWith(['update', true])]
    public function testFailedDeliveryAcknowledgesOnlyDeletes(string $type, bool $async): void
    {
        $object = new Dummy();
        $object->setId(1);
        $options = ['enable_async_update' => $async];
        $operation = 'create' === $type
            ? new SubscriptionCollection(name: 'watch', class: Dummy::class, mercure: $options)
            : new Subscription(name: 'watch', class: Dummy::class, mercure: $options);
        $resolver = $this->createStub(ResourceClassResolverInterface::class);
        $resolver->method('getResourceClass')->willReturn(Dummy::class);
        $resolver->method('isResourceClass')->willReturn(true);
        $iri = $this->createStub(IriConverterInterface::class);
        $iri->method('getIriFromResource')->willReturn('/dummies/1');
        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadata->method('create')->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource(shortName: 'Dummy'))->withOperations(new Operations([]))->withGraphQlOperations([$operation]),
        ]));
        $subscriptions = $this->createMock(SubscriptionManagerInterface::class);
        $subscriptions->expects($this->once())->method('getUpdates')->willReturnCallback(static function (array $publications, string $event) use ($type): iterable {
            self::assertSame($type, $event);
            foreach (['first', 'second'] as $id) {
                yield [$publications[0]['operation'], new SubscriptionUpdate(new RegisteredSubscription($id, [], false, null), ['id' => $id], 'delete' === $type ? null : 'fingerprint')];
            }
        });
        $events = [];
        $subscriptions->expects('delete' === $type ? $this->exactly(2) : $this->never())->method('acknowledge')->willReturnCallback(static function (SubscriptionUpdate $update) use (&$events): void {
            $events[] = 'acknowledge '.$update->getId();
        });
        $failure = new \RuntimeException('Delivery failed');
        $publication = static function (Update $update) use ($failure, &$events): string {
            $id = json_decode($update->getData(), true, flags: \JSON_THROW_ON_ERROR)['id'];
            $events[] = 'publish '.$id;
            if ('first' === $id) {
                throw $failure;
            }

            return 'published';
        };
        $count = 'delete' === $type ? 2 : 1;
        $hub = $this->createMock(HubInterface::class);
        $hub->expects($async ? $this->never() : $this->exactly($count))->method('publish')->willReturnCallback($publication);
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($async ? $this->exactly($count) : $this->never())->method('dispatch')->willReturnCallback(static function (Envelope $envelope) use ($publication): Envelope {
            $publication($envelope->getMessage());

            return $envelope;
        });
        $topics = $this->createStub(MercureSubscriptionIriGeneratorInterface::class);
        $topics->method('generateTopicIri')->willReturn('https://example.com/subscription');
        $listener = new PublishMercureUpdatesListener($resolver, $iri, $metadata, $this->createStub(SerializerInterface::class), ['json' => ['application/json']], $bus, new HubRegistry($hub), $subscriptions, $topics);
        $uow = $this->createStub(UnitOfWork::class);
        $uow->method('getScheduledEntityInsertions')->willReturn('create' === $type ? [$object] : []);
        $uow->method('getScheduledEntityUpdates')->willReturn('update' === $type ? [$object] : []);
        $uow->method('getScheduledEntityDeletions')->willReturn('delete' === $type ? [$object] : []);
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getUnitOfWork')->willReturn($uow);
        $listener->onFlush(new OnFlushEventArgs($manager));
        try {
            $listener->postFlush();
            $this->fail('The original delivery error must propagate.');
        } catch (\RuntimeException $e) {
            $this->assertSame($failure, $e);
        }
        $this->assertSame('delete' === $type ? ['publish first', 'acknowledge first', 'publish second', 'acknowledge second'] : ['publish first'], $events);
    }
}
