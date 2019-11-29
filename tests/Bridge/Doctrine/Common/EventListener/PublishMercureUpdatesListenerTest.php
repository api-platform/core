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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Common\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\EventListener\AbstractPublishMercureUpdatesListener;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\NotAResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyFriend;
use Doctrine\Common\EventArgs;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class PublishMercureUpdatesListenerTest extends TestCase
{
    protected function getPublishMercureUpdatesListener(array $toInserts, array $toUpdates, array $toDeletes, ResourceClassResolverInterface $resourceClassResolver, IriConverterInterface $iriConverter, ResourceMetadataFactoryInterface $resourceMetadataFactory, SerializerInterface $serializer, array $formats, MessageBusInterface $messageBus = null, callable $publisher = null, ExpressionLanguage $expressionLanguage = null): AbstractPublishMercureUpdatesListener
    {
        return new class($toInserts, $toUpdates, $toDeletes, $resourceClassResolver, $iriConverter, $resourceMetadataFactory, $serializer, $formats, $messageBus, $publisher, $expressionLanguage) extends AbstractPublishMercureUpdatesListener {
            private $toInserts;
            private $toUpdates;
            private $toDeletes;

            public function __construct(array $toInserts, array $toUpdates, array $toDeletes, ResourceClassResolverInterface $resourceClassResolver, IriConverterInterface $iriConverter, ResourceMetadataFactoryInterface $resourceMetadataFactory, SerializerInterface $serializer, array $formats, MessageBusInterface $messageBus = null, callable $publisher = null, ExpressionLanguage $expressionLanguage = null)
            {
                parent::__construct($resourceClassResolver, $iriConverter, $resourceMetadataFactory, $serializer, $formats, $messageBus, $publisher, $expressionLanguage);

                $this->toInserts = $toInserts;
                $this->toUpdates = $toUpdates;
                $this->toDeletes = $toDeletes;
            }

            public function onFlush(EventArgs $eventArgs): void
            {
                foreach ($this->toInserts as $document) {
                    $this->storeObjectToPublish($document, 'createdObjects');
                }

                foreach ($this->toUpdates as $document) {
                    $this->storeObjectToPublish($document, 'updatedObjects');
                }

                foreach ($this->toDeletes as $document) {
                    $this->storeObjectToPublish($document, 'deletedObjects');
                }
            }
        };
    }

    public function testPublishUpdate(): void
    {
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
            $targets[] = $update->getTargets();

            return 'id';
        };

        $listener = $this->getPublishMercureUpdatesListener(
            [$toInsert, $toInsertNotResource],
            [$toUpdate, $toUpdateNoMercureAttribute],
            [$toDelete, $toDeleteExpressionLanguage],
            $resourceClassResolverProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $serializerProphecy->reveal(),
            $formats,
            null,
            $publisher
        );

        $eventArgsProphecy = $this->prophesize(EventArgs::class);

        $listener->onFlush($eventArgsProphecy->reveal());
        $listener->postFlush();

        $this->assertSame(['http://example.com/dummies/1', 'http://example.com/dummies/2', 'http://example.com/dummies/3', 'http://example.com/dummy_friends/4'], $topics);
        $this->assertSame([[], [], [], ['foo', 'bar']], $targets);
    }

    public function testNoPublisher(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A message bus or a publisher must be provided.');

        $this->getPublishMercureUpdatesListener(
            [],
            [],
            [],
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            $this->prophesize(IriConverterInterface::class)->reveal(),
            $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(SerializerInterface::class)->reveal(),
            ['jsonld' => ['application/ld+json'], 'jsonhal' => ['application/hal+json']],
            null,
            null
        );
    }

    public function testInvalidMercureAttribute(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value of the "mercure" attribute of the "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy" resource class must be a boolean, an array of targets or a valid expression, "integer" given.');

        $toInsert = new Dummy();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(Dummy::class))->willReturn(Dummy::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['mercure' => 1]));

        $serializerProphecy = $this->prophesize(SerializerInterface::class);

        $listener = $this->getPublishMercureUpdatesListener(
            [$toInsert],
            [],
            [],
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

        $eventArgsProphecy = $this->prophesize(EventArgs::class);

        $listener->onFlush($eventArgsProphecy->reveal());
        //$listener->postFlush();
    }
}
