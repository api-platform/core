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
use ApiPlatform\Core\Util\CorsTrait;
use Fig\Link\GenericLinkProvider;
use Fig\Link\Link;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Adds the HTTP Link header pointing to the Mercure hub for resources having their updates dispatched.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AddLinkHeaderListener
{
    use CorsTrait;

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
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        // Prevent issues with NelmioCorsBundle
        if ($this->isPreflightRequest($request)) {
            return;
        }

        $link = new Link('mercure', $this->hub);

        if (
            null === ($resourceClass = $request->attributes->get('_api_resource_class')) ||
            false === $this->resourceMetadataFactory->create($resourceClass)->getAttribute('mercure', false)
        ) {
            return;
        }

        if (null === $linkProvider = $request->attributes->get('_links')) {
            $request->attributes->set('_links', new GenericLinkProvider([$link]));

            return;
        }

        $request->attributes->set('_links', $linkProvider->withLink($link));
    }
}
