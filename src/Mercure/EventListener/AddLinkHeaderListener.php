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
     */
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        $link = new Link('mercure', $this->hub);

        $attributes = $event->getRequest()->attributes;
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
