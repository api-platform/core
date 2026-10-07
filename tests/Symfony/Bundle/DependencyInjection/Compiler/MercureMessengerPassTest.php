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

namespace ApiPlatform\Tests\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\MercureMessengerPass;
use ApiPlatform\Symfony\Messenger\MercureHubStamp;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Messenger\UpdateHandler;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\DependencyInjection\MessengerPass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;

class MercureMessengerPassTest extends TestCase
{
    #[TestWith([null, 'messenger.bus.default'])]
    #[TestWith(['primary', 'messenger.bus.other'])]
    #[TestWith(['private', 'messenger.bus.other'])]
    #[TestWith(['private', 'messenger.bus.default'])]
    #[TestWith(['private', 'messenger.bus.empty'])]
    public function testSelectedHubRetainsHandlerDecorators(?string $hub, string $busId): void
    {
        $update = new Update('https://example.com/decorated', 'decorated', true);
        $default = $this->createMock(HubInterface::class);
        $private = $this->createMock(HubInterface::class);
        $default->expects('private' === $hub ? $this->never() : $this->once())->method('publish')->willReturn('published');
        $private->expects('private' === $hub ? $this->once() : $this->never())->method('publish')->willReturn('published');
        $transport = new InMemoryTransport(Serializer::create());
        $messenger = $this->createMessenger($default, $private, $transport, true);
        $bus = $messenger['buses'][$busId];

        $bus->dispatch(new Envelope($update, [new MercureHubStamp($hub)]));
        $this->assertEmpty($messenger['decorators']['primary']->updates);
        $this->assertEmpty($messenger['decorators']['private']->updates);
        $received = iterator_to_array($transport->get())[0];
        $handled = $bus->dispatch($received->with(new ReceivedStamp('async')));
        $bus->dispatch($handled);

        $this->assertEquals('private' === $hub ? [] : [$update], $messenger['decorators']['primary']->updates);
        $this->assertEquals('private' === $hub ? [$update] : [], $messenger['decorators']['private']->updates);
        $this->assertEquals('private' === $hub ? [$update] : [], $messenger['decorators']['private.outer']->updates);
        $this->assertEquals([$update], $messenger['observer']->updates);
    }

    public function testApplicationUpdateHandlerIsPreserved(): void
    {
        $update = new Update('https://example.com/custom', 'custom');
        $default = $this->createMock(HubInterface::class);
        $default->expects($this->never())->method('publish');
        $private = $this->createMock(HubInterface::class);
        $private->expects($this->once())->method('publish')->with($update)->willReturn('published');
        $audit = $this->createMock(HubInterface::class);
        $audit->expects($this->once())->method('publish')->with($update)->willReturn('audited');
        $messenger = $this->createMessenger($default, $private, audit: $audit);

        $handled = $messenger['buses']['messenger.bus.default']->dispatch(new Envelope($update, [new MercureHubStamp('private')]));

        $this->assertCount(3, $handled->all(HandledStamp::class));
        $this->assertSame([$update], $messenger['observer']->updates);
    }

    #[TestWith([null])]
    #[TestWith(['private'])]
    public function testRegistryWithoutBundleHandlersStillPublishes(?string $hub): void
    {
        $default = $this->createMock(HubInterface::class);
        $private = $this->createMock(HubInterface::class);
        $default->expects(null === $hub ? $this->once() : $this->never())->method('publish')->willReturn('published');
        $private->expects(null === $hub ? $this->never() : $this->once())->method('publish')->willReturn('published');
        $messenger = $this->createMessenger($default, $private, bundleHandlers: false);
        $update = new Update('https://example.com/custom-registry', 'custom registry');

        $handled = $messenger['buses']['messenger.bus.default']->dispatch(new Envelope($update, [new MercureHubStamp($hub)]));

        $this->assertCount(2, $handled->all(HandledStamp::class));
        $this->assertSame([$update], $messenger['observer']->updates);
    }

