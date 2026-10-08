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

namespace ApiPlatform\Symfony\Messenger;

use Psr\Container\ContainerInterface;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Messenger\UpdateHandler;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;

/**
 * Selects a single publication destination while retaining custom message handlers.
 *
 * @internal
 */
final class MercureHandlersLocator implements HandlersLocatorInterface
{
    public const HANDLER_OPTION = 'api_platform_mercure_handler';

    public function __construct(
        private readonly HandlersLocatorInterface $decorated,
        private readonly HubRegistry $hubRegistry,
        private readonly ContainerInterface $handlers,
    ) {
    }

    public function getHandlers(Envelope $envelope): iterable
    {
        if (!$envelope->getMessage() instanceof Update || !$stamp = $envelope->last(MercureHubStamp::class)) {
            yield from $this->decorated->getHandlers($envelope);

            return;
        }

        $hub = $this->hubRegistry->getHub($stamp->getHub());
        // The registry does not expose the default hub's name, so match it by identity.
        $name = $stamp->getHub() ?? array_search($hub, $this->hubRegistry->all(), true);

        // Custom registries can expose hubs without a MercureBundle handler.
        $handler = false !== $name && $this->handlers->has($name) ? $this->handlers->get($name) : new UpdateHandler($hub);
        $publisher = new HandlerDescriptor($handler, ['alias' => 'api_platform.mercure']);
        foreach ($this->decorated->getHandlers($envelope) as $handler) {
            if (!$handler->getOption(self::HANDLER_OPTION)) {
                yield $handler;
                continue;
            }

            // Replace the bundle's hub-specific publishers, not application handlers.
            if (null !== $publisher) {
                yield $publisher;
                $publisher = null;
            }
        }

        // The selected hub can belong to a different bus in MercureBundle's configuration.
        if (null !== $publisher) {
            yield $publisher;
        }
    }
}
