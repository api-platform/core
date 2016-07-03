<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Swagger\EventListener;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Adds the HTTP Link header pointing to the Swagger documentation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SwaggerLinkHeaderResponseListener
{
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Sends the Swagger header on each response.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ('application/swagger' === $event->getResponse()->headers->get('Content-Type')) {
            $event->getResponse()->headers->set(
                'Link',
                sprintf(
                    '<%s>',
                    $this->urlGenerator->generate('api_swagger_vocab', [], UrlGeneratorInterface::ABS_URL)
                )
            );
        }
    }
}