    #[TestWith([null, false, 'messenger.bus.default'])]
    #[TestWith(['private', false, 'messenger.bus.default'])]
    #[TestWith([null, true, 'messenger.bus.other'])]
    #[TestWith(['private', true, 'messenger.bus.other'])]
    #[TestWith(['private', true, 'messenger.bus.empty'])]
    public function testUpdateRoutingPreservesHubThroughSerialization(?string $hub, bool $json, string $busId): void
    {
        $update = new Update(['https://example.com/subscriptions/1'], '{"name":"changed"}', true, 'event-id', 'update', 1000);
        $default = $this->createMock(HubInterface::class);
        $private = $this->createMock(HubInterface::class);
        $default->expects(null === $hub ? $this->once() : $this->never())->method('publish')->with($this->equalTo($update))->willReturn('published');
        $private->expects(null === $hub ? $this->never() : $this->once())->method('publish')->with($this->equalTo($update))->willReturn('published');
        $transport = new InMemoryTransport($json ? Serializer::create() : new PhpSerializer());
        $messenger = $this->createMessenger($default, $private, $transport);
        $bus = $messenger['buses'][$busId];

        $sent = $bus->dispatch(new Envelope($update, [new MercureHubStamp($hub)]));
        $this->assertEmpty($sent->all(HandledStamp::class));
        $this->assertEmpty($messenger['observer']->updates);
        $this->assertCount(1, $transport->getSent());
        $received = iterator_to_array($transport->get())[0];
        $this->assertNotSame($update, $received->getMessage());
        $this->assertEquals($update, $received->getMessage());
        $this->assertSame($hub, $received->last(MercureHubStamp::class)->getHub());

        $handled = $bus->dispatch($received->with(new ReceivedStamp('async')));
        $this->assertCount(1, $transport->getSent());
        $this->assertCount(2, $handled->all(HandledStamp::class));
        $this->assertEquals([$update], $messenger['observer']->updates);
        // Messenger must retain its normal protection against invoking successful handlers again.
        $bus->dispatch($handled);
        $this->assertCount(1, $messenger['observer']->updates);
    }

    public function testUnstampedUpdatesKeepTheirConfiguredHandler(): void
    {
        $default = $this->createMock(HubInterface::class);
        $default->expects($this->once())->method('publish')->willReturn('default');
        $private = $this->createMock(HubInterface::class);
        $private->expects($this->never())->method('publish');
        $messenger = $this->createMessenger($default, $private, decorate: true);
        $update = new Update('https://example.com/custom', 'legacy');

        $messenger['buses']['messenger.bus.default']->dispatch($update);

        $this->assertSame([$update], $messenger['observer']->updates);
        $this->assertSame([$update], $messenger['decorators']['primary']->updates);
        $this->assertEmpty($messenger['decorators']['private']->updates);
    }

    public function testPublicationWithoutTransportUsesSelectedHub(): void
    {
        $default = $this->createMock(HubInterface::class);
        $default->expects($this->never())->method('publish');
        $private = $this->createMock(HubInterface::class);
        $private->expects($this->once())->method('publish')->willReturn('private');
        $messenger = $this->createMessenger($default, $private);
        $update = new Update('https://example.com/private', 'sync', true);

        $messenger['buses']['messenger.bus.default']->dispatch(new Envelope($update, [new MercureHubStamp('private')]));

        $this->assertSame([$update], $messenger['observer']->updates);
    }

    public function testFailedPublicationCanBeRetriedOnTheSameHub(): void
    {
        $attempts = 0;
        $default = $this->createMock(HubInterface::class);
        $default->expects($this->never())->method('publish');
        $private = $this->createMock(HubInterface::class);
        $private->expects($this->exactly(2))->method('publish')->willReturnCallback(static function () use (&$attempts): string {
            if (1 === ++$attempts) {
                throw new \RuntimeException('Hub unavailable');
            }

            return 'published';
        });
        $messenger = $this->createMessenger($default, $private, decorate: true);
        $bus = $messenger['buses']['messenger.bus.default'];
        $update = new Update('https://example.com/private', 'retry', true);
        try {
            $bus->dispatch(new Envelope($update, [new MercureHubStamp('private')]));
            $this->fail('Publication should have failed.');
        } catch (HandlerFailedException $e) {
            $this->assertSame('Hub unavailable', $e->getPrevious()->getMessage());
            $serializer = new PhpSerializer();
            $retry = $serializer->decode($serializer->encode($e->getEnvelope()));
        }

        $handled = $bus->dispatch($retry);
        $this->assertCount(2, $handled->all(HandledStamp::class));
        $this->assertSame([$update], $messenger['observer']->updates);
        $this->assertEquals([$update, $update], $messenger['decorators']['private']->updates);
        $this->assertEmpty($messenger['decorators']['primary']->updates);
    }

    public function testOtherMessagesAreUnaffected(): void
    {
        $default = $this->createMock(HubInterface::class);
        $default->expects($this->never())->method('publish');
        $private = $this->createMock(HubInterface::class);
        $private->expects($this->never())->method('publish');
        $messenger = $this->createMessenger($default, $private);
        $message = new \stdClass();

        $envelope = $messenger['buses']['messenger.bus.default']->dispatch(new Envelope($message, [new MercureHubStamp('private')]));

        $this->assertSame($message, $envelope->getMessage());
        $this->assertEmpty($envelope->all(HandledStamp::class));
    }

    public function testDisabledMercureDoesNotChangeMessenger(): void
    {
        $container = new ContainerBuilder();
        $container->register('messenger.bus.default', MessageBus::class)->addTag('messenger.bus');
        $definitions = $container->getDefinitions();

        (new MercureMessengerPass())->process($container);

        $this->assertSame($definitions, $container->getDefinitions());
    }

