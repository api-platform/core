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

namespace ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Symfony\Messenger\MercureHandlersLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Messenger\UpdateHandler;

/**
 * @internal
 */
final class MercureMessengerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('api_platform.mercure.include_type') || !$container->has(HubRegistry::class)) {
            return;
        }

        foreach ($container->findTaggedServiceIds('messenger.message_handler') as $id => $tags) {
            $definition = $container->findDefinition($id);
            if (UpdateHandler::class !== $definition->getClass()) {
                continue;
            }

            $definition->clearTag('messenger.message_handler');
            foreach ($tags as $tag) {
                $definition->addTag('messenger.message_handler', $tag + [MercureHandlersLocator::HANDLER_OPTION => true]);
            }
        }

        foreach ($container->findTaggedServiceIds('messenger.bus') as $id => $tags) {
            $decorator = 'api_platform.mercure.handlers_locator.'.$id;
            $container->register($decorator, MercureHandlersLocator::class)
                ->setDecoratedService($id.'.messenger.handlers_locator')
                ->setArguments([new Reference($decorator.'.inner'), new Reference(HubRegistry::class)]);
        }
    }
}
