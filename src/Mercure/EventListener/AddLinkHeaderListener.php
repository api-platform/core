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

namespace ApiPlatform\Core\Mercure\EventListener;

use ApiPlatform\Core\Event\EventInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Fig\Link\GenericLinkProvider;
use Fig\Link\Link;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Adds the HTTP Link header pointing to the Mercure hub for resources having their updates dispatched.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AddLinkHeaderListener
{
    private $resourceMetadataFactory;
    private $hub;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, string $hub)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->hub = $hub;
    }

    /**
     * Sends the Mercure header on each response.
     *
     * @deprecated since version 2.5, to be removed in 3.0.
     */
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        @trigger_error(sprintf('The method %s() is deprecated since 2.5 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        $this->handleEvent($event);
    }

    /**
     * Sends the Mercure header on each response.
     */
    public function handleEvent(/*EventInterface */$event): void
    {
        $link = new Link('mercure', $this->hub);

        if ($event instanceof EventInterface) {
            $request = $event->getContext()['request'];
        } elseif ($event instanceof FilterResponseEvent) {
            @trigger_error(sprintf('Passing an instance of "%s" as argument of "%s" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "%s" instead.', FilterResponseEvent::class, __METHOD__, EventInterface::class), E_USER_DEPRECATED);

            $request = $event->getRequest();
        } else {
            return;
        }

        $attributes = $request->attributes;
        if (
            null === ($resourceClass = $attributes->get('_api_resource_class')) ||
            false === $this->resourceMetadataFactory->create($resourceClass)->getAttribute('mercure', false)
        ) {
            return;
        }

        if (null === $linkProvider = $attributes->get('_links')) {
            $attributes->set('_links', new GenericLinkProvider([$link]));

            return;
        }

        $attributes->set('_links', $linkProvider->withLink($link));
    }
}
