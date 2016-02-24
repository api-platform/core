<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\EventListener;

use Negotiation\Negotiator;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Chooses the format to user according to the Accept header and supported formats.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class FormatRequestListener
{
    private $negotiator;
    private $supportedFormats;

    public function __construct(Negotiator $negotiator, array $supportedFormats)
    {
        $this->negotiator = $negotiator;
        $this->supportedFormats = $supportedFormats;
    }

    /**
     * Assigns the format to use to the _api_format Request attribute.
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->attributes->get('_resource_class')) {
            return;
        }

        // Use the Symfony request format if available and applicable
        $requestFormat = $request->getRequestFormat(null);
        $mimeType = $requestFormat ? $request->getMimeType($requestFormat) : null;
        if (null === $mimeType || !in_array($mimeType, $this->supportedFormats)) {
            if (null === $accept = $request->headers->get('Accept')) {
                $mimeType = null;
            } else {
                // Try to guess the best format to use
                $acceptHeader = $this->negotiator->getBest($accept, array_keys($this->supportedFormats));
                $mimeType = $acceptHeader ? $acceptHeader->getType() : null;
            }
        }

        $request->attributes->set('_api_format', $mimeType ? $this->supportedFormats[$mimeType] : reset($this->supportedFormats));
    }
}
