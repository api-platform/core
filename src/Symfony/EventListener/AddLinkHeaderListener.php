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
use ApiPlatform\Util\CorsTrait;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Mercure\Discovery;
use Symfony\Component\WebLink\Link;

/**
 * Adds the HTTP Link header pointing to the Mercure hub for resources having their updates dispatched.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AddLinkHeaderListener
{
    use CorsTrait;
    use OperationRequestInitiatorTrait;

    public function __construct(private readonly Discovery $discovery, ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null)
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    /**
     * Sends the Mercure header on each response.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);

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
