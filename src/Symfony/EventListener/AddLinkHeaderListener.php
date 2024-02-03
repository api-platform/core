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

namespace ApiPlatform\Symfony\EventListener;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\Util\CorsTrait;
use ApiPlatform\State\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Symfony\Util\RequestAttributesExtractor;
use Psr\Link\LinkProviderInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Mercure\Discovery;
use Symfony\Component\WebLink\HttpHeaderSerializer;

/**
 * Adds the HTTP Link header pointing to the Mercure hub for resources having their updates dispatched.
 *
 * @deprecated use ApiPlatform\Symfony\State\MercureLinkProcessor instead
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AddLinkHeaderListener
{
    use CorsTrait;
    use OperationRequestInitiatorTrait;

    public function __construct(
        private readonly Discovery $discovery,
        ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null,
        private readonly HttpHeaderSerializer $serializer = new HttpHeaderSerializer()
    ) {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    /**
     * Sends the Mercure header on each response.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);

        // API Platform 3.2 has a MainController where everything is handled by processors/providers
        if ('api_platform.symfony.main_controller' === $operation?->getController() || $this->isPreflightRequest($request) || $request->attributes->get('_api_platform_disable_listeners')) {
            return;
        }

        // Does the same as the web-link AddLinkHeaderListener as we want to use `_api_platform_links` not `_links`,
        // note that the AddLinkHeaderProcessor is doing it with the MainController
        $linkProvider = $event->getRequest()->attributes->get('_api_platform_links');
        if ($operation && $linkProvider instanceof LinkProviderInterface && $links = $linkProvider->getLinks()) {
            $event->getResponse()->headers->set('Link', $this->serializer->serialize($links), false);
        }

        if (
            null === $request->attributes->get('_api_resource_class')
            || !($attributes = RequestAttributesExtractor::extractAttributes($request))
        ) {
            return;
        }

        $mercure = $operation?->getMercure() ?? ($attributes['mercure'] ?? false);

        if (!$mercure) {
            return;
        }

        $hub = \is_array($mercure) ? ($mercure['hub'] ?? null) : null;
        $this->discovery->addLink($request, $hub);
    }
}
