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

use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\JsonLd\ContextBuilder;
use ApiPlatform\Util\CorsTrait;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

/**
 * Adds the HTTP Link header pointing to the Hydra documentation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AddLinkHeaderListener
{
    use CorsTrait;

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    /**
     * Sends the Hydra header on each response.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        // Prevent issues with NelmioCorsBundle
        if ($this->isPreflightRequest($request)) {
            return;
        }

        $apiDocUrl = $this->urlGenerator->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL);
        $link = new Link(ContextBuilder::HYDRA_NS.'apiDocumentation', $apiDocUrl);

        if (null === $linkProvider = $request->attributes->get('_links')) {
            $request->attributes->set('_links', new GenericLinkProvider([$link]));

            return;
        }
        $request->attributes->set('_links', $linkProvider->withLink($link));
    }
}
