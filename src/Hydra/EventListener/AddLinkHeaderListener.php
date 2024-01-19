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

namespace ApiPlatform\Hydra\EventListener;

use ApiPlatform\Api\UrlGeneratorInterface as LegacyUrlGeneratorInterface;
use ApiPlatform\JsonLd\ContextBuilder;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\State\Util\CorsTrait;
use Psr\Link\EvolvableLinkProviderInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

/**
 * Adds the HTTP Link header pointing to the Hydra documentation.
 *
 * @deprecated use ApiPlatform\Hydra\State\HydraLinkProcessor instead
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AddLinkHeaderListener
{
    use CorsTrait;

    public function __construct(private readonly UrlGeneratorInterface|LegacyUrlGeneratorInterface $urlGenerator)
    {
    }

    /**
     * Sends the Hydra header on each response.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        if (($operation = $request->attributes->get('_api_operation')) && 'api_platform.symfony.main_controller' === $operation->getController()) {
            return;
        }

        // Prevent issues with NelmioCorsBundle
        if ($this->isPreflightRequest($request)) {
            return;
        }

        $apiDocUrl = $this->urlGenerator->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL);
        $apiDocLink = new Link(ContextBuilder::HYDRA_NS.'apiDocumentation', $apiDocUrl);
        $linkProvider = $request->attributes->get('_api_platform_links', new GenericLinkProvider());

        if (!$linkProvider instanceof EvolvableLinkProviderInterface) {
            return;
        }

        foreach ($linkProvider->getLinks() as $link) {
            if ($link->getHref() === $apiDocUrl) {
                return;
            }
        }

        $request->attributes->set('_api_platform_links', $linkProvider->withLink($apiDocLink));
    }
}