    /** @return array{buses: array<string, MessageBusInterface>, observer: MercureUpdateObserver, decorators: array<string, MercureUpdateHandlerDecorator>} */
    private function createMessenger(HubInterface $default, HubInterface $private, ?InMemoryTransport $transport = null, bool $decorate = false, ?HubInterface $audit = null, bool $bundleHandlers = true): array
    {
        $container = new ContainerBuilder();
        $container->setParameter('api_platform.mercure.include_type', false);
        $container->register('hub.default', HubInterface::class)->setSynthetic(true)->setPublic(true);
        $container->register('hub.private', HubInterface::class)->setSynthetic(true)->setPublic(true);
        $container->register(HubRegistry::class, HubRegistry::class)->setArguments([new Reference('hub.default'), ['primary' => new Reference('hub.default'), 'private' => new Reference('hub.private')]]);
        if ($bundleHandlers) {
            $container->register('mercure.hub.primary.message_handler', UpdateHandler::class)->addArgument(new Reference('hub.default'))->addTag('messenger.message_handler', ['bus' => 'messenger.bus.default']);
            $container->register('mercure.hub.private.message_handler', UpdateHandler::class)->addArgument(new Reference('hub.private'))->addTag('messenger.message_handler', ['bus' => 'messenger.bus.other']);
        }
        if ($audit) {
            $container->register('hub.audit', HubInterface::class)->setSynthetic(true)->setPublic(true);
            // Messenger identifies handlers by class and method unless an alias is set.
            $container->register('app.audit_handler', UpdateHandler::class)->addArgument(new Reference('hub.audit'))->addTag('messenger.message_handler', ['alias' => 'app.audit']);
        }
        if ($decorate) {
            foreach (['primary', 'private'] as $name) {
                $container->register('decorator.'.$name, MercureUpdateHandlerDecorator::class)
                    ->setDecoratedService('mercure.hub.'.$name.'.message_handler')
                    ->addArgument(new Reference('decorator.'.$name.'.inner'))
                    ->setPublic(true);
            }
            $container->register('decorator.private.outer', MercureUpdateHandlerDecorator::class)
                ->setDecoratedService('mercure.hub.private.message_handler', priority: -1)
                ->addArgument(new Reference('decorator.private.outer.inner'))
                ->setPublic(true);
        }
        $container->register('observer', MercureUpdateObserver::class)->addTag('messenger.message_handler')->setSynthetic(true)->setPublic(true);
        $container->register('senders', SendersLocator::class)->setSynthetic(true)->setPublic(true);
        $container->register('messenger.middleware.send_message', SendMessageMiddleware::class)->addArgument(new Reference('senders'));
        $container->register('messenger.middleware.handle_message', HandleMessageMiddleware::class)->setAbstract(true)->setArguments([null, true]);
        foreach (['messenger.bus.default', 'messenger.bus.other', 'messenger.bus.empty'] as $busId) {
            $container->register($busId, MessageBus::class)->setArguments([[]])->addTag('messenger.bus')->setPublic(true);
            $container->setParameter($busId.'.middleware', [['id' => 'send_message'], ['id' => 'handle_message']]);
        }
        // Register in the opposite order: the API Platform pass must precede Messenger's pass.
        $container->addCompilerPass(new MessengerPass());
        $container->addCompilerPass(new MercureMessengerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1);
        $container->compile();
        $container->set('hub.default', $default);
        $container->set('hub.private', $private);
        if ($audit) {
            $container->set('hub.audit', $audit);
        }
        $container->set('senders', new SendersLocator($transport ? [Update::class => ['async']] : [], new ServiceLocator($transport ? ['async' => static fn () => $transport] : [])));

        $observer = new MercureUpdateObserver();
        $container->set('observer', $observer);
        $buses = [];
        foreach (array_keys($container->findTaggedServiceIds('messenger.bus')) as $busId) {
            $buses[$busId] = $container->get($busId);
        }

        $decorators = [];
        if ($decorate) {
            foreach (['primary', 'private', 'private.outer'] as $name) {
                $decorators[$name] = $container->get('decorator.'.$name);
            }
        }

        return ['buses' => $buses, 'observer' => $observer, 'decorators' => $decorators];
    }
}

class MercureUpdateObserver
{
    public array $updates = [];

    public function __invoke(Update $update): void
    {
        $this->updates[] = $update;
    }
}

class MercureUpdateHandlerDecorator
{
    public array $updates = [];

    public function __construct(private readonly object $inner)
    {
    }

    public function __invoke(Update $update): void
    {
        $this->updates[] = $update;
        ($this->inner)($update);
    }
}
