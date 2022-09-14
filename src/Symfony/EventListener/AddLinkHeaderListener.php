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

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Util\CorsTrait;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Util\RequestAttributesExtractor;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Mercure\Discovery;
use Symfony\Component\WebLink\GenericLinkProvider;
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

    /**
     * @var ResourceMetadataCollectionFactoryInterface|ResourceMetadataFactoryInterface
     */
    private $resourceMetadataFactory;
    private $discovery;

    /**
     * @param Discovery|string $discovery
     * @param mixed            $resourceMetadataFactory
     */
    public function __construct($resourceMetadataFactory, $discovery)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        if ($resourceMetadataFactory && !$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceMetadataCollectionFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        }

        if ($resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            $this->resourceMetadataCollectionFactory = $resourceMetadataFactory;
        }

        $this->discovery = $discovery;
    }

    /**
     * Sends the Mercure header on each response.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        if ($this->isPreflightRequest($request)) {
            return;
        }

        $operation = $this->initializeOperation($request);

        if (
            null === ($resourceClass = $request->attributes->get('_api_resource_class')) ||
            !($attributes = RequestAttributesExtractor::extractAttributes($request))
        ) {
            return;
        }

        $mercure = $operation ? $operation->getMercure() : ($attributes['mercure'] ?? false);
        // TODO: remove in 3.0
        if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            $mercure = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('mercure', false);
        }

        if (!$mercure) {
            return;
        }

        if (!$this->discovery instanceof Discovery) {
            $link = new Link('mercure', $this->discovery);
            if (null === $linkProvider = $request->attributes->get('_links')) {
                $request->attributes->set('_links', new GenericLinkProvider([$link]));

                return;
            }

            $request->attributes->set('_links', $linkProvider->withLink($link));

            return;
        }

        $hub = \is_array($mercure) ? ($mercure['hub'] ?? null) : null;

        $this->discovery->addLink($request, $hub);
    }
}

class_alias(AddLinkHeaderListener::class, \ApiPlatform\Core\Mercure\EventListener\AddLinkHeaderListener::class);
