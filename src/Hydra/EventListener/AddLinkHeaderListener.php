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

namespace ApiPlatform\Core\Hydra\EventListener;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Event\EventInterface;
use ApiPlatform\Core\JsonLd\ContextBuilder;
use Fig\Link\GenericLinkProvider;
use Fig\Link\Link;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Adds the HTTP Link header pointing to the Hydra documentation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AddLinkHeaderListener
{
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Sends the Hydra header on each response.
     *
     * @deprecated since version 2.5, to be removed in 3.0.
     */
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        @trigger_error(sprintf('The method %s() is deprecated since 2.5 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        $this->handleEvent($event);
    }

    /**
     * Sends the Hydra header on each response.
     */
    public function handleEvent(/*EventInterface */ $event): void
    {
        $apiDocUrl = $this->urlGenerator->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL);
        $link = new Link(ContextBuilder::HYDRA_NS.'apiDocumentation', $apiDocUrl);

        if ($event instanceof EventInterface) {
            $request = $event->getContext()['request'];
        } elseif ($event instanceof FilterResponseEvent) {
            @trigger_error(sprintf('Passing an instance of "%s" as argument of "%s" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "%s" instead.', FilterResponseEvent::class, __METHOD__, EventInterface::class), E_USER_DEPRECATED);

            $request = $event->getRequest();
        } else {
            return;
        }

        $attributes = $request->attributes;
        if (null === $linkProvider = $attributes->get('_links')) {
            $attributes->set('_links', new GenericLinkProvider([$link]));

            return;
        }
        $attributes->set('_links', $linkProvider->withLink($link));
    }
}
